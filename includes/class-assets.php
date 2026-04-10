<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Assets {

    private const LEAFLET_VERSION    = '1.9.4';
    private const DATATABLES_VERSION = '2.1.8';

    public function enqueue_frontend(): void {
        $page = EdnaSurvey_Router::$current_page;
        if ( empty( $page ) ) {
            return;
        }

        // Dashicons for nav cards
        wp_enqueue_style( 'dashicons' );

        // Common frontend styles
        wp_enqueue_style(
            'ednasurvey-frontend',
            EDNASURVEY_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EDNASURVEY_VERSION
        );

        $settings  = get_option( 'ednasurvey_settings', array() );
        $map_pages = array( 'onlinesubmission', 'offlinesubmission', 'map' );

        // Leaflet (on map pages)
        if ( in_array( $page, $map_pages, true ) ) {
            $this->enqueue_leaflet();
            wp_localize_script( 'leaflet', 'ednasurveyMap', array(
                'tileUrl'      => $settings['tile_server_url'] ?? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution'  => $settings['tile_attribution'] ?? '',
                'centerLat'    => (float) ( $settings['map_center_lat'] ?? 35.6762 ),
                'centerLng'    => (float) ( $settings['map_center_lng'] ?? 139.6503 ),
                'defaultZoom'  => (int) ( $settings['map_default_zoom'] ?? 5 ),
            ) );
        }

        // DataTables (on table pages)
        if ( in_array( $page, array( 'sites' ), true ) ) {
            $this->enqueue_datatables();
        }

        // Page-specific JS
        $js_map = array(
            'onlinesubmission'  => 'online-submission',
            'offlinesubmission' => 'offline-submission',
            'sites'             => 'sites-table',
            'map'               => 'map',
            'chat'              => 'chat',
        );

        if ( isset( $js_map[ $page ] ) ) {
            $handle = 'ednasurvey-' . $js_map[ $page ];
            $deps   = array( 'jquery' );
            if ( in_array( $page, $map_pages, true ) ) {
                $deps[] = 'leaflet';
            }
            if ( 'sites' === $page ) {
                $deps[] = 'datatables';
            }

            wp_enqueue_script(
                $handle,
                EDNASURVEY_PLUGIN_URL . 'assets/js/frontend/' . $js_map[ $page ] . '.js',
                $deps,
                EDNASURVEY_VERSION,
                true
            );

            wp_localize_script( $handle, 'ednasurveyAjax', array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'restUrl'   => rest_url( 'ednasurvey/v1/' ),
                'nonce'     => wp_create_nonce( 'ednasurvey_nonce' ),
                'restNonce' => wp_create_nonce( 'wp_rest' ),
                'i18n'      => array(
                    'submitting'       => __( 'Submitting...', 'wp-ednasurvey' ),
                    'submitSuccess'    => __( 'Your survey data has been submitted successfully!', 'wp-ednasurvey' ),
                    'backToDashboard'  => __( 'Back to Dashboard', 'wp-ednasurvey' ),
                    'errorOccurred'    => __( 'An error occurred.', 'wp-ednasurvey' ),
                    'serverError'      => __( 'Server error. Please try again.', 'wp-ednasurvey' ),
                    'analyzing'        => __( 'Analyzing...', 'wp-ednasurvey' ),
                    'photoLimitMsg'    => __( 'Upload up to {max} photos ({limit} per site).', 'wp-ednasurvey' ),
                    'tooManyPhotos'    => __( 'Maximum {max} photos allowed. You can add {remaining} more.', 'wp-ednasurvey' ),
                    'noPhotos'         => __( 'No photos', 'wp-ednasurvey' ),
                    'noLocation'       => __( 'No location - click map to set', 'wp-ednasurvey' ),
                    'missingLocations' => __( 'Some sites are missing location. Please set all locations on the map.', 'wp-ednasurvey' ),
                    'gpsFromExcel'     => __( 'GPS from Excel', 'wp-ednasurvey' ),
                    'gpsFromPhoto'     => __( 'GPS from photo EXIF', 'wp-ednasurvey' ),
                    'envLocalConflict'  => __( 'Environment (Local) "{label1}" and "{label2}" cannot be selected together.', 'wp-ednasurvey' ),
                    'selectPlaceholder' => __( '-- Select --', 'wp-ednasurvey' ),
                    'confirmReview'    => __( 'Please review your submission', 'wp-ednasurvey' ),
                    'confirmSubmit'    => __( 'Submit', 'wp-ednasurvey' ),
                    'confirmBack'      => __( 'Back to Edit', 'wp-ednasurvey' ),
                ),
            ) );
        }

        // Chat page specific styles
        if ( 'chat' === $page ) {
            wp_enqueue_style(
                'ednasurvey-chat',
                EDNASURVEY_PLUGIN_URL . 'assets/css/chat.css',
                array(),
                EDNASURVEY_VERSION
            );
        }
    }

    public function enqueue_admin( string $hook ): void {
        // Only on our plugin pages
        if ( ! str_contains( $hook, 'edna-survey' ) && ! str_contains( $hook, 'ednasurvey' ) ) {
            return;
        }

        wp_enqueue_style(
            'ednasurvey-admin',
            EDNASURVEY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EDNASURVEY_VERSION
        );

        // Leaflet for Sites Map page
        if ( str_contains( $hook, 'sites-map' ) ) {
            $this->enqueue_leaflet();
            $settings = get_option( 'ednasurvey_settings', array() );
            wp_localize_script( 'ednasurvey-leaflet-init', 'ednasurveyMap', array(
                'tileUrl'     => $settings['tile_server_url'] ?? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution' => $settings['tile_attribution'] ?? '',
                'centerLat'   => (float) ( $settings['map_center_lat'] ?? 35.6762 ),
                'centerLng'   => (float) ( $settings['map_center_lng'] ?? 139.6503 ),
                'defaultZoom' => (int) ( $settings['map_default_zoom'] ?? 5 ),
            ) );
        }

        // Page-specific admin JS
        $page_js = array(
            'settings'      => 'settings',
            'all-sites'     => 'all-sites',
            'sites-map'     => 'sites-map',
            'messages'      => 'messages',
            'add-users'     => 'add-users',
        );

        foreach ( $page_js as $page_slug => $js_file ) {
            if ( str_contains( $hook, $page_slug ) ) {
                wp_enqueue_script(
                    'ednasurvey-admin-' . $js_file,
                    EDNASURVEY_PLUGIN_URL . 'assets/js/admin/' . $js_file . '.js',
                    array( 'jquery' ),
                    EDNASURVEY_VERSION,
                    true
                );

                wp_localize_script( 'ednasurvey-admin-' . $js_file, 'ednasurveyAdmin', array(
                    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                    'restUrl'   => rest_url( 'ednasurvey/v1/' ),
                    'nonce'     => wp_create_nonce( 'ednasurvey_nonce' ),
                    'restNonce' => wp_create_nonce( 'wp_rest' ),
                ) );
                break;
            }
        }
    }

    private function enqueue_leaflet(): void {
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@' . self::LEAFLET_VERSION . '/dist/leaflet.css',
            array(),
            self::LEAFLET_VERSION
        );
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@' . self::LEAFLET_VERSION . '/dist/leaflet.js',
            array(),
            self::LEAFLET_VERSION,
            true
        );
        wp_enqueue_style(
            'ednasurvey-leaflet-custom',
            EDNASURVEY_PLUGIN_URL . 'assets/css/leaflet-custom.css',
            array( 'leaflet' ),
            EDNASURVEY_VERSION
        );
    }

    private function enqueue_datatables(): void {
        wp_enqueue_style(
            'datatables',
            'https://cdn.datatables.net/' . self::DATATABLES_VERSION . '/css/dataTables.dataTables.min.css',
            array(),
            self::DATATABLES_VERSION
        );
        wp_enqueue_script(
            'datatables',
            'https://cdn.datatables.net/' . self::DATATABLES_VERSION . '/js/dataTables.min.js',
            array( 'jquery' ),
            self::DATATABLES_VERSION,
            true
        );
    }
}
