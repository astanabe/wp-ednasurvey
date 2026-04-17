<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Validation_Service {

    /**
     * Validate online submission data using the Field Registry.
     *
     * @param array $data          Form data keyed by field name.
     * @param array $custom_fields Active custom field DB objects.
     * @return array Error messages.
     */
    public function validate_site_data( array $data, array $custom_fields = array() ): array {
        $errors   = array();
        $registry = EdnaSurvey_Field_Registry::get_instance();

        // --- Group A: Always required ---

        // survey_date
        if ( empty( $data['survey_date'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'survey_date' ) );
        } elseif ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['survey_date'] ) ) {
            $errors[] = __( 'Invalid date format.', 'wp-ednasurvey' );
        }

        // survey_time
        if ( empty( $data['survey_time'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'survey_time' ) );
        } elseif ( ! preg_match( '/^\d{2}:\d{2}$/', $data['survey_time'] ) ) {
            $errors[] = __( 'Invalid time format.', 'wp-ednasurvey' );
        }

        // latitude
        if ( ! isset( $data['latitude'] ) || '' === $data['latitude'] ) {
            $errors[] = __( 'Latitude is required. Please set a pin on the map.', 'wp-ednasurvey' );
        } else {
            $lat = (float) $data['latitude'];
            if ( $lat < -90 || $lat > 90 ) {
                $errors[] = __( 'Latitude must be between -90 and 90.', 'wp-ednasurvey' );
            }
        }

        // longitude
        if ( ! isset( $data['longitude'] ) || '' === $data['longitude'] ) {
            $errors[] = __( 'Longitude is required. Please set a pin on the map.', 'wp-ednasurvey' );
        } else {
            $lng = (float) $data['longitude'];
            if ( $lng < -180 || $lng > 180 ) {
                $errors[] = __( 'Longitude must be between -180 and 180.', 'wp-ednasurvey' );
            }
        }

        // sitename_local
        if ( empty( $data['sitename_local'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'sitename_local' ) );
        }

        // sitename_en
        if ( empty( $data['sitename_en'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'sitename_en' ) );
        }

        // --- Group B: Always required ---

        if ( empty( $data['sample_id'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'sample_id' ) );
        }
        if ( empty( $data['correspondence'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'correspondence' ) );
        }
        if ( empty( $data['collector1'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'collector1' ) );
        }

        // env_broad
        if ( empty( $data['env_broad'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'env_broad' ) );
        } elseif ( ! isset( EdnaSurvey_I18n::get_env_broad_choices()[ $data['env_broad'] ] ) ) {
            $errors[] = __( 'Invalid selection for Environment (Broad).', 'wp-ednasurvey' );
        }

        // Force env_local1 for sterile water
        if ( 'sterile water' === ( $data['env_broad'] ?? '' ) ) {
            $data['env_local1'] = 'sterile water environment';
        }

        // env_local1
        if ( empty( $data['env_local1'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'env_local1' ) );
        } else {
            $broad_val  = $data['env_broad'] ?? '';
            $valid_map  = EdnaSurvey_I18n::get_env_local_for_broad();
            $valid_keys = $valid_map[ $broad_val ] ?? array();
            if ( '' !== $data['env_local1'] && ! in_array( $data['env_local1'], $valid_keys, true ) ) {
                $errors[] = sprintf( __( 'Invalid selection for Environment (Local) %d.', 'wp-ednasurvey' ), 1 );
            }
        }

        // --- Group C: Mode-dependent validation ---

        // env_local2-7 (grouped)
        if ( $registry->has_input( 'env_local2' ) ) {
            $broad_val  = $data['env_broad'] ?? '';
            $valid_map  = EdnaSurvey_I18n::get_env_local_for_broad();
            $valid_keys = $valid_map[ $broad_val ] ?? array();
            for ( $i = 2; $i <= 7; $i++ ) {
                $key = 'env_local' . $i;
                $val = $data[ $key ] ?? '';
                if ( $registry->is_required( $key ) && '' === $val ) {
                    $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( $key ) );
                }
                if ( '' !== $val && ! in_array( $val, $valid_keys, true ) ) {
                    $errors[] = sprintf( __( 'Invalid selection for Environment (Local) %d.', 'wp-ednasurvey' ), $i );
                }
            }
        }

        // env_local conflict check (only among visible fields)
        $errors = array_merge( $errors, $this->check_env_local_conflicts( $data, '', $registry ) );

        // collector2-5 (grouped)
        if ( $registry->has_input( 'collector2' ) && $registry->is_required( 'collector2' ) ) {
            for ( $i = 2; $i <= 5; $i++ ) {
                $key = 'collector' . $i;
                if ( empty( $data[ $key ] ) ) {
                    $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( $key ) );
                }
            }
        }

        // weather
        if ( $registry->has_input( 'weather' ) ) {
            if ( $registry->is_required( 'weather' ) && empty( $data['weather'] ) ) {
                $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'weather' ) );
            } elseif ( ! empty( $data['weather'] ) && ! isset( EdnaSurvey_I18n::get_weather_choices()[ $data['weather'] ] ) ) {
                $errors[] = __( 'Invalid selection for Weather.', 'wp-ednasurvey' );
            }
        }

        // wind
        if ( $registry->has_input( 'wind' ) ) {
            if ( $registry->is_required( 'wind' ) && empty( $data['wind'] ) ) {
                $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'wind' ) );
            } elseif ( ! empty( $data['wind'] ) && ! isset( EdnaSurvey_I18n::get_wind_choices()[ $data['wind'] ] ) ) {
                $errors[] = __( 'Invalid selection for Wind.', 'wp-ednasurvey' );
            }
        }

        // Numeric fields: watervol1/2, airvol1/2, weight1/2
        $numeric_fields = array( 'watervol1', 'watervol2', 'airvol1', 'airvol2', 'weight1', 'weight2' );
        foreach ( $numeric_fields as $nf ) {
            if ( ! $registry->has_input( $nf ) ) {
                continue;
            }
            $val = $data[ $nf ] ?? '';
            if ( $registry->is_required( $nf ) && ( ! isset( $data[ $nf ] ) || '' === $data[ $nf ] ) ) {
                $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( $nf ) );
            } elseif ( '' !== $val && ! is_numeric( $val ) ) {
                $errors[] = sprintf( __( '%s must be a number.', 'wp-ednasurvey' ), $registry->get_label( $nf ) );
            }
        }

        // filter_name
        if ( $registry->has_input( 'filter_name' ) && $registry->is_required( 'filter_name' ) && empty( $data['filter_name'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'filter_name' ) );
        }

        // env_medium
        if ( $registry->has_input( 'env_medium' ) && $registry->is_required( 'env_medium' ) && empty( $data['env_medium'] ) ) {
            $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'env_medium' ) );
        }

        // notes — no required check (always optional when visible)

        // --- Custom field validation ---
        foreach ( $custom_fields as $field ) {
            $key   = 'custom_' . $field->id;
            $value = $data[ $key ] ?? '';
            $mode  = $field->field_mode ?? 'enabled';

            // Skip fields without input
            if ( ! in_array( $mode, array( 'required', 'enabled' ), true ) ) {
                continue;
            }

            if ( 'required' === $mode && empty( $value ) ) {
                $label = EdnaSurvey_I18n::get_localized_field( $field->label_local ?? '', $field->label_en ?? '' );
                $errors[] = sprintf( __( '%s is required.', 'wp-ednasurvey' ), $label );
                continue;
            }

            if ( ! empty( $value ) && ! empty( $field->field_options ) ) {
                $options = json_decode( $field->field_options, true );
                if ( 'number' === $field->field_type && ! is_numeric( $value ) ) {
                    $label    = EdnaSurvey_I18n::get_localized_field( $field->label_local ?? '', $field->label_en ?? '' );
                    $errors[] = sprintf( __( '%s must be a number.', 'wp-ednasurvey' ), $label );
                }
                if ( 'select' === $field->field_type && ! empty( $options['choices'] ) ) {
                    if ( ! in_array( $value, $options['choices'], true ) ) {
                        $label    = EdnaSurvey_I18n::get_localized_field( $field->label_local ?? '', $field->label_en ?? '' );
                        $errors[] = sprintf( __( 'Invalid selection for %s.', 'wp-ednasurvey' ), $label );
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validate a single offline Excel row using the Field Registry.
     *
     * @param array $data          Row data keyed by DB column name.
     * @param int   $row_number    1-based row number for messages.
     * @param bool  $has_photo_gps Whether a matched photo provides GPS.
     * @param array $custom_fields Active custom field definitions.
     * @return array Error messages.
     */
    public function validate_offline_row( array $data, int $row_number, bool $has_photo_gps = false, array $custom_fields = array() ): array {
        $errors   = array();
        $registry = EdnaSurvey_Field_Registry::get_instance();
        $prefix   = sprintf( __( 'Row %d', 'wp-ednasurvey' ), $row_number ) . ': ';

        // --- Group A: Always required ---

        // Text required fields
        $group_a_text = array( 'sample_id', 'sitename_local', 'sitename_en', 'correspondence', 'collector1' );
        foreach ( $group_a_text as $key ) {
            if ( empty( $data[ $key ] ) ) {
                $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( $key ) );
            }
        }

        // survey_date
        if ( empty( $data['survey_date'] ) ) {
            $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'survey_date' ) );
        } elseif ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $data['survey_date'] ) ) {
            $errors[] = $prefix . __( 'Invalid date format (YYYY-MM-DD).', 'wp-ednasurvey' );
        }

        // survey_time
        if ( empty( $data['survey_time'] ) ) {
            $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'survey_time' ) );
        } elseif ( ! preg_match( '/^\d{2}:\d{2}$/', (string) $data['survey_time'] ) ) {
            $errors[] = $prefix . __( 'Invalid time format (hh:mm).', 'wp-ednasurvey' );
        }

        // --- Group C: Mode-dependent (only validate fields with input) ---

        // Numeric fields
        $numeric_fields = array( 'watervol1', 'watervol2', 'airvol1', 'airvol2', 'weight1', 'weight2' );
        foreach ( $numeric_fields as $nf ) {
            if ( ! $registry->has_input( $nf ) ) {
                continue;
            }
            $val = $data[ $nf ] ?? '';
            if ( $registry->is_required( $nf ) ) {
                if ( ! isset( $data[ $nf ] ) || '' === $data[ $nf ] ) {
                    $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( $nf ) );
                } elseif ( ! is_numeric( $data[ $nf ] ) ) {
                    $errors[] = $prefix . sprintf( __( '%s must be a number.', 'wp-ednasurvey' ), $registry->get_label( $nf ) );
                }
            } elseif ( '' !== $val && ! is_numeric( $val ) ) {
                $errors[] = $prefix . sprintf( __( '%s must be a number.', 'wp-ednasurvey' ), $registry->get_label( $nf ) );
            }
        }

        // filter_name
        if ( $registry->has_input( 'filter_name' ) && $registry->is_required( 'filter_name' ) && empty( $data['filter_name'] ) ) {
            $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'filter_name' ) );
        }

        // env_medium
        if ( $registry->has_input( 'env_medium' ) && $registry->is_required( 'env_medium' ) && empty( $data['env_medium'] ) ) {
            $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'env_medium' ) );
        }

        // collector2-5
        if ( $registry->has_input( 'collector2' ) && $registry->is_required( 'collector2' ) ) {
            for ( $i = 2; $i <= 5; $i++ ) {
                $key = 'collector' . $i;
                if ( empty( $data[ $key ] ) ) {
                    $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( $key ) );
                }
            }
        }

        // Environment (Broad) — always required (Group B)
        if ( empty( $data['env_broad'] ) ) {
            $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'env_broad' ) );
        } elseif ( ! isset( EdnaSurvey_I18n::get_env_broad_choices()[ (string) $data['env_broad'] ] ) ) {
            $errors[] = $prefix . __( 'Invalid selection for Environment (Broad).', 'wp-ednasurvey' );
        }

        // Force env_local1 for sterile water
        if ( 'sterile water' === ( $data['env_broad'] ?? '' ) ) {
            $data['env_local1'] = 'sterile water environment';
        }

        // env_local1 — always required (Group B)
        if ( empty( $data['env_local1'] ) ) {
            $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'env_local1' ) );
        } else {
            $broad_val  = (string) ( $data['env_broad'] ?? '' );
            $valid_map  = EdnaSurvey_I18n::get_env_local_for_broad();
            $valid_keys = $valid_map[ $broad_val ] ?? array();
            if ( ! in_array( (string) $data['env_local1'], $valid_keys, true ) ) {
                $errors[] = $prefix . sprintf( __( 'Invalid selection for Environment (Local) %d.', 'wp-ednasurvey' ), 1 );
            }
        }

        // env_local2-7 (mode-dependent)
        if ( $registry->has_input( 'env_local2' ) ) {
            $broad_val  = (string) ( $data['env_broad'] ?? '' );
            $valid_map  = EdnaSurvey_I18n::get_env_local_for_broad();
            $valid_keys = $valid_map[ $broad_val ] ?? array();
            for ( $i = 2; $i <= 7; $i++ ) {
                $key = 'env_local' . $i;
                $val = (string) ( $data[ $key ] ?? '' );
                if ( $registry->is_required( $key ) && '' === $val ) {
                    $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( $key ) );
                }
                if ( '' !== $val && ! in_array( $val, $valid_keys, true ) ) {
                    $errors[] = $prefix . sprintf( __( 'Invalid selection for Environment (Local) %d.', 'wp-ednasurvey' ), $i );
                }
            }
        }

        // env_local conflict check
        $errors = array_merge( $errors, $this->check_env_local_conflicts( $data, $prefix ) );

        // Weather (mode-dependent)
        if ( $registry->has_input( 'weather' ) ) {
            if ( $registry->is_required( 'weather' ) && empty( $data['weather'] ) ) {
                $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'weather' ) );
            } elseif ( ! empty( $data['weather'] ) && ! isset( EdnaSurvey_I18n::get_weather_choices()[ (string) $data['weather'] ] ) ) {
                $errors[] = $prefix . __( 'Invalid selection for Weather.', 'wp-ednasurvey' );
            }
        }

        // Wind (mode-dependent)
        if ( $registry->has_input( 'wind' ) ) {
            if ( $registry->is_required( 'wind' ) && empty( $data['wind'] ) ) {
                $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $registry->get_label( 'wind' ) );
            } elseif ( ! empty( $data['wind'] ) && ! isset( EdnaSurvey_I18n::get_wind_choices()[ (string) $data['wind'] ] ) ) {
                $errors[] = $prefix . __( 'Invalid selection for Wind.', 'wp-ednasurvey' );
            }
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

        // Custom fields (mode-dependent)
        foreach ( $custom_fields as $field ) {
            $key   = 'custom_' . $field->field_key;
            $value = $data[ $key ] ?? '';
            $mode  = $field->field_mode ?? 'enabled';

            if ( ! in_array( $mode, array( 'required', 'enabled' ), true ) ) {
                continue;
            }

            if ( 'required' === $mode && empty( $value ) ) {
                $label    = EdnaSurvey_I18n::get_localized_field( $field->label_local ?? '', $field->label_en ?? '' );
                $errors[] = $prefix . sprintf( __( '%s is required.', 'wp-ednasurvey' ), $label );
            }
        }

        return $errors;
    }

    /**
     * Check for conflicting env_local combinations.
     *
     * Only checks env_local fields that are visible (have input).
     *
     * @param array                       $data     Row data with env_local1..env_local7 keys.
     * @param string                      $prefix   Error message prefix (e.g. "Row 3: ").
     * @param EdnaSurvey_Field_Registry|null $registry Registry instance (null = auto-load).
     * @return array Error messages.
     */
    private function check_env_local_conflicts( array $data, string $prefix = '', ?EdnaSurvey_Field_Registry $registry = null ): array {
        $errors  = array();
        $choices = EdnaSurvey_I18n::get_env_local_choices();
        $groups  = EdnaSurvey_I18n::get_env_local_conflict_groups();

        if ( null === $registry ) {
            $registry = EdnaSurvey_Field_Registry::get_instance();
        }

        $selected = array();
        for ( $i = 1; $i <= 7; $i++ ) {
            $key = 'env_local' . $i;
            // env_local1 is always active (Group B); env_local2-7 depend on mode
            if ( $i > 1 && ! $registry->has_input( $key ) ) {
                continue;
            }
            $val = (string) ( $data[ $key ] ?? '' );
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
