<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Offline_Template_Controller {

    public function render( WP_User $target_user ): void {
        $username = $target_user->user_login;

        // Check if download was requested
        if ( ! empty( $_GET['download'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ednasurvey_download_template' ) ) {
            $excel_service = new EdnaSurvey_Excel_Service();
            $excel_service->generate_and_download_template( $username );
            exit;
        }

        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/offline-template.php';
    }
}
