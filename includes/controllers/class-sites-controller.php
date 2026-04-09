<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Sites_Controller {

    public function render( WP_User $target_user ): void {
        $username    = $target_user->user_login;
        $site_model  = new EdnaSurvey_Site_Model();
        $photo_model = new EdnaSurvey_Photo_Model();
        $sites       = $site_model->get_by_user( $target_user->ID );
        $settings    = get_option( 'ednasurvey_settings', array() );

        // Attach photos to each site
        foreach ( $sites as &$site ) {
            $site->photos = $photo_model->get_by_site( (int) $site->id );
        }
        unset( $site );

        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/sites.php';
    }
}
