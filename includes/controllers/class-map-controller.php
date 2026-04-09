<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Map_Controller {

    public function render( WP_User $target_user ): void {
        $username   = $target_user->user_login;
        $site_model = new EdnaSurvey_Site_Model();
        $sites      = $site_model->get_by_user( $target_user->ID );
        $settings   = get_option( 'ednasurvey_settings', array() );

        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/map.php';
    }
}
