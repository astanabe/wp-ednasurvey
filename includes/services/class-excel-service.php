<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\NamedRange;

class EdnaSurvey_Excel_Service {

    /**
     * Column definitions for the template.
     *
     * Each entry:
     *   key       – DB column name (row 1 label)
     *   ja        – Japanese label (row 2)
     *   en        – English label (row 2)
     *   hint_ja   – Japanese input note (row 3)
     *   hint_en   – English input note (row 3)
     *   example_ja – Japanese example (row 4)
     *   example_en – English example (row 4)
     *   type      – validation type
     */
    private function get_columns(): array {
        $settings      = get_option( 'ednasurvey_settings', array() );
        $fields_config = $settings['default_fields_config'] ?? array();

        $columns = array();

        if ( ! empty( $fields_config['sample_id'] ) ) {
            $columns[] = array(
                'key' => 'sample_id', 'label' => __( 'Sample ID', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'Assigned string', 'wp-ednasurvey' ),
                'example' => 'EWJ0001',
                'type' => 'text',
            );
        }
        if ( ! empty( $fields_config['survey_datetime'] ) ) {
            $columns[] = array(
                'key' => 'survey_date', 'label' => __( 'Survey Date', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'YYYY-MM-DD format', 'wp-ednasurvey' ),
                'example' => '2026-04-09',
                'type' => 'date',
            );
            $columns[] = array(
                'key' => 'survey_time', 'label' => __( 'Survey Time', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'hh:mm (24-hour)', 'wp-ednasurvey' ),
                'example' => '14:30',
                'type' => 'time',
            );
        }
        if ( ! empty( $fields_config['location'] ) ) {
            $columns[] = array(
                'key' => 'latitude', 'label' => __( 'Latitude', 'wp-ednasurvey' ),
                'required_label' => __( 'Omit if photo has GPS', 'wp-ednasurvey' ),
                'hint' => __( 'Decimal (6 places). No DMS. South is negative', 'wp-ednasurvey' ),
                'example' => '38.268215',
                'type' => 'latitude',
            );
            $columns[] = array(
                'key' => 'longitude', 'label' => __( 'Longitude', 'wp-ednasurvey' ),
                'required_label' => __( 'Omit if photo has GPS', 'wp-ednasurvey' ),
                'hint' => __( 'Decimal (6 places). No DMS. West is negative', 'wp-ednasurvey' ),
                'example' => '140.981483',
                'type' => 'longitude',
            );
        }
        if ( ! empty( $fields_config['site_name'] ) ) {
            $columns[] = array(
                'key' => 'sitename_local', 'label' => __( 'Local Language Site Name', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'Local language string', 'wp-ednasurvey' ),
                'example' => '仙台湾荒浜',
                'type' => 'text',
            );
            $columns[] = array(
                'key' => 'sitename_en', 'label' => __( 'Site Name (English)', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'Alphabet characters', 'wp-ednasurvey' ),
                'example' => 'Arahama coast, Sendai bay',
                'type' => 'text',
            );
        }
        if ( ! empty( $fields_config['correspondence'] ) ) {
            $columns[] = array(
                'key' => 'correspondence', 'label' => __( 'Representative', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'Text', 'wp-ednasurvey' ),
                'example' => __( 'Taro Tohoku', 'wp-ednasurvey' ),
                'type' => 'text',
            );
        }
        if ( ! empty( $fields_config['collectors'] ) ) {
            $example_names = array(
                __( 'Taro Tohoku', 'wp-ednasurvey' ),
                __( 'Jiro Tohoku', 'wp-ednasurvey' ),
                __( 'Saburo Tohoku', 'wp-ednasurvey' ),
                '', '',
            );
            for ( $i = 1; $i <= 5; $i++ ) {
                $columns[] = array(
                    'key' => 'collector' . $i,
                    'label' => sprintf( __( 'Collector %d', 'wp-ednasurvey' ), $i ),
                    'required_label' => 1 === $i ? __( 'Required', 'wp-ednasurvey' ) : __( 'Optional', 'wp-ednasurvey' ),
                    'hint' => __( 'Text', 'wp-ednasurvey' ),
                    'example' => $example_names[ $i - 1 ],
                    'type' => 'text',
                );
            }
        }
        if ( ! empty( $fields_config['water_volume'] ) ) {
            $columns[] = array(
                'key' => 'watervol1', 'label' => __( 'Filtered Water Vol. 1 (mL)', 'wp-ednasurvey' ),
                'required_label' => __( 'Required (0 allowed)', 'wp-ednasurvey' ),
                'hint' => __( 'Integer (mL)', 'wp-ednasurvey' ),
                'example' => '500',
                'type' => 'integer',
            );
            $columns[] = array(
                'key' => 'watervol2', 'label' => __( 'Filtered Water Vol. 2 (mL)', 'wp-ednasurvey' ),
                'required_label' => __( 'Required (0 allowed)', 'wp-ednasurvey' ),
                'hint' => __( 'Integer (mL)', 'wp-ednasurvey' ),
                'example' => '500',
                'type' => 'integer',
            );
        }

        if ( ! empty( $fields_config['env_broad'] ) ) {
            $env_broad_choices = EdnaSurvey_I18n::get_env_broad_choices();
            $columns[] = array(
                'key' => 'env_broad', 'label' => __( 'Environment (Broad)', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'Select from list. "estuarine": excludes areas outside river mouth. "mangrove": estuarine mangroves = mangrove. "large river": whether a sightseeing boat can operate (not rapids boats). "saline lake": excludes brackish/lagoons. "sterile water": blanks/negative controls', 'wp-ednasurvey' ),
                'example' => __( 'marine', 'wp-ednasurvey' ),
                'type' => 'select',
                'options' => array( 'choices' => array_values( $env_broad_choices ) ),
            );
            for ( $eli = 1; $eli <= 7; $eli++ ) {
                $columns[] = array(
                    'key' => 'env_local' . $eli,
                    'label' => sprintf( __( 'Env. (Local) %d', 'wp-ednasurvey' ), $eli ),
                    'required_label' => 1 === $eli ? __( 'Required', 'wp-ednasurvey' ) : __( 'Optional', 'wp-ednasurvey' ),
                    'hint' => __( 'Select from list based on Env. (Broad)', 'wp-ednasurvey' ),
                    'example' => 1 === $eli ? __( 'bay', 'wp-ednasurvey' ) : '',
                    'type' => 'env_local',
                );
            }
        }
        if ( ! empty( $fields_config['weather'] ) ) {
            $weather_choices = EdnaSurvey_I18n::get_weather_choices();
            $columns[] = array(
                'key' => 'weather', 'label' => __( 'Weather', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'Select from list', 'wp-ednasurvey' ),
                'example' => __( 'sunny', 'wp-ednasurvey' ),
                'type' => 'select',
                'options' => array( 'choices' => array_values( $weather_choices ) ),
            );
        }
        if ( ! empty( $fields_config['wind'] ) ) {
            $wind_choices = EdnaSurvey_I18n::get_wind_choices();
            $columns[] = array(
                'key' => 'wind', 'label' => __( 'Wind', 'wp-ednasurvey' ),
                'required_label' => __( 'Required', 'wp-ednasurvey' ),
                'hint' => __( 'Select from list. Criterion: whether a syringe or filter holder used for filtration is continuously moved by the wind', 'wp-ednasurvey' ),
                'example' => __( 'not windy', 'wp-ednasurvey' ),
                'type' => 'select',
                'options' => array( 'choices' => array_values( $wind_choices ) ),
            );
        }

        // Dynamic custom fields
        $field_model   = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_active_fields();
        foreach ( $custom_fields as $cf ) {
            $columns[] = array(
                'key'            => 'custom_' . $cf->field_key,
                'label'          => EdnaSurvey_I18n::get_localized_field( $cf->label_ja, $cf->label_en ),
                'required_label' => $cf->is_required ? __( 'Required', 'wp-ednasurvey' ) : __( 'Optional', 'wp-ednasurvey' ),
                'hint'           => $cf->field_type,
                'example'        => '',
                'type'           => $cf->field_type,
                'options'        => $cf->field_options ? json_decode( $cf->field_options, true ) : null,
            );
        }

        if ( ! empty( $fields_config['notes'] ) ) {
            $columns[] = array(
                'key' => 'notes', 'label' => __( 'Notes', 'wp-ednasurvey' ),
                'required_label' => __( 'Optional', 'wp-ednasurvey' ),
                'hint' => __( 'Free text', 'wp-ednasurvey' ),
                'example' => '',
                'type' => 'text',
            );
        }
        if ( ! empty( $fields_config['photos'] ) ) {
            $columns[] = array(
                'key' => 'photo_files', 'label' => __( 'Photo Filenames', 'wp-ednasurvey' ),
                'required_label' => __( 'Omit if no photos taken', 'wp-ednasurvey' ),
                'hint' => __( 'Comma-separated for multiple', 'wp-ednasurvey' ),
                'example' => 'IMG_001.jpg,IMG_002.jpg',
                'type' => 'text',
            );
        }

        return $columns;
    }

