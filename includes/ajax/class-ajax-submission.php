<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Ajax_Submission extends EdnaSurvey_Ajax_Handler {

    public function register(): void {
        add_action( 'wp_ajax_ednasurvey_submit_site', array( $this, 'handle_submit_site' ) );
        add_action( 'wp_ajax_ednasurvey_upload_temp_photos', array( $this, 'handle_upload_temp_photos' ) );
        add_action( 'wp_ajax_ednasurvey_delete_temp_photo', array( $this, 'handle_delete_temp_photo' ) );
        add_action( 'wp_ajax_ednasurvey_analyze_offline_excel', array( $this, 'handle_analyze_offline_excel' ) );
        add_action( 'wp_ajax_ednasurvey_confirm_offline', array( $this, 'handle_confirm_offline' ) );
    }

    public function handle_submit_site(): void {
        $this->verify_nonce();
        $user = $this->require_login();

        $validation = new EdnaSurvey_Validation_Service();
        $field_model = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_active_fields();

        $raw_post = wp_unslash( $_POST );
        $data     = array();
        foreach ( $raw_post as $key => $value ) {
            if ( is_string( $value ) ) {
                $data[ $key ] = sanitize_text_field( $value );
            }
        }
        $errors = $validation->validate_site_data( $data, $custom_fields );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array( 'messages' => $errors ) );
        }

        // Check photo limit
        $settings    = get_option( 'ednasurvey_settings', array() );
        $photo_limit = (int) ( $settings['photo_upload_limit'] ?? 10 );
        if ( ! empty( $_FILES['photos'] ) && is_array( $_FILES['photos']['name'] ) ) {
            if ( count( $_FILES['photos']['name'] ) > $photo_limit ) {
                wp_send_json_error( array(
                    'messages' => array(
                        /* translators: %d: maximum number of photos */
                        sprintf( __( 'Maximum %d photos allowed.', 'wp-ednasurvey' ), $photo_limit ),
                    ),
                ) );
            }
        }

        // Build submission metadata
        $meta = $this->build_submission_meta( $user, 'online' );

        // Insert site
        $site_model = new EdnaSurvey_Site_Model();
        $site_data  = array(
            'user_id'              => $user->ID,
            'survey_date'          => $data['survey_date'] ?? null,
            'survey_time'          => $data['survey_time'] ?? null,
            'latitude'             => ! empty( $data['latitude'] ) ? round( (float) $data['latitude'], 6 ) : null,
            'longitude'            => ! empty( $data['longitude'] ) ? round( (float) $data['longitude'], 6 ) : null,
            'sitename_local'         => $data['sitename_local'] ?? '',
            'sitename_en'         => $data['sitename_en'] ?? '',
            'correspondence'       => $data['correspondence'] ?? '',
            'collector1'           => $data['collector1'] ?? '',
            'collector2'           => $data['collector2'] ?? '',
            'collector3'           => $data['collector3'] ?? '',
            'collector4'           => $data['collector4'] ?? '',
            'collector5'           => $data['collector5'] ?? '',
            'sample_id'            => $data['sample_id'] ?? '',
            'watervol1'          => ! empty( $data['watervol1'] ) ? (float) $data['watervol1'] : null,
            'watervol2'          => ! empty( $data['watervol2'] ) ? (float) $data['watervol2'] : null,
            'env_broad'            => $data['env_broad'] ?? '',
            'env_local1'           => $data['env_local1'] ?? '',
            'env_local2'           => $data['env_local2'] ?? '',
            'env_local3'           => $data['env_local3'] ?? '',
            'env_local4'           => $data['env_local4'] ?? '',
            'env_local5'           => $data['env_local5'] ?? '',
            'env_local6'           => $data['env_local6'] ?? '',
            'env_local7'           => $data['env_local7'] ?? '',
            'weather'              => $data['weather'] ?? '',
            'wind'                 => $data['wind'] ?? '',
            'notes'                => $data['notes'] ?? '',
            'internal_sample_id'   => $meta['internal_sample_id'],
            'submitted_user_login' => $meta['submitted_user_login'],
            'submitted_user_email' => $meta['submitted_user_email'],
            'submitted_user_name'  => $meta['submitted_user_name'],
            'submitted_ip'         => $meta['submitted_ip'],
            'submitted_hostname'   => $meta['submitted_hostname'],
            'submitted_geo'        => $meta['submitted_geo'],
            'submitted_at'         => $meta['submitted_at'],
            'submitted_user_agent' => $meta['submitted_user_agent'],
            'submitted_method'     => $meta['submitted_method'],
        );

        $site_id = $site_model->insert( $site_data );
        if ( ! $site_id ) {
            wp_send_json_error( array( 'messages' => array( __( 'Failed to save site data.', 'wp-ednasurvey' ) ) ) );
        }

        // Save custom field values
        $custom_data_model = new EdnaSurvey_Custom_Field_Data_Model();
        foreach ( $custom_fields as $cf ) {
            $key   = 'custom_' . $cf->id;
            $value = $data[ $key ] ?? '';
            if ( '' !== $value ) {
                $custom_data_model->save( $site_id, (int) $cf->id, $value );
            }
        }

        // Process photo uploads
        $photo_errors = array();
        if ( ! empty( $_FILES['photos'] ) && ! empty( $_FILES['photos']['name'][0] ) ) {
            $photo_service = new EdnaSurvey_Photo_Service();
            $result        = $photo_service->process_uploads( $_FILES['photos'], $user->ID, $site_id );
            $photo_errors  = $result['errors'];

            $photo_model = new EdnaSurvey_Photo_Model();
            foreach ( $result['saved'] as $photo ) {
                $photo_model->insert( array(
                    'site_id'           => $site_id,
                    'user_id'           => $user->ID,
                    'original_filename' => $photo['original_filename'],
                    'stored_filename'   => $photo['stored_filename'],
                    'file_path'         => $photo['file_path'],
                    'file_url'          => $photo['file_url'],
                    'mime_type'         => $photo['mime_type'],
                    'exif_latitude'     => $photo['exif_latitude'],
                    'exif_longitude'    => $photo['exif_longitude'],
                ) );
            }
        }

        $response = array(
            'site_id'      => $site_id,
            'redirect_url' => home_url( '/' . $user->user_login . '/' ),
        );

        if ( ! empty( $photo_errors ) ) {
            $response['photo_warnings'] = $photo_errors;
        }

        wp_send_json_success( $response );
    }

    // ── Offline submission endpoints (Step 1-3) ──────────────────────

    public function handle_upload_temp_photos(): void {
        $this->verify_nonce();
        $user = $this->require_login();

        $session_id = sanitize_file_name( $_POST['session_id'] ?? '' );
        $num_sites  = max( 1, (int) ( $_POST['num_sites'] ?? 1 ) );

        if ( empty( $session_id ) ) {
            $session_id = wp_generate_uuid4();
        }

        $settings    = get_option( 'ednasurvey_settings', array() );
        $photo_limit = (int) ( $settings['photo_upload_limit'] ?? 10 );
        $max_total   = $num_sites * $photo_limit;

        $photo_service = new EdnaSurvey_Photo_Service();
        $existing      = $photo_service->list_temp_photos( $session_id );

        if ( empty( $_FILES['photos'] ) ) {
            wp_send_json_error( array( 'messages' => array( __( 'No photos selected.', 'wp-ednasurvey' ) ) ) );
        }

        $file_count = is_array( $_FILES['photos']['name'] ) ? count( $_FILES['photos']['name'] ) : 0;
        if ( count( $existing ) + $file_count > $max_total ) {
            wp_send_json_error( array(
                'messages' => array( sprintf(
                    /* translators: %d: max photo count */
                    __( 'Maximum %d photos allowed for this upload session.', 'wp-ednasurvey' ),
                    $max_total
                ) ),
            ) );
        }

        $uploaded = array();
        $errors   = array();

        for ( $i = 0; $i < $file_count; $i++ ) {
            $single = array(
                'name'     => $_FILES['photos']['name'][ $i ],
                'type'     => $_FILES['photos']['type'][ $i ],
                'tmp_name' => $_FILES['photos']['tmp_name'][ $i ],
                'error'    => $_FILES['photos']['error'][ $i ],
                'size'     => $_FILES['photos']['size'][ $i ],
            );
            try {
                $uploaded[] = $photo_service->process_temp_upload( $single, $session_id );
            } catch ( \Throwable $e ) {
                $errors[] = $e->getMessage();
            }
        }

        if ( ! empty( $errors ) && empty( $uploaded ) ) {
            wp_send_json_error( array( 'messages' => $errors ) );
        }

        $response = array(
            'session_id' => $session_id,
            'photos'     => $uploaded,
        );
        if ( ! empty( $errors ) ) {
            $response['warnings'] = $errors;
        }

        wp_send_json_success( $response );
    }

    public function handle_delete_temp_photo(): void {
        $this->verify_nonce();
        $this->require_login();

        $session_id      = sanitize_file_name( $_POST['session_id'] ?? '' );
        $stored_filename = sanitize_file_name( $_POST['stored_filename'] ?? '' );

        if ( empty( $session_id ) || empty( $stored_filename ) ) {
            wp_send_json_error( array( 'messages' => array( __( 'Invalid request.', 'wp-ednasurvey' ) ) ) );
        }

        $photo_service = new EdnaSurvey_Photo_Service();
        if ( $photo_service->delete_temp_photo( $session_id, $stored_filename ) ) {
            wp_send_json_success( array( 'deleted' => $stored_filename ) );
        } else {
            wp_send_json_error( array( 'messages' => array( __( 'Photo not found.', 'wp-ednasurvey' ) ) ) );
        }
    }

    public function handle_analyze_offline_excel(): void {
        $this->verify_nonce();
        $this->require_login();

        $session_id = sanitize_file_name( $_POST['session_id'] ?? '' );

        if ( empty( $_FILES['excel_file'] ) ) {
            wp_send_json_error( array( 'messages' => array( __( 'No Excel file uploaded.', 'wp-ednasurvey' ) ) ) );
        }

        $file = $_FILES['excel_file'];
        $ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( 'xlsx' !== $ext ) {
            wp_send_json_error( array( 'messages' => array( __( 'Only .xlsx files are accepted.', 'wp-ednasurvey' ) ) ) );
        }

        $excel_service = new EdnaSurvey_Excel_Service();
        try {
            $rows = $excel_service->parse_upload( $file['tmp_name'] );
        } catch ( \Throwable $e ) {
            wp_send_json_error( array( 'messages' => array( __( 'Failed to parse Excel file.', 'wp-ednasurvey' ) ) ) );
        }

        if ( empty( $rows ) ) {
            wp_send_json_error( array( 'messages' => array( __( 'No data found in the Excel file.', 'wp-ednasurvey' ) ) ) );
        }

        $photo_service = new EdnaSurvey_Photo_Service();
        $temp_photos   = $session_id ? $photo_service->list_temp_photos( $session_id ) : array();
        $settings      = get_option( 'ednasurvey_settings', array() );
        $threshold     = (int) ( $settings['photo_time_threshold'] ?? 30 );

        $result = $excel_service->analyze_with_photos( $rows, $temp_photos, $threshold );

        if ( ! empty( $result['errors'] ) ) {
            wp_send_json_error( array( 'messages' => $result['errors'] ) );
        }

        wp_send_json_success( array(
            'sites'    => $result['sites'],
            'warnings' => $result['warnings'],
        ) );
    }

    public function handle_confirm_offline(): void {
        $this->verify_nonce();
        $user = $this->require_login();

        $session_id = sanitize_file_name( $_POST['session_id'] ?? '' );
        $sites_json = wp_unslash( $_POST['sites'] ?? '[]' );
        $sites_data = json_decode( $sites_json, true );

        if ( ! is_array( $sites_data ) || empty( $sites_data ) ) {
            wp_send_json_error( array( 'messages' => array( __( 'No site data received.', 'wp-ednasurvey' ) ) ) );
        }

        $site_model        = new EdnaSurvey_Site_Model();
        $custom_data_model = new EdnaSurvey_Custom_Field_Data_Model();
        $field_model       = new EdnaSurvey_Custom_Field_Model();
        $custom_fields     = $field_model->get_active_fields();
        $photo_service     = new EdnaSurvey_Photo_Service();
        $photo_model       = new EdnaSurvey_Photo_Model();

        $inserted_ids = array();

        foreach ( $sites_data as $os ) {
            $raw  = $os['raw_data'] ?? array();
            $meta = $this->build_submission_meta( $user, 'offline' );

            $site_record = array(
                'user_id'              => $user->ID,
                'survey_date'          => $raw['survey_date'] ?? null,
                'survey_time'          => $raw['survey_time'] ?? null,
                'latitude'             => isset( $os['latitude'] ) ? round( (float) $os['latitude'], 6 ) : null,
                'longitude'            => isset( $os['longitude'] ) ? round( (float) $os['longitude'], 6 ) : null,
                'sitename_local'       => $raw['sitename_local'] ?? '',
                'sitename_en'          => $raw['sitename_en'] ?? '',
                'correspondence'       => $raw['correspondence'] ?? '',
                'collector1'           => $raw['collector1'] ?? '',
                'collector2'           => $raw['collector2'] ?? '',
                'collector3'           => $raw['collector3'] ?? '',
                'collector4'           => $raw['collector4'] ?? '',
                'collector5'           => $raw['collector5'] ?? '',
                'sample_id'            => $raw['sample_id'] ?? '',
                'watervol1'            => $raw['watervol1'] ?? null,
                'watervol2'            => $raw['watervol2'] ?? null,
                'env_broad'            => $raw['env_broad'] ?? '',
                'env_local1'           => $raw['env_local1'] ?? '',
                'env_local2'           => $raw['env_local2'] ?? '',
                'env_local3'           => $raw['env_local3'] ?? '',
                'env_local4'           => $raw['env_local4'] ?? '',
                'env_local5'           => $raw['env_local5'] ?? '',
                'env_local6'           => $raw['env_local6'] ?? '',
                'env_local7'           => $raw['env_local7'] ?? '',
                'weather'              => $raw['weather'] ?? '',
                'wind'                 => $raw['wind'] ?? '',
                'notes'                => $raw['notes'] ?? '',
                'internal_sample_id'   => $meta['internal_sample_id'],
                'submitted_user_login' => $meta['submitted_user_login'],
                'submitted_user_email' => $meta['submitted_user_email'],
                'submitted_user_name'  => $meta['submitted_user_name'],
                'submitted_ip'         => $meta['submitted_ip'],
                'submitted_hostname'   => $meta['submitted_hostname'],
                'submitted_geo'        => $meta['submitted_geo'],
                'submitted_at'         => $meta['submitted_at'],
                'submitted_user_agent' => $meta['submitted_user_agent'],
                'submitted_method'     => $meta['submitted_method'],
            );

            $site_id = $site_model->insert( $site_record );
            if ( ! $site_id ) {
                continue;
            }
            $inserted_ids[] = $site_id;

            // Custom fields
            foreach ( $custom_fields as $cf ) {
                $value = $raw[ 'custom_' . $cf->field_key ] ?? '';
                if ( '' !== $value ) {
                    $custom_data_model->save( $site_id, (int) $cf->id, (string) $value );
                }
            }

            // Move matched photos from temp to permanent
            $matched = $os['matched_photos'] ?? array();
            if ( ! empty( $matched ) && ! empty( $session_id ) ) {
                $filenames = array_map( fn( $p ) => $p['stored_filename'], $matched );
                $result    = $photo_service->move_temp_to_permanent( $session_id, $filenames, $user->ID, $site_id );

                foreach ( $result['moved'] as $photo ) {
                    $photo_model->insert( array(
                        'site_id'           => $site_id,
                        'user_id'           => $user->ID,
                        'original_filename' => $photo['original_filename'],
                        'stored_filename'   => $photo['stored_filename'],
                        'file_path'         => $photo['file_path'],
                        'file_url'          => $photo['file_url'],
                        'mime_type'         => $photo['mime_type'],
                        'exif_latitude'     => $photo['exif_latitude'],
                        'exif_longitude'    => $photo['exif_longitude'],
                    ) );
                }
            }
        }

        // Clean up temp directory
        if ( ! empty( $session_id ) ) {
            $photo_service->delete_temp_dir( $session_id );
        }

        wp_send_json_success( array(
            'site_ids'     => $inserted_ids,
            'redirect_url' => home_url( '/' . $user->user_login . '/' ),
        ) );
    }

    /**
     * Build submission metadata array.
     *
     * @param WP_User $user   The logged-in user.
     * @param string  $method 'online' or 'offline'.
     * @return array
     */
    private function build_submission_meta( WP_User $user, string $method ): array {
        $now = current_time( 'mysql' );
        $ip  = $this->get_client_ip();

        return array(
            'internal_sample_id'   => $this->generate_internal_sample_id( $user->user_login, $ip, $now ),
            'submitted_user_login' => $user->user_login,
            'submitted_user_email' => $user->user_email,
            'submitted_user_name'  => trim( $user->first_name . ' ' . $user->last_name ),
            'submitted_ip'         => $ip,
            'submitted_hostname'   => $this->resolve_hostname( $ip ),
            'submitted_geo'        => $this->resolve_geo( $ip ),
            'submitted_at'         => $now,
            'submitted_user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
                ? mb_substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 500 )
                : '',
            'submitted_method'     => $method,
        );
    }

    /**
     * Generate a unique internal sample ID.
     *
     * Format: {user_login}-{ip_padded}-{YYYYMMDDhhmmss}-{random_hex}
     * IPv4 example: tanaka-192168001005-20260409123456-a1b2c3d4e5f6a7b8
     */
    private function generate_internal_sample_id( string $user_login, string $ip, string $datetime ): string {
        // Timestamp portion
        $ts = gmdate( 'YmdHis', strtotime( $datetime ) );

        // IP portion: each octet zero-padded to 3 digits, dots removed
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            $octets   = explode( '.', $ip );
            $ip_part  = implode( '', array_map( fn( $o ) => str_pad( $o, 3, '0', STR_PAD_LEFT ), $octets ) );
        } else {
            // IPv6 or other: strip non-alphanumeric, take last 12 chars
            $ip_part = substr( preg_replace( '/[^0-9a-fA-F]/', '', $ip ), -12 );
            $ip_part = str_pad( $ip_part, 12, '0', STR_PAD_LEFT );
        }

        // Random hex portion (8 bytes = 16 hex chars)
        $rand = bin2hex( random_bytes( 8 ) );

        return $user_login . '-' . $ip_part . '-' . $ts . '-' . $rand;
    }

    /**
     * Get the client's IP address.
     */
    private function get_client_ip(): string {
        // Check common proxy headers (trusted environments only)
        $headers = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
        );

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                // X-Forwarded-For may contain multiple IPs; take the first
                $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
                $ip  = trim( $ips[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
    }

    /**
     * Reverse-DNS lookup for the given IP.
     */
    private function resolve_hostname( string $ip ): string {
        if ( empty( $ip ) || '0.0.0.0' === $ip ) {
            return '';
        }

        $host = @gethostbyaddr( $ip );

        // gethostbyaddr returns the IP itself on failure
        if ( false === $host || $host === $ip ) {
            return '';
        }

        return sanitize_text_field( $host );
    }

    /**
     * Resolve geographic location from IP address.
     * Uses ip-api.com (free, no key required, non-commercial).
     */
    private function resolve_geo( string $ip ): string {
        if ( empty( $ip ) || '0.0.0.0' === $ip || '127.0.0.1' === $ip || '::1' === $ip ) {
            return '';
        }

        // Try PHP geoip extension first
        if ( function_exists( 'geoip_country_name_by_name' ) ) {
            $country = @geoip_country_name_by_name( $ip );
            if ( $country ) {
                $region = '';
                if ( function_exists( 'geoip_region_by_name' ) ) {
                    $r = @geoip_region_by_name( $ip );
                    if ( $r && ! empty( $r['region'] ) ) {
                        $region = $r['region'];
                    }
                }
                return sanitize_text_field( $region ? $country . ', ' . $region : $country );
            }
        }

        // Fallback: ip-api.com with short timeout
        $response = wp_remote_get(
            'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,country,regionName,city',
            array( 'timeout' => 2 )
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body ) || 'success' !== ( $body['status'] ?? '' ) ) {
            return '';
        }

        $parts = array_filter( array(
            $body['country'] ?? '',
            $body['regionName'] ?? '',
            $body['city'] ?? '',
        ) );

        return sanitize_text_field( implode( ', ', $parts ) );
    }
}
