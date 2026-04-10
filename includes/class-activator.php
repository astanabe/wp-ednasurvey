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
        self::set_default_options();
        update_option( 'ednasurvey_db_version', EDNASURVEY_DB_VERSION );
        update_option( 'ednasurvey_flush_rewrite', true );
        flush_rewrite_rules();
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
            watervol1 DECIMAL(10,2) DEFAULT NULL,
            watervol2 DECIMAL(10,2) DEFAULT NULL,
            env_broad VARCHAR(255) DEFAULT '',
            env_local1 VARCHAR(255) DEFAULT '',
            env_local2 VARCHAR(255) DEFAULT '',
            env_local3 VARCHAR(255) DEFAULT '',
            env_local4 VARCHAR(255) DEFAULT '',
            env_local5 VARCHAR(255) DEFAULT '',
            env_local6 VARCHAR(255) DEFAULT '',
            env_local7 VARCHAR(255) DEFAULT '',
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
            label_ja VARCHAR(255) NOT NULL,
            label_en VARCHAR(255) NOT NULL,
            field_type VARCHAR(50) NOT NULL DEFAULT 'text',
            field_options TEXT DEFAULT NULL,
            is_required TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
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
    }

    private static function set_default_options(): void {
        if ( false === get_option( 'ednasurvey_settings' ) ) {
            $defaults = array(
                'tile_server_url'      => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'tile_attribution'     => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                'map_center_lat'       => 35.6762,
                'map_center_lng'       => 139.6503,
                'map_default_zoom'     => 5,
                'photo_upload_limit'   => 10,
                'default_fields_config' => array(
                    'survey_datetime' => true,
                    'location'        => true,
                    'site_name'       => true,
                    'correspondence'  => true,
                    'collectors'      => true,
                    'sample_id'       => true,
                    'water_volume'    => true,
                    'env_broad'       => true,
                    'weather'         => true,
                    'wind'            => true,
                    'notes'           => true,
                    'photos'          => true,
                ),
            );
            add_option( 'ednasurvey_settings', $defaults );
        }
    }
}