    /**
     * Generate and send the template Excel file as a download.
     */
    public function generate_and_download_template( string $user_login = 'template' ): void {
        $spreadsheet = $this->create_template();
        $writer      = new Xlsx( $spreadsheet );

        $filename = 'ednasurvey_' . sanitize_file_name( $user_login ) . '.xlsx';

        // Clear all output buffers (WordPress may have multiple layers)
        while ( ob_get_level() ) {
            ob_end_clean();
        }

        // Force HTTP 200 — WordPress may have set 404 before the router intercepted
        status_header( 200 );
        header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
        header( 'Content-Disposition: attachment;filename="' . $filename . '"' );
        header( 'Cache-Control: max-age=0' );
        header( 'Pragma: public' );

        $writer->save( 'php://output' );
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * Create the template spreadsheet.
     *
     * Row 1: DB column names — locked, dark blue background
     * Row 2: Language-specific labels — locked, light yellow, bold
     * Row 3: Required/Optional — locked, light yellow
     * Row 4: Input format instructions — locked, light yellow
     * Row 5: Example data — locked, light yellow
     * Row 6+: Data entry area — unlocked, white, with validation
     *
     * Rows 1-2 frozen on scroll.
     * Rows 1-5 protected from editing.
     * Workbook structure protected (no sheet add/delete).
     */
    public function create_template(): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle( 'Sheet1' );

        // Set default font
        /* translators: Default font for Excel templates. Use a CJK font for Japanese (e.g. 游ゴシック). */
        $font_name = _x( 'Calibri', 'excel-font', 'wp-ednasurvey' );
        if ( 'Calibri' !== $font_name ) {
            $spreadsheet->getDefaultStyle()->getFont()->setName( $font_name )->setSize( 11 );
        }
        $columns = $this->get_columns();
        $colCount = count( $columns );
        $lastCol  = Coordinate::stringFromColumnIndex( $colCount );

        // -- Write header rows --------------------------------------------------

        foreach ( $columns as $idx => $col ) {
            $c = $idx + 1;

            // Row 1: DB column key
            $sheet->getCell( [ $c, 1 ] )->setValue( $col['key'] );

            // Row 2: Language label
            $sheet->getCell( [ $c, 2 ] )->setValue( $col['label'] );

            // Row 3: Required / Optional
            $sheet->getCell( [ $c, 3 ] )->setValue( $col['required_label'] ?? '' );

            // Row 4: Input format instructions
            $sheet->getCell( [ $c, 4 ] )->setValue( $col['hint'] );

            // Row 5: Example data
            $sheet->getCell( [ $c, 5 ] )->setValue( $col['example'] ?? '' );

            $sheet->getColumnDimension( Coordinate::stringFromColumnIndex( $c ) )->setAutoSize( true );
        }

        // -- Cap column widths at 50 after auto-size calculation ----------------
        // AutoSize is deferred, so calculate it now, then enforce the cap.

        $sheet->calculateColumnWidths();
        foreach ( $columns as $idx => $col ) {
            $dim = $sheet->getColumnDimension( Coordinate::stringFromColumnIndex( $idx + 1 ) );
            if ( $dim->getWidth() > 50 ) {
                $dim->setAutoSize( false );
                $dim->setWidth( 50 );
            }
        }

        // -- Style Row 1: DB key row — dark blue bg, white bold text -----------

        $row1Range = 'A1:' . $lastCol . '1';
        $sheet->getStyle( $row1Range )->applyFromArray( array(
            'font' => array(
                'bold'  => true,
                'color' => array( 'rgb' => 'FFFFFF' ),
                'size'  => 10,
            ),
            'fill' => array(
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => array( 'rgb' => '2C3E50' ),
            ),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ),
        ) );

