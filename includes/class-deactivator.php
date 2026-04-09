<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Deactivator {

    public static function deactivate(): void {
        flush_rewrite_rules();
        wp_clear_scheduled_hook( 'ednasurvey_cleanup_temp_photos' );
    }
}
