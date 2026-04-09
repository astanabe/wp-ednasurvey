<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Offline_Submission_Controller {

    public function render( WP_User $target_user ): void {
        $username             = $target_user->user_login;
        $settings             = get_option( 'ednasurvey_settings', array() );
        $photo_limit          = (int) ( $settings['photo_upload_limit'] ?? 10 );
        $photo_time_threshold = (int) ( $settings['photo_time_threshold'] ?? 30 );
        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/offline-submission.php';
    }
}
