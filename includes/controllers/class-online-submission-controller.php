<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Online_Submission_Controller {

    public function render( WP_User $target_user ): void {
        $username = $target_user->user_login;
        $settings = get_option( 'ednasurvey_settings', array() );
        $field_model = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_active_fields();

        // Check if copying from existing site (copy_from = internal_sample_id)
        $copy_data = null;
        if ( ! empty( $_GET['copy_from'] ) ) {
            $site_model = new EdnaSurvey_Site_Model();
            $site = $site_model->get_by_internal_id( sanitize_text_field( wp_unslash( $_GET['copy_from'] ) ) );
            if ( $site && (int) $site->user_id === $target_user->ID ) {
                $copy_data = $site;
                $custom_data_model = new EdnaSurvey_Custom_Field_Data_Model();
                $copy_data->custom_fields = $custom_data_model->get_by_site( (int) $site->id );
            }
        }

        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/online-submission.php';
    }
}
