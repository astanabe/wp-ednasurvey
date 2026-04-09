<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Admin_Site_Detail {

    public function render(): void {
        $internal_id = sanitize_text_field( wp_unslash( $_GET['site'] ?? '' ) );
        if ( empty( $internal_id ) ) {
            wp_die( esc_html__( 'Site not found.', 'wp-ednasurvey' ), 404 );
        }

        $site_model = new EdnaSurvey_Site_Model();
        $site       = $site_model->get_by_internal_id( $internal_id );
        if ( ! $site ) {
            wp_die( esc_html__( 'Site not found.', 'wp-ednasurvey' ), 404 );
        }

        $user       = get_user_by( 'id', $site->user_id );
        $photo_model = new EdnaSurvey_Photo_Model();
        $photos      = $photo_model->get_by_site( (int) $site->id );
        $settings    = get_option( 'ednasurvey_settings', array() );

        $custom_data   = array();
        $field_model   = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_active_fields();
        if ( ! empty( $custom_fields ) ) {
            $data_model = new EdnaSurvey_Custom_Field_Data_Model();
            $raw        = $data_model->get_by_site( (int) $site->id );
            $values     = array();
            foreach ( $raw as $cd ) {
                $values[ (int) $cd->field_id ] = $cd->field_value;
            }
            foreach ( $custom_fields as $cf ) {
                if ( isset( $values[ (int) $cf->id ] ) ) {
                    $custom_data[] = array( 'field' => $cf, 'value' => $values[ (int) $cf->id ] );
                }
            }
        }

        include EDNASURVEY_PLUGIN_DIR . 'templates/admin/site-detail.php';
    }
}