        // -- Style Rows 2-5: light yellow bg -----------------------------------

        $row25Range = 'A2:' . $lastCol . '5';
        $sheet->getStyle( $row25Range )->applyFromArray( array(
            'fill' => array(
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => array( 'rgb' => 'FFF9E6' ),
            ),
        ) );

        // Row 2: bold
        $row2Range = 'A2:' . $lastCol . '2';
        $sheet->getStyle( $row2Range )->getFont()->setBold( true );

        // Row 4: wrap text (hints may be long and columns are width-capped)
        $row4Range = 'A4:' . $lastCol . '4';
        $sheet->getStyle( $row4Range )->getAlignment()->setWrapText( true );

        // Bottom border on row 5 to visually separate header from data
        $row5Range = 'A5:' . $lastCol . '5';
        $sheet->getStyle( $row5Range )->getBorders()->getBottom()->setBorderStyle( Border::BORDER_MEDIUM );

        // -- Freeze pane: rows 1-2 stay visible on scroll ----------------------

        $sheet->freezePane( 'A3' );

        // -- Cell protection: lock rows 1-5, unlock rows 6+ --------------------

        for ( $row = 1; $row <= 5; $row++ ) {
            $range = 'A' . $row . ':' . $lastCol . $row;
            $sheet->getStyle( $range )->getProtection()->setLocked( Protection::PROTECTION_PROTECTED );
        }

