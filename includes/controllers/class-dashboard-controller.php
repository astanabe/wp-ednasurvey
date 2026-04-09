<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Dashboard_Controller {

    public function render( WP_User $target_user ): void {
        $username = $target_user->user_login;
        $settings = get_option( 'ednasurvey_settings', array() );
        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/dashboard.php';
    }
}
