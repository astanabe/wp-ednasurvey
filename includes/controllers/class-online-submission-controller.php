<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Online_Submission_Controller {

    public function render( WP_User $target_user ): void {
        $username      = $target_user->user_login;
        $settings      = get_option( 'ednasurvey_settings', array() );
        $registry      = EdnaSurvey_Field_Registry::get_instance();
        $field_model   = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_active_fields();

        // Per-user defaults saved via "Set this as default for next time" checkbox
        $user_defaults = get_user_meta( $target_user->ID, 'ednasurvey_online_defaults', true );
        if ( ! is_array( $user_defaults ) ) {
            $user_defaults = array();
        }

        // Check if copying from existing site (copy_from = internal_sample_id)
        $copy_data = null;
        if ( ! empty( $_GET['copy_from'] ) ) {
            $site_model = new EdnaSurvey_Site_Model();
            $site = $site_model->get_by_internal_id( sanitize_text_field( wp_unslash( $_GET['copy_from'] ) ) );
            if ( $site && (int) $site->user_id === $target_user->ID ) {
                $copy_data = $site;
                $custom_data_model = new EdnaSurvey_Custom_Field_Data_Model();
                $copy_data->custom_fields = $custom_data_model->get_by_site( (int) $site->id );

                // Expose child-table filter values as waterfilter1/watervol1/...
                // properties so the form's $fval() can pre-fill them on resubmit.
                $filter_model = new EdnaSurvey_Site_Filter_Model();
                $types_meta   = EdnaSurvey_Filter_Fields::types();
                foreach ( $filter_model->get_by_site( (int) $site->id ) as $frow ) {
                    $meta = $types_meta[ $frow->filter_type ] ?? null;
                    if ( ! $meta ) {
                        continue;
                    }
                    $idx                                            = (int) $frow->filter_index;
                    $copy_data->{ $meta['id_key_base'] . $idx }     = $frow->filter_id;
                    $copy_data->{ $meta['val_key_base'] . $idx }    = $frow->filter_value;
                }
            }
        }

        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/online-submission.php';
    }
}