        $dataStartRow = 6;
        $dataEndRow   = 205;
        $dataRange = 'A' . $dataStartRow . ':' . $lastCol . $dataEndRow;
        $sheet->getStyle( $dataRange )->getProtection()->setLocked( Protection::PROTECTION_UNPROTECTED );

        // Enable sheet protection
        $sheet->getProtection()->setSheet( true );
        $sheet->getProtection()->setSort( false );
        $sheet->getProtection()->setAutoFilter( false );
        $sheet->getProtection()->setInsertRows( false );

        // -- Data validation for rows 6-205 ------------------------------------
        // 'select' and 'env_local' types are handled below via Lists sheet.

        foreach ( $columns as $idx => $col ) {
            $colType = $col['type'] ?? 'text';
            if ( 'select' === $colType || 'env_local' === $colType ) {
                continue; // handled via Lists sheet below
            }
            $colLetter = Coordinate::stringFromColumnIndex( $idx + 1 );
            for ( $row = $dataStartRow; $row <= $dataEndRow; $row++ ) {
                $cellRef = $colLetter . $row;
                $this->apply_validation( $sheet, $cellRef, $col );
            }
        }

        // -- Dropdown validations -------------------------------------------------
        // All dropdown choices are stored on a hidden "Lists" sheet and
        // referenced via named ranges. This avoids the 255-byte inline limit.
        // env_local uses INDIRECT to cascade from the env_broad selection.

        $hasDropdowns = false;
        foreach ( $columns as $col ) {
            if ( in_array( $col['type'] ?? '', array( 'select', 'env_local' ), true ) ) {
                $hasDropdowns = true;
                break;
            }
        }

        if ( $hasDropdowns ) {
            $listsSheet = $spreadsheet->createSheet();
            $listsSheet->setTitle( 'Lists' );
            $listCol = 1;

            $validationErrorTitle = __( 'Input error', 'wp-ednasurvey' );
            $validationErrorMsg   = __( 'Please select from the list', 'wp-ednasurvey' );

            // --- 1. Simple select columns (env_broad, weather, wind, custom selects) ---

            foreach ( $columns as $idx => $col ) {
                if ( 'select' !== ( $col['type'] ?? '' ) ) {
                    continue;
                }
                $choices = $col['options']['choices'] ?? array();
                if ( empty( $choices ) ) {
                    continue;
                }

                // Write choices to Lists sheet
                foreach ( $choices as $ri => $choice ) {
                    $listsSheet->getCell( [ $listCol, $ri + 1 ] )->setValue( $choice );
                }

                // Define named range
                $listColLetter = Coordinate::stringFromColumnIndex( $listCol );
                $rangeName     = '_list_' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $col['key'] );
                $spreadsheet->addNamedRange(
                    new NamedRange( $rangeName, $listsSheet, '$' . $listColLetter . '$1:$' . $listColLetter . '$' . count( $choices ) )
                );

                // Apply validation to data cells
                $dataColLetter = Coordinate::stringFromColumnIndex( $idx + 1 );
                for ( $row = $dataStartRow; $row <= $dataEndRow; $row++ ) {
                    $validation = $sheet->getCell( $dataColLetter . $row )->getDataValidation();
                    $validation->setType( DataValidation::TYPE_LIST );
                    $validation->setErrorStyle( DataValidation::STYLE_INFORMATION );
                    $validation->setAllowBlank( false );
                    $validation->setShowInputMessage( true );
                    $validation->setShowErrorMessage( true );
                    $validation->setShowDropDown( true );
                    $validation->setErrorTitle( $validationErrorTitle );
                    $validation->setError( $validationErrorMsg );
                    $validation->setFormula1( $rangeName );
                }

                $listCol++;
            }

            // --- 2. env_local dependent dropdowns (INDIRECT) ---

            $env_broad_col_idx = null;
            foreach ( $columns as $idx => $col ) {
                if ( 'env_broad' === $col['key'] ) {
                    $env_broad_col_idx = $idx;
                    break;
                }
            }

