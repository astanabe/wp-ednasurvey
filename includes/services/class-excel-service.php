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
                'key' => 'sample_id', 'ja' => 'サンプルID', 'en' => 'Sample ID',
                'required_ja' => '必須', 'required_en' => 'Required',
                'hint_ja' => '指定された文字列', 'hint_en' => 'Assigned string',
                'example_ja' => 'EWJ0001', 'example_en' => 'EWJ0001',
                'type' => 'text',
            );
        }
        if ( ! empty( $fields_config['survey_datetime'] ) ) {
            $columns[] = array(
                'key' => 'survey_date', 'ja' => '採集日', 'en' => 'Survey Date',
                'required_ja' => '必須', 'required_en' => 'Required',
                'hint_ja' => 'YYYY-MM-DD形式', 'hint_en' => 'YYYY-MM-DD format',
                'example_ja' => '2026-04-09', 'example_en' => '2026-04-09',
                'type' => 'date',
            );
            $columns[] = array(
                'key' => 'survey_time', 'ja' => '採集時刻', 'en' => 'Survey Time',
                'required_ja' => '必須', 'required_en' => 'Required',
                'hint_ja' => 'hh:mm（24時間表記）', 'hint_en' => 'hh:mm (24-hour)',
                'example_ja' => '14:30', 'example_en' => '14:30',
                'type' => 'time',
            );
        }
        if ( ! empty( $fields_config['location'] ) ) {
            $columns[] = array(
                'key' => 'latitude', 'ja' => '緯度', 'en' => 'Latitude',
                'required_ja' => '写真にGPSがあれば省略可', 'required_en' => 'Omit if photo has GPS',
                'hint_ja' => '小数表記（小数点以下6桁）。分秒表記禁止。南緯は負の値', 'hint_en' => 'Decimal (6 places). No DMS. South is negative',
                'example_ja' => '38.268215', 'example_en' => '38.268215',
                'type' => 'latitude',
            );
            $columns[] = array(
                'key' => 'longitude', 'ja' => '経度', 'en' => 'Longitude',
                'required_ja' => '写真にGPSがあれば省略可', 'required_en' => 'Omit if photo has GPS',
                'hint_ja' => '小数表記（小数点以下6桁）。分秒表記禁止。西経は負の値', 'hint_en' => 'Decimal (6 places). No DMS. West is negative',
                'example_ja' => '140.981483', 'example_en' => '140.981483',
                'type' => 'longitude',
            );
        }
        if ( ! empty( $fields_config['site_name'] ) ) {
            $columns[] = array(
                'key' => 'sitename_local', 'ja' => '現地語地点名', 'en' => 'Local Language Site Name',
                'required_ja' => '必須', 'required_en' => 'Required',
                'hint_ja' => '日本語文字列', 'hint_en' => 'Local language string',
                'example_ja' => '仙台湾荒浜', 'example_en' => '仙台湾荒浜',
                'type' => 'text',
            );
            $columns[] = array(
                'key' => 'sitename_en', 'ja' => '英語地点名', 'en' => 'Site Name (English)',
                'required_ja' => '必須', 'required_en' => 'Required',
                'hint_ja' => 'アルファベット表記', 'hint_en' => 'Alphabet characters',
                'example_ja' => 'Arahama coast, Sendai bay', 'example_en' => 'Arahama coast, Sendai bay',
                'type' => 'text',
            );
        }
        if ( ! empty( $fields_config['correspondence'] ) ) {
            $columns[] = array(
                'key' => 'correspondence', 'ja' => '代表者氏名', 'en' => 'Representative',
                'required_ja' => '必須', 'required_en' => 'Required',
                'hint_ja' => '文字列', 'hint_en' => 'Text',
                'example_ja' => '東北太郎', 'example_en' => 'Taro Tohoku',
                'type' => 'text',
            );
        }
        if ( ! empty( $fields_config['collectors'] ) ) {
            $example_names_ja = array( '東北太郎', '東北次郎', '東北三郎', '', '' );
            $example_names_en = array( 'Taro Tohoku', 'Jiro Tohoku', 'Saburo Tohoku', '', '' );
            for ( $i = 1; $i <= 5; $i++ ) {
                $columns[] = array(
                    'key' => 'collector' . $i, 'ja' => '採集者' . $i, 'en' => 'Collector ' . $i,
                    'required_ja' => 1 === $i ? '必須' : '省略可',
                    'required_en' => 1 === $i ? 'Required' : 'Optional',
                    'hint_ja' => '文字列', 'hint_en' => 'Text',
                    'example_ja' => $example_names_ja[ $i - 1 ], 'example_en' => $example_names_en[ $i - 1 ],
                    'type' => 'text',
                );
            }
        }
        if ( ! empty( $fields_config['water_volume'] ) ) {
            $columns[] = array(
                'key' => 'watervol1', 'ja' => '濾過水量1(mL)', 'en' => 'Filtered Water Vol. 1 (mL)',
                'required_ja' => '必須（0可）', 'required_en' => 'Required (0 allowed)',
                'hint_ja' => '整数値(mL)', 'hint_en' => 'Integer (mL)',
                'example_ja' => '500', 'example_en' => '500',
                'type' => 'integer',
            );
            $columns[] = array(
                'key' => 'watervol2', 'ja' => '濾過水量2(mL)', 'en' => 'Filtered Water Vol. 2 (mL)',
                'required_ja' => '必須（0可）', 'required_en' => 'Required (0 allowed)',
                'hint_ja' => '整数値(mL)', 'hint_en' => 'Integer (mL)',
                'example_ja' => '500', 'example_en' => '500',
                'type' => 'integer',
            );
        }

        // Dynamic custom fields
        $field_model   = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_active_fields();
        foreach ( $custom_fields as $cf ) {
            $columns[] = array(
                'key'         => 'custom_' . $cf->field_key,
                'ja'          => $cf->label_ja,
                'en'          => $cf->label_en,
                'required_ja' => $cf->is_required ? '必須' : '省略可',
                'required_en' => $cf->is_required ? 'Required' : 'Optional',
                'hint_ja'     => $cf->field_type,
                'hint_en'     => $cf->field_type,
                'example_ja'  => '',
                'example_en'  => '',
                'type'        => $cf->field_type,
                'options'     => $cf->field_options ? json_decode( $cf->field_options, true ) : null,
            );
        }

        if ( ! empty( $fields_config['notes'] ) ) {
            $columns[] = array(
                'key' => 'notes', 'ja' => '備考', 'en' => 'Notes',
                'required_ja' => '省略可', 'required_en' => 'Optional',
                'hint_ja' => '自由記述', 'hint_en' => 'Free text',
                'example_ja' => '強風', 'example_en' => 'Strong wind',
                'type' => 'text',
            );
        }
        if ( ! empty( $fields_config['photos'] ) ) {
            $columns[] = array(
                'key' => 'photo_files', 'ja' => '写真ファイル名', 'en' => 'Photo Filenames',
                'required_ja' => '写真未撮影なら省略可', 'required_en' => 'Omit if no photos taken',
                'hint_ja' => 'カンマ区切りで複数記述', 'hint_en' => 'Comma-separated for multiple',
                'example_ja' => 'IMG_001.jpg,IMG_002.jpg', 'example_en' => 'IMG_001.jpg,IMG_002.jpg',
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

        $lang = EdnaSurvey_I18n::get_current_language();

        // Set default font
        if ( 'ja' === $lang ) {
            $spreadsheet->getDefaultStyle()->getFont()->setName( '游ゴシック' )->setSize( 11 );
        }
        $columns = $this->get_columns();
        $colCount = count( $columns );
        $lastCol  = Coordinate::stringFromColumnIndex( $colCount );

        // -- Write header rows --------------------------------------------------

        foreach ( $columns as $idx => $col ) {
            $c = $idx + 1;

            // Row 1: DB column key
            $sheet->getCellByColumnAndRow( $c, 1 )->setValue( $col['key'] );

            // Row 2: Language label
            $sheet->getCellByColumnAndRow( $c, 2 )->setValue(
                'ja' === $lang ? $col['ja'] : $col['en']
            );

            // Row 3: Required / Optional
            $sheet->getCellByColumnAndRow( $c, 3 )->setValue(
                'ja' === $lang ? ( $col['required_ja'] ?? '' ) : ( $col['required_en'] ?? '' )
            );

            // Row 4: Input format instructions
            $sheet->getCellByColumnAndRow( $c, 4 )->setValue(
                'ja' === $lang ? $col['hint_ja'] : $col['hint_en']
            );

            // Row 5: Example data
            $sheet->getCellByColumnAndRow( $c, 5 )->setValue(
                'ja' === $lang ? ( $col['example_ja'] ?? '' ) : ( $col['example_en'] ?? '' )
            );

            $sheet->getColumnDimensionByColumn( $c )->setAutoSize( true );
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

        foreach ( $columns as $idx => $col ) {
            $colLetter = Coordinate::stringFromColumnIndex( $idx + 1 );

            for ( $row = $dataStartRow; $row <= $dataEndRow; $row++ ) {
                $cellRef = $colLetter . $row;
                $this->apply_validation( $sheet, $cellRef, $col );
            }
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
                $validation->setErrorTitle( 'ja' === EdnaSurvey_I18n::get_current_language() ? '日付形式エラー' : 'Date format error' );
                $validation->setError( 'ja' === EdnaSurvey_I18n::get_current_language() ? 'YYYY-MM-DD形式で入力してください' : 'Enter in YYYY-MM-DD format' );
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
                $validation->setErrorTitle( 'ja' === EdnaSurvey_I18n::get_current_language() ? '時刻形式エラー' : 'Time format error' );
                $validation->setError( 'ja' === EdnaSurvey_I18n::get_current_language() ? 'hh:mm形式で入力してください' : 'Enter in hh:mm format' );
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
                $validation->setErrorTitle( 'ja' === EdnaSurvey_I18n::get_current_language() ? '緯度範囲エラー' : 'Latitude range error' );
                $validation->setError( 'ja' === EdnaSurvey_I18n::get_current_language() ? '-90〜90の範囲で入力してください' : 'Enter a value between -90 and 90' );
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
                $validation->setErrorTitle( 'ja' === EdnaSurvey_I18n::get_current_language() ? '経度範囲エラー' : 'Longitude range error' );
                $validation->setError( 'ja' === EdnaSurvey_I18n::get_current_language() ? '-180〜180の範囲で入力してください' : 'Enter a value between -180 and 180' );
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
                $validation->setErrorTitle( 'ja' === EdnaSurvey_I18n::get_current_language() ? '整数エラー' : 'Integer error' );
                $validation->setError( 'ja' === EdnaSurvey_I18n::get_current_language() ? '整数値を入力してください' : 'Enter a whole number' );
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

            case 'select':
                if ( ! empty( $col['options']['choices'] ) ) {
                    $validation = $sheet->getCell( $cellRef )->getDataValidation();
                    $validation->setType( DataValidation::TYPE_LIST );
                    $validation->setErrorStyle( DataValidation::STYLE_STOP );
                    $validation->setAllowBlank( true );
                    $validation->setShowDropDown( true );
                    $validation->setFormula1( '"' . implode( ',', $col['options']['choices'] ) . '"' );
                }
                break;

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
            $headers[ $c ] = trim( (string) $sheet->getCellByColumnAndRow( $c, 1 )->getValue() );
        }

        // Data starts at row 6 (rows 2-5 are labels/required/hints/example)
        $rows = array();
        for ( $row = 6; $row <= $maxRow; $row++ ) {
            $data    = array();
            $isEmpty = true;

            for ( $c = 1; $c <= $maxColIndex; $c++ ) {
                $value = $sheet->getCellByColumnAndRow( $c, $row )->getValue();
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
