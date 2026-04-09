<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Ajax_Sites extends EdnaSurvey_Ajax_Handler {

    public function register(): void {
        // No admin-ajax actions needed; using REST API instead
    }

    public function register_rest_routes(): void {
        register_rest_route( 'ednasurvey/v1', '/sites', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_sites' ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
            'args'                => array(
                'user_id'   => array( 'type' => 'integer' ),
                'date_from' => array( 'type' => 'string' ),
                'date_to'   => array( 'type' => 'string' ),
                'search'    => array( 'type' => 'string' ),
            ),
        ) );

        register_rest_route( 'ednasurvey/v1', '/sites/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_site' ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
            'args'                => array(
                'id' => array( 'type' => 'integer', 'required' => true ),
            ),
        ) );

        register_rest_route( 'ednasurvey/v1', '/settings/map', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_map_settings' ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ) );

        register_rest_route( 'ednasurvey/v1', '/settings/fields', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_fields' ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ) );
    }

    public function rest_get_sites( WP_REST_Request $request ): WP_REST_Response {
        $current_user = wp_get_current_user();
        $site_model   = new EdnaSurvey_Site_Model();

        $filters = array();

        // Admin can view all sites; subscribers only their own
        if ( current_user_can( 'manage_options' ) ) {
            if ( $request->get_param( 'user_id' ) ) {
                $filters['user_id'] = (int) $request->get_param( 'user_id' );
            }
        } else {
            $filters['user_id'] = $current_user->ID;
        }

        if ( $request->get_param( 'date_from' ) ) {
            $filters['date_from'] = sanitize_text_field( $request->get_param( 'date_from' ) );
        }
        if ( $request->get_param( 'date_to' ) ) {
            $filters['date_to'] = sanitize_text_field( $request->get_param( 'date_to' ) );
        }
        if ( $request->get_param( 'search' ) ) {
            $filters['search'] = sanitize_text_field( $request->get_param( 'search' ) );
        }

        $sites = $site_model->get_all( $filters );

        // Attach user info and photos
        $photo_model       = new EdnaSurvey_Photo_Model();
        $custom_data_model = new EdnaSurvey_Custom_Field_Data_Model();
        $is_admin          = current_user_can( 'manage_options' );

        foreach ( $sites as &$site ) {
            $user             = get_user_by( 'id', $site->user_id );
            $site->user_login = $user ? $user->user_login : '';
            $site->photos     = $photo_model->get_by_site( (int) $site->id );
            $site->custom_data = $custom_data_model->get_by_site( (int) $site->id );

            // Strip submission metadata for non-admin users
            if ( ! $is_admin ) {
                $this->strip_submission_meta( $site );
            }
        }
        unset( $site );

        return new WP_REST_Response( array( 'sites' => $sites ), 200 );
    }

    public function rest_get_site( WP_REST_Request $request ): WP_REST_Response {
        $site_id    = (int) $request->get_param( 'id' );
        $site_model = new EdnaSurvey_Site_Model();
        $site       = $site_model->get_by_id( $site_id );

        if ( ! $site ) {
            return new WP_REST_Response( array( 'message' => 'Not found' ), 404 );
        }

        // Permission check
        $current_user = wp_get_current_user();
        if ( (int) $site->user_id !== $current_user->ID && ! current_user_can( 'manage_options' ) ) {
            return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
        }

        $photo_model       = new EdnaSurvey_Photo_Model();
        $custom_data_model = new EdnaSurvey_Custom_Field_Data_Model();
        $site->photos      = $photo_model->get_by_site( $site_id );
        $site->custom_data = $custom_data_model->get_by_site( $site_id );

        // Strip submission metadata for non-admin users
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->strip_submission_meta( $site );
        }

        return new WP_REST_Response( array( 'site' => $site ), 200 );
    }

    /**
     * Remove submission metadata fields from a site object (for non-admin users).
     */
    private function strip_submission_meta( object $site ): void {
        $meta_fields = array(
            'internal_sample_id', 'submitted_user_login', 'submitted_user_email',
            'submitted_user_name', 'submitted_ip', 'submitted_hostname',
            'submitted_geo', 'submitted_at', 'submitted_user_agent', 'submitted_method',
        );
        foreach ( $meta_fields as $field ) {
            unset( $site->$field );
        }
    }

    public function rest_get_map_settings( WP_REST_Request $request ): WP_REST_Response {
        $settings = get_option( 'ednasurvey_settings', array() );
        return new WP_REST_Response( array(
            'tile_url'     => $settings['tile_server_url'] ?? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution'  => $settings['tile_attribution'] ?? '',
            'center_lat'   => (float) ( $settings['map_center_lat'] ?? 35.6762 ),
            'center_lng'   => (float) ( $settings['map_center_lng'] ?? 139.6503 ),
            'default_zoom' => (int) ( $settings['map_default_zoom'] ?? 5 ),
        ), 200 );
    }

    public function rest_get_fields( WP_REST_Request $request ): WP_REST_Response {
        $settings      = get_option( 'ednasurvey_settings', array() );
        $field_model   = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_active_fields();

        return new WP_REST_Response( array(
            'default_fields_config' => $settings['default_fields_config'] ?? array(),
            'custom_fields'         => $custom_fields,
        ), 200 );
    }
}
