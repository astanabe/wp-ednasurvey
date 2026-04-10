<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Validation_Service {

    public function validate_site_data( array $data, array $custom_fields = array() ): array {
        $errors = array();
        $settings = get_option( 'ednasurvey_settings', array() );
        $fields_config = $settings['default_fields_config'] ?? array();

        // Date/time validation
        if ( ! empty( $fields_config['survey_datetime'] ) ) {
            if ( empty( $data['survey_date'] ) ) {
                $errors[] = __( 'Survey date is required.', 'wp-ednasurvey' );
            } elseif ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['survey_date'] ) ) {
                $errors[] = __( 'Invalid date format.', 'wp-ednasurvey' );
            }
            if ( empty( $data['survey_time'] ) ) {
                $errors[] = __( 'Survey time is required.', 'wp-ednasurvey' );
            } elseif ( ! preg_match( '/^\d{2}:\d{2}$/', $data['survey_time'] ) ) {
                $errors[] = __( 'Invalid time format.', 'wp-ednasurvey' );
            }
        }

        // Location validation
        if ( ! empty( $fields_config['location'] ) ) {
            if ( ! isset( $data['latitude'] ) || '' === $data['latitude'] ) {
                $errors[] = __( 'Latitude is required. Please set a pin on the map.', 'wp-ednasurvey' );
            } else {
                $lat = (float) $data['latitude'];
                if ( $lat < -90 || $lat > 90 ) {
                    $errors[] = __( 'Latitude must be between -90 and 90.', 'wp-ednasurvey' );
                }
            }
            if ( ! isset( $data['longitude'] ) || '' === $data['longitude'] ) {
                $errors[] = __( 'Longitude is required. Please set a pin on the map.', 'wp-ednasurvey' );
            } else {
                $lng = (float) $data['longitude'];
                if ( $lng < -180 || $lng > 180 ) {
                    $errors[] = __( 'Longitude must be between -180 and 180.', 'wp-ednasurvey' );
                }
            }
        }

        // Site name
        if ( ! empty( $fields_config['site_name'] ) ) {
            if ( empty( $data['sitename_local'] ) && empty( $data['sitename_en'] ) ) {
                $errors[] = __( 'At least one site name (Japanese or English) is required.', 'wp-ednasurvey' );
            }
        }

        // Sample ID
        if ( ! empty( $fields_config['sample_id'] ) && empty( $data['sample_id'] ) ) {
            $errors[] = __( 'Sample ID is required.', 'wp-ednasurvey' );
        }

        // Environment (Broad)
        if ( ! empty( $fields_config['env_broad'] ) ) {
            if ( empty( $data['env_broad'] ) ) {
                $errors[] = __( 'Environment (Broad) is required.', 'wp-ednasurvey' );
            } elseif ( ! isset( EdnaSurvey_I18n::get_env_broad_choices()[ $data['env_broad'] ] ) ) {
                $errors[] = __( 'Invalid selection for Environment (Broad).', 'wp-ednasurvey' );
            }
        }

        // Environment (Local) — requires env_broad
        if ( ! empty( $fields_config['env_broad'] ) ) {
            if ( empty( $data['env_local1'] ) ) {
                $errors[] = __( 'At least one Environment (Local) selection is required.', 'wp-ednasurvey' );
            } else {
                $broad_val   = $data['env_broad'] ?? '';
                $valid_map   = EdnaSurvey_I18n::get_env_local_for_broad();
                $valid_keys  = $valid_map[ $broad_val ] ?? array();
                for ( $i = 1; $i <= 7; $i++ ) {
                    $val = $data[ 'env_local' . $i ] ?? '';
                    if ( '' !== $val && ! in_array( $val, $valid_keys, true ) ) {
                        $errors[] = sprintf( __( 'Invalid selection for Environment (Local) %d.', 'wp-ednasurvey' ), $i );
                    }
                }
            }

            // env_local conflict check
            $errors = array_merge( $errors, $this->check_env_local_conflicts( $data ) );
        }

        // Weather
        if ( ! empty( $fields_config['weather'] ) ) {
            if ( empty( $data['weather'] ) ) {
                $errors[] = __( 'Weather is required.', 'wp-ednasurvey' );
            } elseif ( ! isset( EdnaSurvey_I18n::get_weather_choices()[ $data['weather'] ] ) ) {
                $errors[] = __( 'Invalid selection for Weather.', 'wp-ednasurvey' );
            }
        }

        // Wind
        if ( ! empty( $fields_config['wind'] ) ) {
            if ( empty( $data['wind'] ) ) {
                $errors[] = __( 'Wind is required.', 'wp-ednasurvey' );
            } elseif ( ! isset( EdnaSurvey_I18n::get_wind_choices()[ $data['wind'] ] ) ) {
                $errors[] = __( 'Invalid selection for Wind.', 'wp-ednasurvey' );
            }
        }

        // Water volume
        if ( ! empty( $fields_config['water_volume'] ) ) {
            if ( isset( $data['watervol1'] ) && '' !== $data['watervol1'] && ! is_numeric( $data['watervol1'] ) ) {
                $errors[] = __( 'Filtered water volume 1 must be a number.', 'wp-ednasurvey' );
            }
            if ( isset( $data['watervol2'] ) && '' !== $data['watervol2'] && ! is_numeric( $data['watervol2'] ) ) {
                $errors[] = __( 'Filtered water volume 2 must be a number.', 'wp-ednasurvey' );
            }
        }

        // Custom field validation
        foreach ( $custom_fields as $field ) {
            $key   = 'custom_' . $field->id;
            $value = $data[ $key ] ?? '';

            if ( $field->is_required && empty( $value ) ) {
                $label = EdnaSurvey_I18n::get_localized_field( $field->label_ja, $field->label_en );
                /* translators: %s: field label */
                $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $label );
                continue;
            }

            if ( ! empty( $value ) && ! empty( $field->field_options ) ) {
                $options = json_decode( $field->field_options, true );
                if ( 'number' === $field->field_type ) {
                    if ( ! is_numeric( $value ) ) {
                        $label    = EdnaSurvey_I18n::get_localized_field( $field->label_ja, $field->label_en );
                        /* translators: %s: field label */
                        $errors[] = sprintf( __( '%s must be a number.', 'wp-ednasurvey' ), $label );
                    }
                }
                if ( 'select' === $field->field_type && ! empty( $options['choices'] ) ) {
                    if ( ! in_array( $value, $options['choices'], true ) ) {
                        $label    = EdnaSurvey_I18n::get_localized_field( $field->label_ja, $field->label_en );
                        /* translators: %s: field label */
                        $errors[] = sprintf( __( 'Invalid selection for %s.', 'wp-ednasurvey' ), $label );
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validate a single offline Excel row.
     *
     * @param array $data          Row data keyed by DB column name.
     * @param int   $row_number    1-based row number for messages.
     * @param bool  $has_photo_gps Whether a matched photo provides GPS.
     * @param array $custom_fields Active custom field definitions.
     * @return array Error messages.
     */
    public function validate_offline_row( array $data, int $row_number, bool $has_photo_gps = false, array $custom_fields = array() ): array {
        $errors = array();
        $prefix = sprintf( __( 'Row %d', 'wp-ednasurvey' ), $row_number ) . ': ';

        // Always required
        $required_text = array(
            'sample_id'      => __( 'Sample ID', 'wp-ednasurvey' ),
            'sitename_local' => __( 'Local site name', 'wp-ednasurvey' ),
            'sitename_en'    => __( 'English site name', 'wp-ednasurvey' ),
            'correspondence' => __( 'Representative', 'wp-ednasurvey' ),
            'collector1'     => __( 'Collector 1', 'wp-ednasurvey' ),
        );

        foreach ( $required_text as $key => $label ) {
            if ( empty( $data[ $key ] ) ) {
                /* translators: %s: field name */
                $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $label );
            }
        }

        // survey_date
        if ( empty( $data['survey_date'] ) ) {
            $errors[] = $prefix . __( 'Survey date is required.', 'wp-ednasurvey' );
        } elseif ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $data['survey_date'] ) ) {
            $errors[] = $prefix . __( 'Invalid date format (YYYY-MM-DD).', 'wp-ednasurvey' );
        }

        // survey_time
        if ( empty( $data['survey_time'] ) ) {
            $errors[] = $prefix . __( 'Survey time is required.', 'wp-ednasurvey' );
        } elseif ( ! preg_match( '/^\d{2}:\d{2}$/', (string) $data['survey_time'] ) ) {
            $errors[] = $prefix . __( 'Invalid time format (hh:mm).', 'wp-ednasurvey' );
        }

        // watervol1, watervol2 (0 is allowed, but must be numeric)
        foreach ( array( 'watervol1' => 'Filtered water volume 1', 'watervol2' => 'Filtered water volume 2' ) as $key => $label ) {
            if ( ! isset( $data[ $key ] ) || '' === $data[ $key ] ) {
                $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $label );
            } elseif ( ! is_numeric( $data[ $key ] ) ) {
                $errors[] = $prefix . sprintf( __( '%s must be a number.', 'wp-ednasurvey' ), $label );
            }
        }

        // Environment (Broad)
        if ( empty( $data['env_broad'] ) ) {
            $errors[] = $prefix . __( 'Environment (Broad) is required.', 'wp-ednasurvey' );
        } elseif ( ! isset( EdnaSurvey_I18n::get_env_broad_choices()[ (string) $data['env_broad'] ] ) ) {
            $errors[] = $prefix . __( 'Invalid selection for Environment (Broad).', 'wp-ednasurvey' );
        }

        // Environment (Local)
        if ( empty( $data['env_local1'] ) ) {
            $errors[] = $prefix . __( 'At least one Environment (Local) selection is required.', 'wp-ednasurvey' );
        } else {
            $broad_val  = (string) ( $data['env_broad'] ?? '' );
            $valid_map  = EdnaSurvey_I18n::get_env_local_for_broad();
            $valid_keys = $valid_map[ $broad_val ] ?? array();
            for ( $eli = 1; $eli <= 7; $eli++ ) {
                $val = (string) ( $data[ 'env_local' . $eli ] ?? '' );
                if ( '' !== $val && ! in_array( $val, $valid_keys, true ) ) {
                    $errors[] = $prefix . sprintf( __( 'Invalid selection for Environment (Local) %d.', 'wp-ednasurvey' ), $eli );
                }
            }

            // env_local conflict check
            $errors = array_merge( $errors, $this->check_env_local_conflicts( $data, $prefix ) );
        }

        // Weather
        if ( empty( $data['weather'] ) ) {
            $errors[] = $prefix . __( 'Weather is required.', 'wp-ednasurvey' );
        } elseif ( ! isset( EdnaSurvey_I18n::get_weather_choices()[ (string) $data['weather'] ] ) ) {
            $errors[] = $prefix . __( 'Invalid selection for Weather.', 'wp-ednasurvey' );
        }

        // Wind
        if ( empty( $data['wind'] ) ) {
            $errors[] = $prefix . __( 'Wind is required.', 'wp-ednasurvey' );
        } elseif ( ! isset( EdnaSurvey_I18n::get_wind_choices()[ (string) $data['wind'] ] ) ) {
            $errors[] = $prefix . __( 'Invalid selection for Wind.', 'wp-ednasurvey' );
        }

        // latitude / longitude — required unless photo GPS available
        $lat = $data['latitude'] ?? '';
        $lng = $data['longitude'] ?? '';
        if ( '' === $lat && '' === $lng && ! $has_photo_gps ) {
            $errors[] = $prefix . __( 'Latitude/longitude required (no photo GPS available).', 'wp-ednasurvey' );
        } else {
            if ( '' !== $lat ) {
                $latf = (float) $lat;
                if ( $latf < -90 || $latf > 90 ) {
                    $errors[] = $prefix . __( 'Latitude must be between -90 and 90.', 'wp-ednasurvey' );
                }
            }
            if ( '' !== $lng ) {
                $lngf = (float) $lng;
                if ( $lngf < -180 || $lngf > 180 ) {
                    $errors[] = $prefix . __( 'Longitude must be between -180 and 180.', 'wp-ednasurvey' );
                }
            }
        }

        // Custom fields
        foreach ( $custom_fields as $field ) {
            $key   = 'custom_' . $field->field_key;
            $value = $data[ $key ] ?? '';
            if ( $field->is_required && empty( $value ) ) {
                $label    = EdnaSurvey_I18n::get_localized_field( $field->label_ja, $field->label_en );
                $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $label );
            }
        }

        return $errors;
    }

    /**
     * Check for conflicting env_local combinations.
     *
     * @param array  $data   Row data with env_local1..env_local7 keys.
     * @param string $prefix Error message prefix (e.g. "Row 3: ").
     * @return array Error messages.
     */
    private function check_env_local_conflicts( array $data, string $prefix = '' ): array {
        $errors  = array();
        $choices = EdnaSurvey_I18n::get_env_local_choices();
        $groups  = EdnaSurvey_I18n::get_env_local_conflict_groups();

        $selected = array();
        for ( $i = 1; $i <= 7; $i++ ) {
            $val = (string) ( $data[ 'env_local' . $i ] ?? '' );
            if ( '' !== $val ) {
                $selected[] = $val;
            }
        }

        foreach ( $groups as $group ) {
            $found = array_values( array_intersect( $group, $selected ) );
            $cnt   = count( $found );
            for ( $i = 0; $i < $cnt - 1; $i++ ) {
                for ( $j = $i + 1; $j < $cnt; $j++ ) {
                    $label1 = $choices[ $found[ $i ] ] ?? $found[ $i ];
                    $label2 = $choices[ $found[ $j ] ] ?? $found[ $j ];
                    /* translators: 1: first environment type, 2: second environment type */
                    $errors[] = $prefix . sprintf(
                        __( 'Environment (Local) "%1$s" and "%2$s" cannot be selected together.', 'wp-ednasurvey' ),
                        $label1, $label2
                    );
                }
            }
        }

        return $errors;
    }
}