            if ( null !== $env_broad_col_idx ) {
                $env_local_choices = EdnaSurvey_I18n::get_env_local_choices();
                $env_local_map     = EdnaSurvey_I18n::get_env_local_for_broad();
                $env_broad_choices = EdnaSurvey_I18n::get_env_broad_choices();

                foreach ( $env_local_map as $broad_key => $local_keys ) {
                    $broad_label = $env_broad_choices[ $broad_key ];
                    $range_name  = str_replace( ' ', '_', $broad_label );

                    $listRow = 1;
                    foreach ( $local_keys as $lk ) {
                        if ( isset( $env_local_choices[ $lk ] ) ) {
                            $listsSheet->getCell( [ $listCol, $listRow ] )
                                ->setValue( $env_local_choices[ $lk ] );
                            $listRow++;
                        }
                    }

                    $colLetter = Coordinate::stringFromColumnIndex( $listCol );
                    $lastRow   = max( 1, $listRow - 1 );
                    $spreadsheet->addNamedRange(
                        new NamedRange( $range_name, $listsSheet, '$' . $colLetter . '$1:$' . $colLetter . '$' . $lastRow )
                    );

                    $listCol++;
                }

                // Apply INDIRECT validation to env_local columns
                $envBroadColLetter = Coordinate::stringFromColumnIndex( $env_broad_col_idx + 1 );

                // Localized labels for sterile water auto-fill in env_local1
                $sterileWaterBroadLabel = $env_broad_choices['sterile water'] ?? '';
                $sterileWaterLocalLabel = $env_local_choices['sterile water environment'] ?? '';

                foreach ( $columns as $idx => $col ) {
                    if ( 'env_local' !== ( $col['type'] ?? '' ) ) {
                        continue;
                    }
                    $colLetter = Coordinate::stringFromColumnIndex( $idx + 1 );
                    for ( $row = $dataStartRow; $row <= $dataEndRow; $row++ ) {
                        $validation = $sheet->getCell( $colLetter . $row )->getDataValidation();
                        $validation->setType( DataValidation::TYPE_LIST );
                        $validation->setErrorStyle( DataValidation::STYLE_INFORMATION );
                        $validation->setAllowBlank( true );
                        $validation->setShowInputMessage( true );
                        $validation->setShowErrorMessage( true );
                        $validation->setShowDropDown( true );
                        $validation->setErrorTitle( $validationErrorTitle );
                        $validation->setError( $validationErrorMsg );
                        $validation->setFormula1( 'INDIRECT(SUBSTITUTE($' . $envBroadColLetter . $row . '," ","_"))' );
                    }

                    // Auto-fill env_local1: when env_broad is sterile water,
                    // set env_local1 to the sterile water environment label.
                    if ( 'env_local1' === $col['key'] && '' !== $sterileWaterBroadLabel && '' !== $sterileWaterLocalLabel ) {
                        for ( $row = $dataStartRow; $row <= $dataEndRow; $row++ ) {
                            $sheet->getCell( $colLetter . $row )->setValue(
                                '=IF($' . $envBroadColLetter . $row . '="' . $sterileWaterBroadLabel . '","' . $sterileWaterLocalLabel . '","")'
                            );
                        }
                    }
                }
            }

