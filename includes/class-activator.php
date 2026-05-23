<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Activator {

    public static function activate(): void {
        self::check_requirements();

        $installed_version = get_option( 'ednasurvey_db_version', false );

        if ( false === $installed_version ) {
            // Fresh install: drop any leftover tables and start clean
            self::drop_tables();
        }

        self::create_tables();

        // Run data/config migrations when upgrading an existing install.
        if ( false !== $installed_version ) {
            self::maybe_upgrade( (string) $installed_version );
        }

        self::set_default_options();
        update_option( 'ednasurvey_db_version', EDNASURVEY_DB_VERSION );
        update_option( 'ednasurvey_flush_rewrite', true );
        flush_rewrite_rules();
    }

    /**
     * Run version-gated upgrade migrations. Idempotent and safe to call
     * from both the activation hook and the runtime DB-version check.
     *
     * @param string $from_version Previously installed DB version.
     */
    public static function maybe_upgrade( string $from_version ): void {
        // 2.2.0: water/air/container measurements moved from fixed columns on
        // the sites table into the ednasurvey_site_filters child table.
        if ( version_compare( $from_version, '2.2.0', '<' ) ) {
            self::migrate_filters_to_child_table();
        }
    }

    /**
     * Migrate the legacy fixed measurement columns (watervol1/2, airvol1/2,
     * weight1/2) into the ednasurvey_site_filters child table, set the per-type
     * counts to preserve previous visibility, then drop the old columns.
     */
    private static function migrate_filters_to_child_table(): void {
        global $wpdb;
        $sites   = $wpdb->prefix . 'ednasurvey_sites';
        $filters = $wpdb->prefix . 'ednasurvey_site_filters';

        // Legacy value columns per type, in 1-based index order.
        $legacy = array(
            'water'     => array( 1 => 'watervol1', 2 => 'watervol2' ),
            'air'       => array( 1 => 'airvol1', 2 => 'airvol2' ),
            'container' => array( 1 => 'weight1', 2 => 'weight2' ),
        );

        // Columns actually present on this install.
        $cols = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
                DB_NAME,
                $sites
            )
        );
        $cols = array_map( 'strtolower', (array) $cols );

        $present = array();
        foreach ( $legacy as $type => $indexes ) {
            foreach ( $indexes as $i => $col ) {
                if ( in_array( strtolower( $col ), $cols, true ) ) {
                    $present[ $type ][ $i ] = $col;
                }
            }
        }

        if ( empty( $present ) ) {
            return; // Already migrated.
        }

        // Determine per-type counts that preserve prior visibility, and never
        // hide existing data. Legacy defaults: water visible, air/container not.
        $settings     = get_option( 'ednasurvey_settings', array() );
        $field_config = is_array( $settings ) ? ( $settings['field_config'] ?? array() ) : array();
        $default_active = array( 'water' => true, 'air' => false, 'container' => false );
        $first_col      = array( 'water' => 'watervol1', 'air' => 'airvol1', 'container' => 'weight1' );

        $counts = array( 'water' => 0, 'air' => 0, 'container' => 0 );
        foreach ( array( 'water', 'air', 'container' ) as $type ) {
            if ( ! isset( $present[ $type ] ) ) {
                continue;
            }
            $mode   = $field_config[ $first_col[ $type ] ]['mode'] ?? ( $default_active[ $type ] ? 'enabled' : 'disabled' );
            $active = in_array( $mode, array( 'required', 'enabled' ), true );
            $count  = $active ? 2 : 0;

            // Never hide rows that contain data.
            foreach ( $present[ $type ] as $i => $col ) {
                $has = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sites} WHERE {$col} IS NOT NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                if ( $has > 0 && $i > $count ) {
                    $count = $i;
                }
            }
            $counts[ $type ] = $count;
        }

        // Persist counts.
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        $settings['water_filter_count'] = $counts['water'];
        $settings['air_filter_count']   = $counts['air'];
        $settings['container_count']    = $counts['container'];
        update_option( 'ednasurvey_settings', $settings );

        // Migrate values into the child table. Global running number N follows
        // display order: water, then air, then container.
        // Advance by the final count per type so migrated "<sample_id>-N"
        // values match what new submissions generate (EdnaSurvey_Filter_Fields).
        $order    = array( 'water', 'air', 'container' );
        $base_seq = array();
        $running  = 0;
        foreach ( $order as $type ) {
            $base_seq[ $type ] = $running;
            $running          += $counts[ $type ];
        }

        foreach ( $present as $type => $indexes ) {
            foreach ( $indexes as $i => $col ) {
                $n = $base_seq[ $type ] + $i;
                $wpdb->query( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "INSERT IGNORE INTO {$filters} (site_id, filter_type, filter_index, filter_id, filter_value)
                     SELECT id, '{$type}', {$i}, CONCAT(COALESCE(sample_id, ''), '-{$n}'), {$col}
                     FROM {$sites} WHERE {$col} IS NOT NULL"
                );
            }
        }

        // Drop all legacy fixed measurement columns (value + any leftover ID
        // columns from interim development builds).
        $drop_candidates = array(
            'watervol1', 'watervol2', 'airvol1', 'airvol2', 'weight1', 'weight2',
            'waterfilter1', 'waterfilter2', 'airfilter1', 'airfilter2', 'container1', 'container2',
        );
        foreach ( $drop_candidates as $col ) {
            if ( in_array( strtolower( $col ), $cols, true ) ) {
                $wpdb->query( "ALTER TABLE {$sites} DROP COLUMN {$col}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            }
        }
    }

    private static function check_requirements(): void {
        if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
            deactivate_plugins( EDNASURVEY_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'eDNA Survey requires PHP 8.1 or higher.', 'wp-ednasurvey' ),
                'Plugin Activation Error',
                array( 'back_link' => true )
            );
        }

        global $wp_version;
        if ( version_compare( $wp_version, '6.4', '<' ) ) {
            deactivate_plugins( EDNASURVEY_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'eDNA Survey requires WordPress 6.4 or higher.', 'wp-ednasurvey' ),
                'Plugin Activation Error',
                array( 'back_link' => true )
            );
        }
    }

    public static function drop_tables(): void {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $tables = array(
            "{$prefix}ednasurvey_site_filters",
            "{$prefix}ednasurvey_site_custom_data",
            "{$prefix}ednasurvey_custom_fields",
            "{$prefix}ednasurvey_photos",
            "{$prefix}ednasurvey_messages",
            "{$prefix}ednasurvey_sites",
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
    }

    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Sites table
        $sql = "CREATE TABLE {$prefix}ednasurvey_sites (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            survey_date DATE DEFAULT NULL,
            survey_time TIME DEFAULT NULL,
            latitude DECIMAL(9,6) DEFAULT NULL,
            longitude DECIMAL(10,6) DEFAULT NULL,
            sitename_local VARCHAR(255) DEFAULT '',
            sitename_en VARCHAR(255) DEFAULT '',
            correspondence VARCHAR(255) DEFAULT '',
            collector1 VARCHAR(255) DEFAULT '',
            collector2 VARCHAR(255) DEFAULT '',
            collector3 VARCHAR(255) DEFAULT '',
            collector4 VARCHAR(255) DEFAULT '',
            collector5 VARCHAR(255) DEFAULT '',
            sample_id VARCHAR(255) DEFAULT '',
            filter_name VARCHAR(255) DEFAULT '',
            env_broad VARCHAR(255) DEFAULT '',
            env_local1 VARCHAR(255) DEFAULT '',
            env_local2 VARCHAR(255) DEFAULT '',
            env_local3 VARCHAR(255) DEFAULT '',
            env_local4 VARCHAR(255) DEFAULT '',
            env_local5 VARCHAR(255) DEFAULT '',
            env_local6 VARCHAR(255) DEFAULT '',
            env_local7 VARCHAR(255) DEFAULT '',
            env_medium VARCHAR(255) DEFAULT '',
            weather VARCHAR(255) DEFAULT '',
            wind VARCHAR(255) DEFAULT '',
            notes TEXT,
            internal_sample_id VARCHAR(255) DEFAULT NULL,
            submitted_user_login VARCHAR(60) DEFAULT '',
            submitted_user_email VARCHAR(100) DEFAULT '',
            submitted_user_name VARCHAR(200) DEFAULT '',
            submitted_ip VARCHAR(45) DEFAULT '',
            submitted_hostname VARCHAR(255) DEFAULT '',
            submitted_geo VARCHAR(255) DEFAULT '',
            submitted_at DATETIME DEFAULT NULL,
            submitted_user_agent TEXT DEFAULT NULL,
            submitted_method VARCHAR(20) DEFAULT 'online',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_internal_sample_id (internal_sample_id),
            KEY idx_user_id (user_id),
            KEY idx_survey_date (survey_date)
        ) $charset_collate;";
        dbDelta( $sql );

        // Photos table
        $sql = "CREATE TABLE {$prefix}ednasurvey_photos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(512) NOT NULL,
            file_url VARCHAR(512) NOT NULL,
            mime_type VARCHAR(50) DEFAULT 'image/jpeg',
            exif_latitude DECIMAL(9,6) DEFAULT NULL,
            exif_longitude DECIMAL(10,6) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_site_id (site_id),
            KEY idx_user_id (user_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // Custom fields definition table
        $sql = "CREATE TABLE {$prefix}ednasurvey_custom_fields (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            field_key VARCHAR(100) NOT NULL,
            label_local VARCHAR(255) NOT NULL DEFAULT '',
            label_en VARCHAR(255) NOT NULL DEFAULT '',
            description_local TEXT DEFAULT NULL,
            description_en TEXT DEFAULT NULL,
            example_local TEXT DEFAULT NULL,
            example_en TEXT DEFAULT NULL,
            field_type VARCHAR(50) NOT NULL DEFAULT 'text',
            field_options TEXT DEFAULT NULL,
            field_mode VARCHAR(20) NOT NULL DEFAULT 'enabled',
            default_value TEXT DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_field_key (field_key)
        ) $charset_collate;";
        dbDelta( $sql );

        // Custom field values (EAV)
        $sql = "CREATE TABLE {$prefix}ednasurvey_site_custom_data (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id BIGINT UNSIGNED NOT NULL,
            field_id BIGINT UNSIGNED NOT NULL,
            field_value TEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_site_field (site_id, field_id),
            KEY idx_field_id (field_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // Messages table
        $sql = "CREATE TABLE {$prefix}ednasurvey_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_user_id BIGINT UNSIGNED NOT NULL,
            sender_id BIGINT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_conversation (conversation_user_id, created_at),
            KEY idx_sender (sender_id),
            KEY idx_unread (conversation_user_id, is_read)
        ) $charset_collate;";
        dbDelta( $sql );

        // Site filters (water/air/container) — one row per filtration/measurement unit
        $sql = "CREATE TABLE {$prefix}ednasurvey_site_filters (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id BIGINT UNSIGNED NOT NULL,
            filter_type VARCHAR(20) NOT NULL DEFAULT '',
            filter_index INT UNSIGNED NOT NULL DEFAULT 1,
            filter_id VARCHAR(255) DEFAULT '',
            filter_value DECIMAL(10,2) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_site_type_index (site_id, filter_type, filter_index),
            KEY idx_site_id (site_id)
        ) $charset_collate;";
        dbDelta( $sql );
    }

    private static function set_default_options(): void {
        if ( false === get_option( 'ednasurvey_settings' ) ) {
            $defaults = array(
                'tile_server_url'       => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'tile_attribution'      => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                'tile_server_url_2'     => '',
                'tile_attribution_2'    => '',
                'map_center_lat'        => 35.6762,
                'map_center_lng'        => 139.6503,
                'map_default_zoom'      => 5,
                'map_input_zoom'        => 18,
                'photo_upload_limit'    => 10,
                'local_language'        => 'ja',
                'collectors_group_mode' => EdnaSurvey_Field_Registry::MODE_ENABLED,
                'env_local_group_mode'  => EdnaSurvey_Field_Registry::MODE_ENABLED,
                'field_config'          => array(), // empty = use hardcoded defaults
                'water_filter_count'    => 2,
                'air_filter_count'      => 0,
                'container_count'       => 0,
            );
            add_option( 'ednasurvey_settings', $defaults );
        }
    }
}
