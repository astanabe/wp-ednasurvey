<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Admin_Sites_Map {

    public function render(): void {
        $site_model = new EdnaSurvey_Site_Model();
        $sites      = $site_model->get_all();
        $settings   = get_option( 'ednasurvey_settings', array() );

        foreach ( $sites as &$site ) {
            $user             = get_user_by( 'id', $site->user_id );
            $site->user_login = $user ? $user->user_login : '';
        }
        unset( $site );

        include EDNASURVEY_PLUGIN_DIR . 'templates/admin/sites-map.php';
    }
}
