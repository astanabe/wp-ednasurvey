<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_CSV_Service {

    public function generate_csv( array $sites, string $separator = ',' ): string {
        $settings = get_option( 'ednasurvey_settings', array() );
        $fields_config = $settings['default_fields_config'] ?? array();

        $headers = $this->build_headers( $fields_config );
        $photo_model = new EdnaSurvey_Photo_Model();
        $custom_field_model = new EdnaSurvey_Custom_Field_Model();
        $custom_data_model  = new EdnaSurvey_Custom_Field_Data_Model();
        $custom_fields      = $custom_field_model->get_active_fields();

        $output = fopen( 'php://temp', 'r+' );
        fputcsv( $output, $headers, $separator );

        $number = 1;
        foreach ( $sites as $site ) {
            $photos     = $photo_model->get_by_site( (int) $site->id );
            $photo_names = array_map( fn( $p ) => $p->original_filename, $photos );

            $row = array();
            $row[] = $number++;

            // Submission metadata (admin-only columns)
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

            if ( ! empty( $fields_config['sample_id'] ) ) {
                $row[] = $site->sample_id ?? '';
            }
            if ( ! empty( $fields_config['survey_datetime'] ) ) {
                $row[] = $site->survey_date ?? '';
                $row[] = $site->survey_time ?? '';
            }
            if ( ! empty( $fields_config['location'] ) ) {
                $row[] = $site->latitude ?? '';
                $row[] = $site->longitude ?? '';
            }
            if ( ! empty( $fields_config['site_name'] ) ) {
                $row[] = $site->sitename_local ?? '';
                $row[] = $site->sitename_en ?? '';
            }
            if ( ! empty( $fields_config['correspondence'] ) ) {
                $row[] = $site->correspondence ?? '';
            }
            if ( ! empty( $fields_config['collectors'] ) ) {
                $row[] = $site->collector1 ?? '';
                $row[] = $site->collector2 ?? '';
                $row[] = $site->collector3 ?? '';
                $row[] = $site->collector4 ?? '';
                $row[] = $site->collector5 ?? '';
            }
            if ( ! empty( $fields_config['water_volume'] ) ) {
                $row[] = $site->watervol1 ?? '';
                $row[] = $site->watervol2 ?? '';
            }

            // Custom fields
            $custom_data = $custom_data_model->get_by_site( (int) $site->id );
            $custom_values = array();
            foreach ( $custom_data as $cd ) {
                $custom_values[ (int) $cd->field_id ] = $cd->field_value;
            }
            foreach ( $custom_fields as $cf ) {
                $row[] = $custom_values[ (int) $cf->id ] ?? '';
            }

            if ( ! empty( $fields_config['notes'] ) ) {
                $row[] = $site->notes ?? '';
            }
            if ( ! empty( $fields_config['photos'] ) ) {
                $row[] = implode( '; ', $photo_names );
            }

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

    private function build_headers( array $fields_config ): array {
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

        if ( ! empty( $fields_config['sample_id'] ) ) {
            $headers[] = 'sample_id';
        }
        if ( ! empty( $fields_config['survey_datetime'] ) ) {
            $headers[] = 'survey_date';
            $headers[] = 'survey_time';
        }
        if ( ! empty( $fields_config['location'] ) ) {
            $headers[] = 'latitude';
            $headers[] = 'longitude';
        }
        if ( ! empty( $fields_config['site_name'] ) ) {
            $headers[] = 'sitename_local';
            $headers[] = 'sitename_en';
        }
        if ( ! empty( $fields_config['correspondence'] ) ) {
            $headers[] = 'correspondence';
        }
        if ( ! empty( $fields_config['collectors'] ) ) {
            $headers[] = 'collector1';
            $headers[] = 'collector2';
            $headers[] = 'collector3';
            $headers[] = 'collector4';
            $headers[] = 'collector5';
        }
        if ( ! empty( $fields_config['water_volume'] ) ) {
            $headers[] = 'watervol1';
            $headers[] = 'watervol2';
        }

        // Custom fields
        $custom_field_model = new EdnaSurvey_Custom_Field_Model();
        $custom_fields      = $custom_field_model->get_active_fields();
        foreach ( $custom_fields as $cf ) {
            $headers[] = 'custom_' . $cf->field_key;
        }

        if ( ! empty( $fields_config['notes'] ) ) {
            $headers[] = 'notes';
        }
        if ( ! empty( $fields_config['photos'] ) ) {
            $headers[] = 'photo_files';
        }

        return $headers;
    }
}
