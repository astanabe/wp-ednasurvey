<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Site_Detail_Controller {

    public function render( WP_User $target_user ): void {
        $username = $target_user->user_login;
        $slug     = EdnaSurvey_Router::$current_site_slug;

        if ( empty( $slug ) ) {
            wp_die( esc_html__( 'Page not found.', 'wp-ednasurvey' ), 404 );
        }

        $site_model = new EdnaSurvey_Site_Model();
        $site       = $site_model->get_by_internal_id( $slug );

        if ( ! $site ) {
            wp_die( esc_html__( 'Site not found.', 'wp-ednasurvey' ), 404 );
        }

        // Verify ownership
        if ( (int) $site->user_id !== $target_user->ID && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'wp-ednasurvey' ), 403 );
        }

        $settings      = get_option( 'ednasurvey_settings', array() );
        $photo_model   = new EdnaSurvey_Photo_Model();
        $photos        = $photo_model->get_by_site( (int) $site->id );
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
                    $custom_data[] = array(
                        'label' => $cf,
                        'value' => $values[ (int) $cf->id ],
                    );
                }
            }
        }

        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/site-detail.php';
    }
}
