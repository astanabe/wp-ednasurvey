<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Admin_Download_Data {

    public function render(): void {
        $subscribers = get_users( array( 'role' => 'subscriber', 'orderby' => 'display_name' ) );
        include EDNASURVEY_PLUGIN_DIR . 'templates/admin/download-data.php';
    }
}
