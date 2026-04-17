<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Ajax_Admin extends EdnaSurvey_Ajax_Handler {

    public function register(): void {
        // Bulk delete is handled by WP_List_Table form POST in class-admin-all-sites-table.php
        add_action( 'wp_ajax_ednasurvey_import_users', array( $this, 'handle_import_users' ) );
        add_action( 'wp_ajax_ednasurvey_download_data', array( $this, 'handle_download_data' ) );
        add_action( 'wp_ajax_ednasurvey_save_custom_fields', array( $this, 'handle_save_custom_fields' ) );
    }

    public function handle_import_users(): void {
        $this->verify_nonce();
        $this->require_admin();

        if ( empty( $_FILES['user_csv'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'wp-ednasurvey' ) ) );
        }

        $file = $_FILES['user_csv'];
        $ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, array( 'csv', 'tsv', 'txt' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Only CSV/TSV files are accepted.', 'wp-ednasurvey' ) ) );
        }

        $import_service = new EdnaSurvey_User_Import_Service();
        $result         = $import_service->import_from_file( $file['tmp_name'] );

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( array( 'message' => $result['error'] ) );
        }

        wp_send_json_success( array(
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'message' => sprintf(
                /* translators: 1: number created, 2: number skipped */
                __( '%1$d users created, %2$d skipped (already exist).', 'wp-ednasurvey' ),
                $result['created'],
                count( $result['skipped'] )
            ),
        ) );
    }

    public function handle_download_data(): void {
        check_ajax_referer( 'ednasurvey_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'wp-ednasurvey' ), 403 );
        }

        $format  = sanitize_text_field( $_POST['format'] ?? 'csv' );
        $type    = sanitize_text_field( $_POST['type'] ?? 'data' );
        $filters = array();

        if ( ! empty( $_POST['user_id'] ) ) {
            $filters['user_id'] = absint( $_POST['user_id'] );
        }
        if ( ! empty( $_POST['date_from'] ) ) {
            $filters['date_from'] = sanitize_text_field( $_POST['date_from'] );
        }
        if ( ! empty( $_POST['date_to'] ) ) {
            $filters['date_to'] = sanitize_text_field( $_POST['date_to'] );
        }

        $site_model = new EdnaSurvey_Site_Model();
        $sites      = $site_model->get_all( $filters );

        if ( 'photo_urls' === $type ) {
            $csv_service = new EdnaSurvey_CSV_Service();
            $content     = $csv_service->generate_photo_url_list( $sites );
            $filename    = 'photo_urls_' . gmdate( 'Ymd_His' ) . '.txt';

            header( 'Content-Type: text/plain; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        $separator   = 'tsv' === $format ? "\t" : ',';
        $csv_service = new EdnaSurvey_CSV_Service();
        $content     = $csv_service->generate_csv( $sites, $separator );
        $ext         = 'tsv' === $format ? 'tsv' : 'csv';
        $filename    = 'edna_survey_data_' . gmdate( 'Ymd_His' ) . '.' . $ext;

        $content_type = 'tsv' === $format ? 'text/tab-separated-values' : 'text/csv';
        header( 'Content-Type: ' . $content_type . '; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    public function handle_save_custom_fields(): void {
        $this->verify_nonce();
        $this->require_admin();

        $fields_json = wp_unslash( $_POST['fields'] ?? '[]' );
        $fields      = json_decode( $fields_json, true );

        if ( ! is_array( $fields ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data.', 'wp-ednasurvey' ) ) );
        }

        $field_model  = new EdnaSurvey_Custom_Field_Model();
        $existing_ids = array();
        $valid_modes  = EdnaSurvey_Field_Registry::valid_modes();

        foreach ( $fields as $index => $field ) {
            $mode = sanitize_text_field( $field['field_mode'] ?? 'enabled' );
            if ( ! in_array( $mode, $valid_modes, true ) ) {
                $mode = 'enabled';
            }

            $data = array(
                'field_key'         => sanitize_key( $field['field_key'] ?? '' ),
                'label_local'       => sanitize_text_field( $field['label_local'] ?? '' ),
                'label_en'          => sanitize_text_field( $field['label_en'] ?? '' ),
                'description_local' => sanitize_text_field( $field['description_local'] ?? '' ),
                'description_en'    => sanitize_text_field( $field['description_en'] ?? '' ),
                'example_local'     => sanitize_text_field( $field['example_local'] ?? '' ),
                'example_en'        => sanitize_text_field( $field['example_en'] ?? '' ),
                'field_type'        => sanitize_text_field( $field['field_type'] ?? 'text' ),
                'field_options'     => ! empty( $field['field_options'] ) ? wp_json_encode( $field['field_options'] ) : null,
                'field_mode'        => $mode,
                'default_value'     => sanitize_text_field( $field['default_value'] ?? '' ),
                'sort_order'        => $index,
            );

            if ( ! empty( $field['id'] ) ) {
                $field_model->update( (int) $field['id'], $data );
                $existing_ids[] = (int) $field['id'];
            } else {
                $new_id = $field_model->insert( $data );
                if ( $new_id ) {
                    $existing_ids[] = $new_id;
                }
            }
        }

        // Delete removed fields
        $all_fields = $field_model->get_all_fields();
        $custom_data_model = new EdnaSurvey_Custom_Field_Data_Model();
        foreach ( $all_fields as $f ) {
            if ( ! in_array( (int) $f->id, $existing_ids, true ) ) {
                $custom_data_model->delete_by_field( (int) $f->id );
                $field_model->delete( (int) $f->id );
            }
        }

        EdnaSurvey_Field_Registry::reset();

        wp_send_json_success( array( 'message' => __( 'Custom fields saved.', 'wp-ednasurvey' ) ) );
    }
}
