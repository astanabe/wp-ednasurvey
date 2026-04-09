<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Admin_Add_Users {

    public function render(): void {
        include EDNASURVEY_PLUGIN_DIR . 'templates/admin/add-users.php';
    }
}
