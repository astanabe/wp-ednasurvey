<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Admin_Settings {

    public function render(): void {
        if ( isset( $_POST['ednasurvey_settings_nonce'] ) &&
             wp_verify_nonce( $_POST['ednasurvey_settings_nonce'], 'ednasurvey_save_settings' ) ) {
            $this->save_settings();
        }

        $settings      = get_option( 'ednasurvey_settings', array() );
        $field_model   = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $field_model->get_all_fields();
        $env_checks    = $this->check_environment();

        include EDNASURVEY_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Check server environment for required/recommended extensions and libraries.
     *
     * @return array[] Each item: name, status (ok|warning|error), message
     */
    private function check_environment(): array {
        $checks = array();

        // PHP version
        $php_ok = version_compare( PHP_VERSION, '8.1', '>=' );
        $checks[] = array(
            'name'     => 'PHP',
            'status'   => $php_ok ? 'ok' : 'error',
            'version'  => PHP_VERSION,
            'message'  => $php_ok
                ? __( 'PHP 8.1 or later is required.', 'wp-ednasurvey' )
                : __( 'PHP 8.1 or later is required. Current version does not meet the requirement.', 'wp-ednasurvey' ),
        );

        // Imagick extension
        $imagick_loaded = extension_loaded( 'imagick' );
        $imagick_heic   = false;
        $imagick_ver    = '';
        if ( $imagick_loaded && class_exists( 'Imagick' ) ) {
            $imagick_ver  = Imagick::getVersion()['versionString'] ?? '';
            $heic_formats = Imagick::queryFormats( 'HEIC' );
            $imagick_heic = ! empty( $heic_formats );
        }
        if ( ! $imagick_loaded ) {
            $checks[] = array(
                'name'    => 'Imagick',
                'status'  => 'warning',
                'version' => '-',
                'message' => __( 'Not installed. CLI tools (heif-convert, ffmpeg, exiftool) will be used as fallback for HEIC/HEIF processing.', 'wp-ednasurvey' ),
            );
        } elseif ( ! $imagick_heic ) {
            $checks[] = array(
                'name'    => 'Imagick',
                'status'  => 'warning',
                'version' => $imagick_ver,
                'message' => __( 'Installed but HEIC format is not supported. CLI tools (heif-convert, ffmpeg, exiftool) will be used as fallback.', 'wp-ednasurvey' ),
            );
        } else {
            $checks[] = array(
                'name'    => 'Imagick (HEIC)',
                'status'  => 'ok',
                'version' => $imagick_ver,
                'message' => __( 'HEIC/HEIF to JPEG conversion and GPS extraction from HEIC/HEIF photos are available.', 'wp-ednasurvey' ),
            );
        }

        // CLI tools for HEIC fallback (resolved via settings or auto-detect)
        $cmd_defs = array(
            'cmd_imagemagick'  => array( 'label' => 'ImageMagick', 'purpose' => __( 'HEIC to JPEG conversion', 'wp-ednasurvey' ) ),
            'cmd_heif_convert' => array( 'label' => 'heif-dec/heif-convert', 'purpose' => __( 'HEIC to JPEG conversion (libheif)', 'wp-ednasurvey' ) ),
            'cmd_ffmpeg'       => array( 'label' => 'FFmpeg', 'purpose' => __( 'HEIC to JPEG conversion (FFmpeg)', 'wp-ednasurvey' ) ),
        );

        $has_heic_convert = false;

        foreach ( $cmd_defs as $key => $def ) {
            $resolved = self::resolve_command( $key );

            if ( ! $resolved ) {
                // Not found — skip display (configurable in External Command Paths below)
                continue;
            }

            // Verify the tool actually supports HEIC
            $supports_heic = self::check_heic_support( $key, $resolved );

            if ( $supports_heic ) {
                $checks[] = array(
                    'name'    => $def['label'],
                    'status'  => 'ok',
                    'version' => $resolved,
                    'message' => $def['purpose'] . ' — ' . __( 'HEIC supported', 'wp-ednasurvey' ),
                );
                $has_heic_convert = true;
            } else {
                $checks[] = array(
                    'name'    => $def['label'],
                    'status'  => 'warning',
                    'version' => $resolved,
                    'message' => $def['purpose'] . ' — ' . __( 'found but HEIC not supported', 'wp-ednasurvey' ),
                );
            }
        }

        if ( ! $imagick_heic && ! $has_heic_convert ) {
            $checks[] = array(
                'name'    => __( 'HEIC conversion', 'wp-ednasurvey' ),
                'status'  => 'error',
                'version' => '-',
                'message' => __( 'No method available. HEIC/HEIF photos cannot be processed. Install Imagick with HEIC support, or heif-dec, or ffmpeg.', 'wp-ednasurvey' ),
            );
        }

        // exif extension (must be checked before exiftool)
        $exif_loaded = function_exists( 'exif_read_data' );
        $checks[] = array(
            'name'    => 'exif',
            'status'  => $exif_loaded ? 'ok' : 'warning',
            'version' => $exif_loaded ? phpversion( 'exif' ) ?: '-' : '-',
            'message' => $exif_loaded
                ? __( 'GPS data can be extracted from all photos (HEIC/HEIF photos are converted to JPEG first, preserving EXIF).', 'wp-ednasurvey' )
                : __( 'Not installed. GPS data cannot be extracted from photos. Location must be entered manually.', 'wp-ednasurvey' ),
        );

        // exiftool (fallback for GPS extraction when PHP exif is unavailable)
        $exiftool_resolved = self::resolve_command( 'cmd_exiftool' );
        if ( $exif_loaded ) {
            if ( $exiftool_resolved ) {
                $checks[] = array(
                    'name'    => 'exiftool',
                    'status'  => 'ok',
                    'version' => $exiftool_resolved,
                    'message' => __( 'Available as additional GPS extraction method (PHP exif is primary).', 'wp-ednasurvey' ),
                );
            }
        } else {
            if ( $exiftool_resolved ) {
                $checks[] = array(
                    'name'    => 'exiftool',
                    'status'  => 'ok',
                    'version' => $exiftool_resolved,
                    'message' => __( 'GPS extraction via exiftool (PHP exif not available).', 'wp-ednasurvey' ),
                );
            } else {
                $checks[] = array(
                    'name'    => 'exiftool',
                    'status'  => 'error',
                    'version' => '-',
                    'message' => __( 'Not found. No method available for GPS extraction. Install PHP exif extension or exiftool.', 'wp-ednasurvey' ),
                );
            }
        }

        // mbstring extension
        $mb_loaded = extension_loaded( 'mbstring' );
        $checks[] = array(
            'name'    => 'mbstring',
            'status'  => $mb_loaded ? 'ok' : 'warning',
            'version' => $mb_loaded ? phpversion( 'mbstring' ) ?: '-' : '-',
            'message' => $mb_loaded
                ? __( 'Multi-byte string processing (Japanese text) is available.', 'wp-ednasurvey' )
                : __( 'Not installed. Japanese text processing may not work correctly.', 'wp-ednasurvey' ),
        );

        // zip extension
        $zip_loaded = extension_loaded( 'zip' );
        $checks[] = array(
            'name'    => 'zip',
            'status'  => $zip_loaded ? 'ok' : 'error',
            'version' => $zip_loaded ? phpversion( 'zip' ) ?: '-' : '-',
            'message' => $zip_loaded
                ? __( 'Required by PhpSpreadsheet for Excel (.xlsx) file handling.', 'wp-ednasurvey' )
                : __( 'Not installed. Excel template generation and offline submission upload will not work.', 'wp-ednasurvey' ),
        );

        // PhpSpreadsheet
        $spreadsheet_ok = class_exists( \PhpOffice\PhpSpreadsheet\Spreadsheet::class );
        $spreadsheet_ver = '-';
        if ( $spreadsheet_ok ) {
            $composer_lock = EDNASURVEY_PLUGIN_DIR . 'composer.lock';
            if ( file_exists( $composer_lock ) ) {
                $lock = json_decode( file_get_contents( $composer_lock ), true );
                foreach ( ( $lock['packages'] ?? array() ) as $pkg ) {
                    if ( 'phpoffice/phpspreadsheet' === ( $pkg['name'] ?? '' ) ) {
                        $spreadsheet_ver = $pkg['version'] ?? '-';
                        break;
                    }
                }
            }
        }
        $checks[] = array(
            'name'    => 'PhpSpreadsheet',
            'status'  => $spreadsheet_ok ? 'ok' : 'error',
            'version' => $spreadsheet_ver,
            'message' => $spreadsheet_ok
                ? __( 'Excel template generation and offline data upload are available.', 'wp-ednasurvey' )
                : __( 'Not installed. Run "composer install" in the plugin directory. Excel template and offline submission features will not work.', 'wp-ednasurvey' ),
        );

        return $checks;
    }

    /**
     * Resolve the effective path for an external command.
     * Returns the settings value if set, otherwise auto-detects via which.
     */
    public static function resolve_command( string $key ): ?string {
        $settings = get_option( 'ednasurvey_settings', array() );
        $path     = $settings[ $key ] ?? '';

        if ( '' !== $path ) {
            return is_executable( $path ) ? $path : null;
        }

        if ( ! function_exists( 'exec' ) ) {
            return null;
        }

        // Auto-detect: try each candidate name
        $candidates = self::command_candidates( $key );
        foreach ( $candidates as $cmd ) {
            $output = array();
            $result = 0;
            @exec( 'which ' . escapeshellarg( $cmd ) . ' 2>/dev/null', $output, $result );
            if ( 0 === $result && ! empty( $output[0] ) ) {
                return trim( $output[0] );
            }
        }

        return null;
    }

    /**
     * Possible binary names for a settings key.
     */
    private static function command_candidates( string $key ): array {
        return match ( $key ) {
            'cmd_imagemagick' => array( 'magick', 'convert' ),
            'cmd_heif_convert' => array( 'heif-dec', 'heif-convert' ),
            'cmd_ffmpeg'       => array( 'ffmpeg' ),
            'cmd_exiftool'     => array( 'exiftool' ),
            default            => array(),
        };
    }

    /**
     * Check if a resolved CLI tool actually supports HEIC format.
     */
    private static function check_heic_support( string $key, string $path ): bool {
        if ( ! function_exists( 'exec' ) ) {
            return false;
        }

        $escaped = escapeshellarg( $path );

        switch ( $key ) {
            case 'cmd_imagemagick':
                $output = array();
                $result = 0;
                $base = basename( $path );
                if ( 'magick' === $base ) {
                    @exec( "{$escaped} identify -list format 2>/dev/null", $output, $result );
                } else {
                    $identify = dirname( $path ) . '/identify';
                    if ( is_executable( $identify ) ) {
                        @exec( escapeshellarg( $identify ) . ' -list format 2>/dev/null', $output, $result );
                    } else {
                        @exec( "{$escaped} -list format 2>/dev/null", $output, $result );
                    }
                }
                if ( 0 !== $result || empty( $output ) ) {
                    return false;
                }
                return (bool) preg_match( '/HEIC\b/i', implode( "\n", $output ) );

            case 'cmd_heif_convert':
                $output = array();
                $result = 0;
                @exec( "{$escaped} --list-decoders 2>/dev/null", $output, $result );
                if ( 0 !== $result || empty( $output ) ) {
                    return false;
                }
                return (bool) preg_match( '/libde265/i', implode( "\n", $output ) );

            case 'cmd_ffmpeg':
                $output = array();
                $result = 0;
                @exec( "{$escaped} -decoders 2>/dev/null", $output, $result );
                if ( empty( $output ) ) {
                    return false;
                }
                return (bool) preg_match( '/hevc/i', implode( "\n", $output ) );
        }

        return false;
    }

    private function save_settings(): void {
        $settings = array(
            'tile_server_url'  => sanitize_text_field( wp_unslash( $_POST['tile_server_url'] ?? '' ) ),
            'tile_attribution' => wp_kses_post( wp_unslash( $_POST['tile_attribution'] ?? '' ) ),
            'map_center_lat'   => (float) ( $_POST['map_center_lat'] ?? 35.6762 ),
            'map_center_lng'   => (float) ( $_POST['map_center_lng'] ?? 139.6503 ),
            'map_default_zoom' => (int) ( $_POST['map_default_zoom'] ?? 5 ),
            'photo_upload_limit'   => max( 1, (int) ( $_POST['photo_upload_limit'] ?? 10 ) ),
            'photo_time_threshold' => max( 1, (int) ( $_POST['photo_time_threshold'] ?? 30 ) ),
            'cmd_imagemagick'  => sanitize_text_field( wp_unslash( $_POST['cmd_imagemagick'] ?? '' ) ),
            'cmd_heif_convert' => sanitize_text_field( wp_unslash( $_POST['cmd_heif_convert'] ?? '' ) ),
            'cmd_ffmpeg'       => sanitize_text_field( wp_unslash( $_POST['cmd_ffmpeg'] ?? '' ) ),
            'cmd_exiftool'     => sanitize_text_field( wp_unslash( $_POST['cmd_exiftool'] ?? '' ) ),
            'default_fields_config' => array(
                'survey_datetime' => ! empty( $_POST['field_survey_datetime'] ),
                'location'        => ! empty( $_POST['field_location'] ),
                'site_name'       => ! empty( $_POST['field_site_name'] ),
                'correspondence'  => ! empty( $_POST['field_correspondence'] ),
                'collectors'      => ! empty( $_POST['field_collectors'] ),
                'sample_id'       => ! empty( $_POST['field_sample_id'] ),
                'water_volume'    => ! empty( $_POST['field_water_volume'] ),
                'notes'           => ! empty( $_POST['field_notes'] ),
                'photos'          => ! empty( $_POST['field_photos'] ),
            ),
        );

        update_option( 'ednasurvey_settings', $settings );

        add_settings_error(
            'ednasurvey_settings',
            'settings_updated',
            __( 'Settings saved.', 'wp-ednasurvey' ),
            'updated'
        );
    }
}
