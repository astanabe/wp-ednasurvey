<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class EdnaSurvey_Ajax_Handler {

    abstract public function register(): void;

    protected function verify_nonce( string $nonce_field = 'nonce' ): void {
        if ( ! check_ajax_referer( 'ednasurvey_nonce', $nonce_field, false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-ednasurvey' ) ), 403 );
        }
    }

    protected function require_login(): WP_User {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'wp-ednasurvey' ) ), 401 );
        }
        return wp_get_current_user();
    }

    protected function require_admin(): WP_User {
        $user = $this->require_login();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'wp-ednasurvey' ) ), 403 );
        }
        return $user;
    }
}