            // Hide the Lists sheet and re-activate data sheet
            $listsSheet->setSheetState( \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN );
            $spreadsheet->setActiveSheetIndex( 0 );
        }

        // -- Workbook protection: prevent adding/deleting sheets ---------------

        $security = $spreadsheet->getSecurity();
        $security->setLockStructure( true );

        return $spreadsheet;
    }

    /**
     * Apply cell validation and formatting based on column type.
     */
    private function apply_validation( $sheet, string $cellRef, array $col ): void {
        $type = $col['type'] ?? 'text';

        switch ( $type ) {
            case 'date':
                // Force YYYY-MM-DD text format (prevent Excel auto-conversion to serial)
                $sheet->getStyle( $cellRef )->getNumberFormat()->setFormatCode( '@' );
                $validation = $sheet->getCell( $cellRef )->getDataValidation();
                $validation->setType( DataValidation::TYPE_CUSTOM );
                $validation->setErrorStyle( DataValidation::STYLE_STOP );
                $validation->setAllowBlank( true );
                $validation->setShowErrorMessage( true );
                $validation->setErrorTitle( __( 'Date format error', 'wp-ednasurvey' ) );
                $validation->setError( __( 'Enter in YYYY-MM-DD format', 'wp-ednasurvey' ) );
                // Custom formula: must be 10 chars and parseable as date
                $validation->setFormula1( 'AND(LEN(' . $cellRef . ')=10,ISNUMBER(DATEVALUE(' . $cellRef . ')))' );
                break;

            case 'time':
                // Force hh:mm text format
                $sheet->getStyle( $cellRef )->getNumberFormat()->setFormatCode( '@' );
                $validation = $sheet->getCell( $cellRef )->getDataValidation();
                $validation->setType( DataValidation::TYPE_CUSTOM );
                $validation->setErrorStyle( DataValidation::STYLE_STOP );
                $validation->setAllowBlank( true );
                $validation->setShowErrorMessage( true );
                $validation->setErrorTitle( __( 'Time format error', 'wp-ednasurvey' ) );
                $validation->setError( __( 'Enter in hh:mm format', 'wp-ednasurvey' ) );
                $validation->setFormula1( 'AND(LEN(' . $cellRef . ')=5,ISNUMBER(TIMEVALUE(' . $cellRef . ')))' );
                break;

            case 'latitude':
                $sheet->getStyle( $cellRef )->getNumberFormat()->setFormatCode( '0.000000' );
                $validation = $sheet->getCell( $cellRef )->getDataValidation();
                $validation->setType( DataValidation::TYPE_DECIMAL );
                $validation->setOperator( DataValidation::OPERATOR_BETWEEN );
                $validation->setErrorStyle( DataValidation::STYLE_STOP );
                $validation->setAllowBlank( true );
                $validation->setShowErrorMessage( true );
                $validation->setErrorTitle( __( 'Latitude range error', 'wp-ednasurvey' ) );
                $validation->setError( __( 'Enter a value between -90 and 90', 'wp-ednasurvey' ) );
                $validation->setFormula1( '-90' );
                $validation->setFormula2( '90' );
                break;

            case 'longitude':
                $sheet->getStyle( $cellRef )->getNumberFormat()->setFormatCode( '0.000000' );
                $validation = $sheet->getCell( $cellRef )->getDataValidation();
                $validation->setType( DataValidation::TYPE_DECIMAL );
                $validation->setOperator( DataValidation::OPERATOR_BETWEEN );
                $validation->setErrorStyle( DataValidation::STYLE_STOP );
                $validation->setAllowBlank( true );
                $validation->setShowErrorMessage( true );
                $validation->setErrorTitle( __( 'Longitude range error', 'wp-ednasurvey' ) );
                $validation->setError( __( 'Enter a value between -180 and 180', 'wp-ednasurvey' ) );
                $validation->setFormula1( '-180' );
                $validation->setFormula2( '180' );
                break;

            case 'integer':
                $sheet->getStyle( $cellRef )->getNumberFormat()->setFormatCode( '0' );
                $validation = $sheet->getCell( $cellRef )->getDataValidation();
                $validation->setType( DataValidation::TYPE_WHOLE );
                $validation->setOperator( DataValidation::OPERATOR_BETWEEN );
                $validation->setErrorStyle( DataValidation::STYLE_STOP );
                $validation->setAllowBlank( true );
                $validation->setShowErrorMessage( true );
                $validation->setErrorTitle( __( 'Integer error', 'wp-ednasurvey' ) );
                $validation->setError( __( 'Enter a whole number', 'wp-ednasurvey' ) );
                $validation->setFormula1( '0' );
                $validation->setFormula2( '9999999' );
                break;

            case 'number':
                $sheet->getStyle( $cellRef )->getNumberFormat()->setFormatCode( '0.00' );
                $validation = $sheet->getCell( $cellRef )->getDataValidation();
                $validation->setType( DataValidation::TYPE_DECIMAL );
                $validation->setOperator( DataValidation::OPERATOR_BETWEEN );
                $validation->setErrorStyle( DataValidation::STYLE_WARNING );
                $validation->setAllowBlank( true );
                $validation->setShowErrorMessage( true );
                $validation->setFormula1( '0' );
                $validation->setFormula2( '9999999' );
                break;

            // 'select' and 'env_local' — handled via Lists sheet in create_template
            // 'text', 'textarea' — no validation needed
        }
    }

    /**
     * Parse an uploaded Excel file and return rows as associative arrays.
     *
     * Expected format (generated by create_template):
     *   Row 1: DB column key names
     *   Row 2: Localized labels
     *   Row 3: Required / Optional
     *   Row 4: Input format instructions
     *   Row 5: Example data
     *   Row 6+: User data
     */
    public function parse_upload( string $file_path ): array {
        $spreadsheet = IOFactory::load( $file_path );
        $sheet       = $spreadsheet->getActiveSheet();

        $maxCol      = $sheet->getHighestColumn();
        $maxColIndex = Coordinate::columnIndexFromString( $maxCol );
        $maxRow      = $sheet->getHighestRow();

        // Row 1 = DB column key names
        $headers = array();
        for ( $c = 1; $c <= $maxColIndex; $c++ ) {
            $headers[ $c ] = trim( (string) $sheet->getCell( [ $c, 1 ] )->getValue() );
        }

        // Data starts at row 6 (rows 2-5 are labels/required/hints/example)
        $rows = array();
        for ( $row = 6; $row <= $maxRow; $row++ ) {
            $data    = array();
            $isEmpty = true;

            for ( $c = 1; $c <= $maxColIndex; $c++ ) {
                $cell  = $sheet->getCell( [ $c, $row ] );
                $value = $cell->getValue();
                // Resolve formula cells to their calculated value
                if ( is_string( $value ) && str_starts_with( $value, '=' ) ) {
                    try {
                        $value = $cell->getCalculatedValue();
                    } catch ( \Exception $e ) {
                        $value = null;
                    }
                }
                $key   = $headers[ $c ] ?? '';

                // Convert Excel date serial to Y-m-d string
                if ( 'survey_date' === $key && is_numeric( $value ) && (float) $value > 1000 ) {
                    try {
                        $dateObj = ExcelDate::excelToDateTimeObject( (float) $value );
                        $value   = $dateObj->format( 'Y-m-d' );
                    } catch ( \Exception $e ) {
                        // keep original
                    }
                }

                // Convert Excel time fraction to H:i string
                if ( 'survey_time' === $key && is_numeric( $value ) && (float) $value < 1 ) {
                    $totalSeconds = round( (float) $value * 86400 );
                    $hours   = (int) ( $totalSeconds / 3600 );
                    $minutes = (int) ( ( $totalSeconds % 3600 ) / 60 );
                    $value   = sprintf( '%02d:%02d', $hours, $minutes );
                }

                // Normalize localized select values to English keys
                if ( str_starts_with( (string) $key, 'env_local' ) && null !== $value && '' !== $value ) {
                    $value = EdnaSurvey_I18n::normalize_choice_value( EdnaSurvey_I18n::get_env_local_choices(), (string) $value );
                }
                if ( 'env_broad' === $key && null !== $value && '' !== $value ) {
                    $value = EdnaSurvey_I18n::normalize_choice_value( EdnaSurvey_I18n::get_env_broad_choices(), (string) $value );
                }
                if ( 'weather' === $key && null !== $value && '' !== $value ) {
                    $value = EdnaSurvey_I18n::normalize_choice_value( EdnaSurvey_I18n::get_weather_choices(), (string) $value );
                }
                if ( 'wind' === $key && null !== $value && '' !== $value ) {
                    $value = EdnaSurvey_I18n::normalize_choice_value( EdnaSurvey_I18n::get_wind_choices(), (string) $value );
                }

                if ( null !== $value && '' !== $value ) {
                    $isEmpty = false;
                }

                if ( ! empty( $key ) ) {
                    $data[ $key ] = $value;
                }
            }

            if ( ! $isEmpty ) {
                $rows[] = $data;
            }
        }

        $spreadsheet->disconnectWorksheets();
        return $rows;
    }

    /**
     * Analyze parsed Excel rows against uploaded temp photos.
     *
     * @param array $rows              Parsed rows from parse_upload().
     * @param array $temp_photos       Photo metadata from PhotoService->list_temp_photos().
     * @param int   $threshold_minutes EXIF datetime matching threshold.
     * @return array{sites: array, errors: array, warnings: array}
     */
    public function analyze_with_photos( array $rows, array $temp_photos, int $threshold_minutes ): array {
        $validation    = new EdnaSurvey_Validation_Service();
        $field_model   = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_active_fields();

        $errors   = array();
        $warnings = array();
        $sites    = array();

        // Index photos by original_filename
        $photo_by_name = array();
        foreach ( $temp_photos as $p ) {
            $photo_by_name[ $p['original_filename'] ] = $p;
        }

        $assigned = array(); // stored_filename => true

        // Phase 1: Build sites, match photos by explicit filename
        foreach ( $rows as $idx => $row ) {
            $site = array(
                'row_index'      => $idx,
                'raw_data'       => $row,
                'matched_photos' => array(),
                'has_photo_gps'  => false,
                'latitude'       => ! empty( $row['latitude'] ) ? round( (float) $row['latitude'], 6 ) : null,
                'longitude'      => ! empty( $row['longitude'] ) ? round( (float) $row['longitude'], 6 ) : null,
                'has_location'   => ! empty( $row['latitude'] ) && ! empty( $row['longitude'] ),
                'no_photos'      => false,
                'gps_from_photo' => false,
            );

            $pf = trim( $row['photo_files'] ?? '' );
            if ( '' !== $pf ) {
                $names = array_map( 'trim', explode( ',', $pf ) );
                foreach ( $names as $name ) {
                    if ( isset( $photo_by_name[ $name ] ) ) {
                        $photo = $photo_by_name[ $name ];
                        $site['matched_photos'][] = $photo;
                        $assigned[ $photo['stored_filename'] ] = true;
                        if ( $photo['exif_latitude'] && $photo['exif_longitude'] ) {
                            $site['has_photo_gps'] = true;
                        }
                    } else {
                        $errors[] = sprintf(
                            /* translators: 1: row number, 2: filename */
                            __( 'Row %1$d: Photo file "%2$s" not found in uploaded photos.', 'wp-ednasurvey' ),
                            $idx + 1,
                            $name
                        );
                    }
                }
            }

            $sites[] = $site;
        }

        // Phase 2: For sites with empty photo_files, match by EXIF datetime
        $unassigned = array();
        foreach ( $temp_photos as $p ) {
            if ( ! isset( $assigned[ $p['stored_filename'] ] ) && ! empty( $p['exif_datetime'] ) ) {
                $unassigned[] = $p;
            }
        }

        foreach ( $sites as &$site ) {
            $pf = trim( $site['raw_data']['photo_files'] ?? '' );
            if ( '' !== $pf ) {
                continue; // already matched by filename
            }

            $survey_dt_str = ( $site['raw_data']['survey_date'] ?? '' ) . ' ' . ( $site['raw_data']['survey_time'] ?? '' ) . ':00';
            $survey_ts     = strtotime( $survey_dt_str );
            if ( false === $survey_ts ) {
                continue;
            }

            $candidates = array();
            foreach ( $unassigned as $photo ) {
                $photo_ts = strtotime( $photo['exif_datetime'] );
                if ( false === $photo_ts ) {
                    continue;
                }
                $diff_min = abs( $photo_ts - $survey_ts ) / 60;
                if ( $diff_min <= $threshold_minutes ) {
                    // Check how many sites (with empty photo_files) this photo could match
                    $matching_rows = array();
                    foreach ( $sites as $other ) {
                        if ( '' !== trim( $other['raw_data']['photo_files'] ?? '' ) ) {
                            continue;
                        }
                        $other_dt = ( $other['raw_data']['survey_date'] ?? '' ) . ' ' . ( $other['raw_data']['survey_time'] ?? '' ) . ':00';
                        $other_ts = strtotime( $other_dt );
                        if ( false !== $other_ts && abs( $photo_ts - $other_ts ) / 60 <= $threshold_minutes ) {
                            $matching_rows[] = $other['row_index'] + 1;
                        }
                    }

                    if ( count( $matching_rows ) > 1 ) {
                        $errors[] = sprintf(
                            /* translators: 1: filename, 2: EXIF datetime, 3: row list */
                            __( 'Photo "%1$s" (EXIF: %2$s) matches multiple samples (rows: %3$s). Please specify photo_files in Excel.', 'wp-ednasurvey' ),
                            $photo['original_filename'],
                            $photo['exif_datetime'],
                            implode( ', ', $matching_rows )
                        );
                    } else {
                        $candidates[] = array( 'photo' => $photo, 'diff' => $diff_min );
                    }
                }
            }

            // Sort by time difference and assign
            usort( $candidates, fn( $a, $b ) => $a['diff'] <=> $b['diff'] );
            foreach ( $candidates as $c ) {
                if ( isset( $assigned[ $c['photo']['stored_filename'] ] ) ) {
                    continue;
                }
                $site['matched_photos'][] = $c['photo'];
                $assigned[ $c['photo']['stored_filename'] ] = true;
                if ( $c['photo']['exif_latitude'] && $c['photo']['exif_longitude'] ) {
                    $site['has_photo_gps'] = true;
                }
            }
        }
        unset( $site );

        // Phase 3: Fill GPS from matched photos if site lacks coordinates
        foreach ( $sites as &$site ) {
            if ( $site['has_location'] || empty( $site['matched_photos'] ) ) {
                continue;
            }
            // Priority: first in list (= first in photo_files or closest time)
            foreach ( $site['matched_photos'] as $photo ) {
                if ( $photo['exif_latitude'] && $photo['exif_longitude'] ) {
                    $site['latitude']       = round( (float) $photo['exif_latitude'], 6 );
                    $site['longitude']      = round( (float) $photo['exif_longitude'], 6 );
                    $site['has_location']   = true;
                    $site['gps_from_photo'] = true;
                    break;
                }
            }
        }
        unset( $site );

        // Phase 4: Validate each row
        foreach ( $sites as $site ) {
            $row_errors = $validation->validate_offline_row(
                $site['raw_data'],
                $site['row_index'] + 1,
                $site['has_photo_gps'],
                $custom_fields
            );
            $errors = array_merge( $errors, $row_errors );
        }

        // Phase 5: Warnings
        foreach ( $sites as &$site ) {
            if ( empty( $site['matched_photos'] ) ) {
                $site['no_photos'] = true;
                $warnings[] = sprintf(
                    /* translators: %d: row number */
                    __( 'Row %d: No photos matched. This site will be registered without photos.', 'wp-ednasurvey' ),
                    $site['row_index'] + 1
                );
            }
        }
        unset( $site );

        foreach ( $temp_photos as $p ) {
            if ( ! isset( $assigned[ $p['stored_filename'] ] ) ) {
                $warnings[] = sprintf(
                    /* translators: %s: filename */
                    __( 'Photo "%s" was not matched to any sample.', 'wp-ednasurvey' ),
                    $p['original_filename']
                );
            }
        }

        return array( 'sites' => $sites, 'errors' => $errors, 'warnings' => $warnings );
    }
}
