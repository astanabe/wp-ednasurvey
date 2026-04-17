<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_CSV_Service {

    public function generate_csv( array $sites, string $separator = ',' ): string {
        $registry = EdnaSurvey_Field_Registry::get_instance();
        $headers  = $this->build_headers( $registry );

        $photo_model        = new EdnaSurvey_Photo_Model();
        $custom_field_model = new EdnaSurvey_Custom_Field_Model();
        $custom_data_model  = new EdnaSurvey_Custom_Field_Data_Model();
        $custom_fields      = $custom_field_model->get_active_fields();

        // Ordered standard field keys for CSV (matching header order)
        $ordered_keys = array(
            'sample_id', 'survey_date', 'survey_time',
            'latitude', 'longitude',
            'sitename_local', 'sitename_en',
            'correspondence',
            'collector1', 'collector2', 'collector3', 'collector4', 'collector5',
            'watervol1', 'watervol2', 'airvol1', 'airvol2',
            'weight1', 'weight2', 'filter_name',
            'env_broad', 'env_medium',
            'env_local1', 'env_local2', 'env_local3',
            'env_local4', 'env_local5', 'env_local6', 'env_local7',
            'weather', 'wind',
        );

        $output = fopen( 'php://temp', 'r+' );
        fputcsv( $output, $headers, $separator );

        $number = 1;
        foreach ( $sites as $site ) {
            $photos      = $photo_model->get_by_site( (int) $site->id );
            $photo_names = array_map( fn( $p ) => $p->original_filename, $photos );

            $row = array();
            $row[] = $number++;

            // Submission metadata
            $row[] = $site->internal_sample_id ?? '';
            $row[] = $site->submitted_user_login ?? '';
            $row[] = $site->submitted_user_email ?? '';
            $row[] = $site->submitted_user_name ?? '';
            $row[] = $site->submitted_ip ?? '';
            $row[] = $site->submitted_hostname ?? '';
            $row[] = $site->submitted_geo ?? '';
            $row[] = $site->submitted_at ?? '';
            $row[] = $site->submitted_user_agent ?? '';
            $row[] = $site->submitted_method ?? '';

            // Standard fields (active only)
            foreach ( $ordered_keys as $key ) {
                if ( ! $registry->is_active( $key ) ) {
                    continue;
                }
                $row[] = $site->$key ?? '';
            }

            // Custom fields
            $custom_data   = $custom_data_model->get_by_site( (int) $site->id );
            $custom_values = array();
            foreach ( $custom_data as $cd ) {
                $custom_values[ (int) $cd->field_id ] = $cd->field_value;
            }
            foreach ( $custom_fields as $cf ) {
                $row[] = $custom_values[ (int) $cf->id ] ?? '';
            }

            // Notes
            if ( $registry->is_active( 'notes' ) ) {
                $row[] = $site->notes ?? '';
            }

            // Photos (always)
            $row[] = implode( '; ', $photo_names );

            fputcsv( $output, $row, $separator );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        return $csv;
    }

    public function generate_photo_url_list( array $sites ): string {
        $photo_model = new EdnaSurvey_Photo_Model();
        $site_ids    = array_map( fn( $s ) => (int) $s->id, $sites );
        $photos      = $photo_model->get_urls_by_sites( $site_ids );

        $lines = array();
        foreach ( $photos as $photo ) {
            $lines[] = $photo->file_url;
        }

        return implode( "\n", $lines );
    }

    private function build_headers( EdnaSurvey_Field_Registry $registry ): array {
        $headers = array( 'number' );

        // Submission metadata headers
        $headers[] = 'internal_sample_id';
        $headers[] = 'submitted_user_login';
        $headers[] = 'submitted_user_email';
        $headers[] = 'submitted_user_name';
        $headers[] = 'submitted_ip';
        $headers[] = 'submitted_hostname';
        $headers[] = 'submitted_geo';
        $headers[] = 'submitted_at';
        $headers[] = 'submitted_user_agent';
        $headers[] = 'submitted_method';

        // Standard fields (active = mode != disabled)
        $ordered_keys = array(
            'sample_id', 'survey_date', 'survey_time',
            'latitude', 'longitude',
            'sitename_local', 'sitename_en',
            'correspondence',
            'collector1', 'collector2', 'collector3', 'collector4', 'collector5',
            'watervol1', 'watervol2', 'airvol1', 'airvol2',
            'weight1', 'weight2', 'filter_name',
            'env_broad', 'env_medium',
            'env_local1', 'env_local2', 'env_local3',
            'env_local4', 'env_local5', 'env_local6', 'env_local7',
            'weather', 'wind',
        );

        foreach ( $ordered_keys as $key ) {
            if ( $registry->is_active( $key ) ) {
                $headers[] = $key;
            }
        }

        // Custom fields
        $custom_field_model = new EdnaSurvey_Custom_Field_Model();
        $custom_fields      = $custom_field_model->get_active_fields();
        foreach ( $custom_fields as $cf ) {
            $headers[] = 'custom_' . $cf->field_key;
        }

        if ( $registry->is_active( 'notes' ) ) {
            $headers[] = 'notes';
        }

        $headers[] = 'photo_files';

        return $headers;
    }
}
