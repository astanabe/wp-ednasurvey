<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_User_Import_Service {

    /**
     * Import users from CSV/TSV content.
     *
     * @param string $file_path Path to uploaded file.
     * @return array{created: int, skipped: array}
     */
    public function import_from_file( string $file_path ): array {
        $created = 0;
        $skipped = array();

        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            return array( 'created' => 0, 'skipped' => array(), 'error' => __( 'Could not open file.', 'wp-ednasurvey' ) );
        }

        // Detect delimiter
        $first_line = fgets( $handle );
        rewind( $handle );
        $delimiter = ( substr_count( $first_line, "\t" ) > substr_count( $first_line, ',' ) ) ? "\t" : ',';

        // Read header
        $header = fgetcsv( $handle, 0, $delimiter );
        if ( ! $header ) {
            fclose( $handle );
            return array( 'created' => 0, 'skipped' => array(), 'error' => __( 'Empty file.', 'wp-ednasurvey' ) );
        }

        // Normalize header
        $header = array_map( 'trim', $header );
        $header = array_map( 'strtolower', $header );

        $email_idx     = array_search( 'email', $header, true );
        $firstname_idx = array_search( 'firstname', $header, true );
        $lastname_idx  = array_search( 'lastname', $header, true );

        if ( false === $email_idx ) {
            fclose( $handle );
            return array( 'created' => 0, 'skipped' => array(), 'error' => __( 'Missing "email" column.', 'wp-ednasurvey' ) );
        }

        while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
            $email = sanitize_email( trim( $row[ $email_idx ] ?? '' ) );
            if ( empty( $email ) || ! is_email( $email ) ) {
                continue;
            }

            // Check if user already exists
            if ( email_exists( $email ) || username_exists( $email ) ) {
                $skipped[] = $email;
                continue;
            }

            $firstname = sanitize_text_field( trim( $row[ $firstname_idx ] ?? '' ) );
            $lastname  = sanitize_text_field( trim( $row[ $lastname_idx ] ?? '' ) );

            $user_data = array(
                'user_login'   => $email,
                'user_email'   => $email,
                'user_pass'    => wp_generate_password( 16, true, true ),
                'first_name'   => $firstname,
                'last_name'    => $lastname,
                'display_name' => trim( $firstname . ' ' . $lastname ),
                'role'         => 'subscriber',
            );

            // Prevent sending welcome email
            add_filter( 'wp_send_new_user_notification_to_user', '__return_false' );
            add_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );

            $user_id = wp_insert_user( $user_data );

            remove_filter( 'wp_send_new_user_notification_to_user', '__return_false' );
            remove_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );

            if ( ! is_wp_error( $user_id ) ) {
                $created++;
            }
        }

        fclose( $handle );

        return array( 'created' => $created, 'skipped' => $skipped );
    }
}
