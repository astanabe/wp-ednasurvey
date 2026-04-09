<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Photo_Service {

    private string $base_dir;
    private string $base_url;

    public function __construct() {
        $upload_dir     = wp_upload_dir();
        $this->base_dir = $upload_dir['basedir'] . '/ednasurvey';
        $this->base_url = $upload_dir['baseurl'] . '/ednasurvey';
    }

    /**
     * Process and save uploaded photo files.
     *
     * @param array   $files     $_FILES array for photos.
     * @param int     $user_id   User ID.
     * @param int     $site_id   Site ID.
     * @return array{saved: array, errors: array}
     */
    public function process_uploads( array $files, int $user_id, int $site_id ): array {
        $saved  = array();
        $errors = array();

        $site_dir = $this->base_dir . '/' . $user_id . '/' . $site_id;
        if ( ! wp_mkdir_p( $site_dir ) ) {
            return array(
                'saved'  => array(),
                'errors' => array( __( 'Failed to create upload directory.', 'wp-ednasurvey' ) ),
            );
        }

        // Add index.php to prevent directory listing
        $index_file = $site_dir . '/index.php';
        if ( ! file_exists( $index_file ) ) {
            file_put_contents( $index_file, '<?php // Silence is golden.' );
        }

        $allowed_types = array( 'image/jpeg', 'image/heic', 'image/heif' );
        $file_count    = is_array( $files['name'] ) ? count( $files['name'] ) : 0;

        for ( $i = 0; $i < $file_count; $i++ ) {
            if ( UPLOAD_ERR_OK !== $files['error'][ $i ] ) {
                /* translators: %s: filename */
                $errors[] = sprintf( __( 'Upload error for %s.', 'wp-ednasurvey' ), $files['name'][ $i ] );
                continue;
            }

            $original_name = sanitize_file_name( $files['name'][ $i ] );
            $tmp_path      = $files['tmp_name'][ $i ];

            // Verify it's an image
            $mime = $this->get_mime_type( $tmp_path );
            if ( ! in_array( $mime, $allowed_types, true ) ) {
                /* translators: %s: filename */
                $errors[] = sprintf( __( 'Invalid file type for %s. Only JPEG and HEIC/HEIF are allowed.', 'wp-ednasurvey' ), $original_name );
                continue;
            }

            // Convert HEIC/HEIF to JPEG first (preserves EXIF metadata)
            $is_heic = in_array( $mime, array( 'image/heic', 'image/heif' ), true );
            if ( $is_heic ) {
                $converted = $this->convert_heic_to_jpeg( $tmp_path );
                if ( ! $converted ) {
                    /* translators: %s: filename */
                    $errors[] = sprintf( __( 'Failed to convert %s from HEIC to JPEG.', 'wp-ednasurvey' ), $original_name );
                    continue;
                }
                $tmp_path = $converted;
            }

            // Extract EXIF GPS from JPEG (works for both original JPEG and converted HEIC)
            $exif_data = $this->extract_gps_from_exif( $tmp_path );

            // Generate unique filename
            $ext             = 'jpg';
            $stored_filename = wp_unique_filename( $site_dir, pathinfo( $original_name, PATHINFO_FILENAME ) . '.' . $ext );
            $dest_path       = $site_dir . '/' . $stored_filename;

            if ( ! move_uploaded_file( $is_heic ? $tmp_path : $tmp_path, $dest_path ) ) {
                if ( $is_heic && file_exists( $tmp_path ) ) {
                    // For converted files, use copy since it's already a temp file
                    if ( ! rename( $tmp_path, $dest_path ) ) {
                        /* translators: %s: filename */
                        $errors[] = sprintf( __( 'Failed to save %s.', 'wp-ednasurvey' ), $original_name );
                        continue;
                    }
                } else {
                    /* translators: %s: filename */
                    $errors[] = sprintf( __( 'Failed to save %s.', 'wp-ednasurvey' ), $original_name );
                    continue;
                }
            }

            $relative_path = $user_id . '/' . $site_id . '/' . $stored_filename;
            $file_url      = $this->base_url . '/' . $relative_path;

            $saved[] = array(
                'original_filename' => $original_name,
                'stored_filename'   => $stored_filename,
                'file_path'         => $relative_path,
                'file_url'          => $file_url,
                'mime_type'         => 'image/jpeg',
                'exif_latitude'     => $exif_data['latitude'] ?? null,
                'exif_longitude'    => $exif_data['longitude'] ?? null,
            );
        }

        return array( 'saved' => $saved, 'errors' => $errors );
    }

    /**
     * Extract GPS from EXIF metadata.
     *
     * Called after HEIC→JPEG conversion, so the file is always JPEG at this point.
     * 1. PHP exif_read_data() — if the exif extension is available
     * 2. exiftool CLI — fallback for environments without PHP exif
     */
    public function extract_gps_from_exif( string $file_path ): ?array {
        // 1. PHP exif extension
        if ( function_exists( 'exif_read_data' ) ) {
            $exif = @exif_read_data( $file_path );
            if ( $exif && isset( $exif['GPSLatitude'], $exif['GPSLatitudeRef'] ) ) {
                $lat = $this->gps_to_decimal( $exif['GPSLatitude'], $exif['GPSLatitudeRef'] );
                $lng = $this->gps_to_decimal( $exif['GPSLongitude'], $exif['GPSLongitudeRef'] );

                if ( null !== $lat && null !== $lng ) {
                    return array(
                        'latitude'  => round( $lat, 6 ),
                        'longitude' => round( $lng, 6 ),
                    );
                }
            }
        }

        // 2. exiftool CLI fallback
        return $this->extract_gps_via_exiftool( $file_path );
    }

    private function extract_gps_via_exiftool( string $file_path ): ?array {
        $exiftool = EdnaSurvey_Admin_Settings::resolve_command( 'cmd_exiftool' );
        if ( ! $exiftool ) {
            return null;
        }

        $output = array();
        $result = 0;
        exec( escapeshellarg( $exiftool ) . ' -n -p ' . escapeshellarg( '${GPSLatitude},${GPSLongitude}' ) . ' ' . escapeshellarg( $file_path ) . ' 2>/dev/null', $output, $result );

        if ( 0 !== $result || empty( $output[0] ) ) {
            return null;
        }

        $parts = explode( ',', $output[0] );
        if ( count( $parts ) < 2 ) {
            return null;
        }

        $lat = (float) trim( $parts[0] );
        $lng = (float) trim( $parts[1] );

        if ( 0.0 === $lat && 0.0 === $lng ) {
            return null;
        }

        return array(
            'latitude'  => round( $lat, 6 ),
            'longitude' => round( $lng, 6 ),
        );
    }

    private function gps_to_decimal( array $coordinate, string $hemisphere ): ?float {
        if ( count( $coordinate ) < 3 ) {
            return null;
        }

        $degrees = $this->rational_to_float( $coordinate[0] );
        $minutes = $this->rational_to_float( $coordinate[1] );
        $seconds = $this->rational_to_float( $coordinate[2] );

        if ( null === $degrees || null === $minutes || null === $seconds ) {
            return null;
        }

        $decimal = $degrees + ( $minutes / 60 ) + ( $seconds / 3600 );

        if ( 'S' === $hemisphere || 'W' === $hemisphere ) {
            $decimal *= -1;
        }

        return $decimal;
    }

    private function rational_to_float( string $rational ): ?float {
        $parts = explode( '/', $rational );
        if ( count( $parts ) === 2 && (float) $parts[1] !== 0.0 ) {
            return (float) $parts[0] / (float) $parts[1];
        }
        if ( is_numeric( $rational ) ) {
            return (float) $rational;
        }
        return null;
    }

    private function convert_heic_to_jpeg( string $source_path ): ?string {
        $jpeg_path      = $source_path . '.jpg';
        $source_escaped = escapeshellarg( $source_path );
        $dest_escaped   = escapeshellarg( $jpeg_path );

        // 1. Imagick PHP extension
        if ( class_exists( 'Imagick' ) ) {
            try {
                $imagick = new Imagick( $source_path );
                $imagick->setImageFormat( 'jpeg' );
                $imagick->setImageCompressionQuality( 85 );
                $imagick->writeImage( $jpeg_path );
                $imagick->destroy();
                return $jpeg_path;
            } catch ( \Exception $e ) {
                // Fall through
            }
        }

        // 2. heif-convert (libheif)
        $heif_convert = EdnaSurvey_Admin_Settings::resolve_command( 'cmd_heif_convert' );
        if ( $heif_convert ) {
            $result = 0;
            exec( escapeshellarg( $heif_convert ) . " -q 85 {$source_escaped} {$dest_escaped} 2>/dev/null", $output, $result );
            if ( 0 === $result && file_exists( $jpeg_path ) ) {
                return $jpeg_path;
            }
        }

        // 3. ImageMagick CLI (magick / convert)
        $imagemagick = EdnaSurvey_Admin_Settings::resolve_command( 'cmd_imagemagick' );
        if ( $imagemagick ) {
            $result = 0;
            exec( escapeshellarg( $imagemagick ) . " {$source_escaped} {$dest_escaped} 2>/dev/null", $output, $result );
            if ( 0 === $result && file_exists( $jpeg_path ) ) {
                return $jpeg_path;
            }
        }

        // 4. FFmpeg
        $ffmpeg = EdnaSurvey_Admin_Settings::resolve_command( 'cmd_ffmpeg' );
        if ( $ffmpeg ) {
            $result = 0;
            exec( escapeshellarg( $ffmpeg ) . " -y -i {$source_escaped} -q:v 2 {$dest_escaped} 2>/dev/null", $output, $result );
            if ( 0 === $result && file_exists( $jpeg_path ) ) {
                return $jpeg_path;
            }
        }

        return null;
    }

    private function get_mime_type( string $file_path ): string {
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime  = finfo_file( $finfo, $file_path );
        finfo_close( $finfo );

        // HEIC/HEIF files may report as application/octet-stream
        if ( 'application/octet-stream' === $mime ) {
            $ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
            if ( in_array( $ext, array( 'heic', 'heif' ), true ) ) {
                return 'image/heic';
            }
        }

        return $mime ?: '';
    }

    // ── Temp directory management ─────────────────────────────────────

    public function create_temp_dir( string $session_id ): string {
        $dir = $this->base_dir . '/temp/' . sanitize_file_name( $session_id );
        if ( ! wp_mkdir_p( $dir ) ) {
            throw new \RuntimeException( 'Failed to create temp directory.' );
        }
        $index = $dir . '/index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, '<?php // Silence is golden.' );
        }
        return $dir;
    }

    private function get_temp_dir( string $session_id ): string {
        return $this->base_dir . '/temp/' . sanitize_file_name( $session_id );
    }

    private function get_temp_metadata_path( string $session_id ): string {
        return $this->get_temp_dir( $session_id ) . '/_metadata.json';
    }

    private function read_temp_metadata( string $session_id ): array {
        $path = $this->get_temp_metadata_path( $session_id );
        if ( ! file_exists( $path ) ) {
            return array();
        }
        $data = json_decode( file_get_contents( $path ), true );
        return is_array( $data ) ? $data : array();
    }

    private function write_temp_metadata( string $session_id, array $metadata ): void {
        file_put_contents( $this->get_temp_metadata_path( $session_id ), wp_json_encode( $metadata ) );
    }

    /**
     * Process a single photo into the temp directory.
     * Returns metadata array with EXIF info.
     */
    public function process_temp_upload( array $file, string $session_id ): array {
        $dir = $this->create_temp_dir( $session_id );

        if ( UPLOAD_ERR_OK !== $file['error'] ) {
            throw new \RuntimeException( sprintf( __( 'Upload error for %s.', 'wp-ednasurvey' ), $file['name'] ) );
        }

        $original_name = sanitize_file_name( $file['name'] );
        $tmp_path      = $file['tmp_name'];

        $mime = $this->get_mime_type( $tmp_path );
        $allowed = array( 'image/jpeg', 'image/heic', 'image/heif' );
        if ( ! in_array( $mime, $allowed, true ) ) {
            throw new \RuntimeException( sprintf( __( 'Invalid file type for %s. Only JPEG and HEIC/HEIF are allowed.', 'wp-ednasurvey' ), $original_name ) );
        }

        // Convert HEIC to JPEG
        $is_heic = in_array( $mime, array( 'image/heic', 'image/heif' ), true );
        if ( $is_heic ) {
            $converted = $this->convert_heic_to_jpeg( $tmp_path );
            if ( ! $converted ) {
                throw new \RuntimeException( sprintf( __( 'Failed to convert %s from HEIC to JPEG.', 'wp-ednasurvey' ), $original_name ) );
            }
            $tmp_path = $converted;
        }

        // Extract EXIF (GPS + datetime) from the now-JPEG file
        $exif_gps      = $this->extract_gps_from_exif( $tmp_path );
        $exif_datetime  = $this->extract_datetime_from_exif( $tmp_path );

        // Save to temp dir
        $stored_filename = wp_unique_filename( $dir, pathinfo( $original_name, PATHINFO_FILENAME ) . '.jpg' );
        $dest_path       = $dir . '/' . $stored_filename;

        if ( $is_heic ) {
            rename( $tmp_path, $dest_path );
        } else {
            move_uploaded_file( $tmp_path, $dest_path );
        }

        $photo_meta = array(
            'original_filename' => $original_name,
            'stored_filename'   => $stored_filename,
            'mime_type'         => 'image/jpeg',
            'exif_latitude'     => $exif_gps['latitude'] ?? null,
            'exif_longitude'    => $exif_gps['longitude'] ?? null,
            'exif_datetime'     => $exif_datetime,
            'thumbnail_url'     => $this->base_url . '/temp/' . sanitize_file_name( $session_id ) . '/' . $stored_filename,
        );

        // Append to _metadata.json
        $metadata = $this->read_temp_metadata( $session_id );
        $metadata[ $stored_filename ] = $photo_meta;
        $this->write_temp_metadata( $session_id, $metadata );

        return $photo_meta;
    }

    public function delete_temp_photo( string $session_id, string $stored_filename ): bool {
        $dir  = $this->get_temp_dir( $session_id );
        $file = $dir . '/' . sanitize_file_name( $stored_filename );

        if ( ! file_exists( $file ) ) {
            return false;
        }

        unlink( $file );

        $metadata = $this->read_temp_metadata( $session_id );
        unset( $metadata[ $stored_filename ] );
        $this->write_temp_metadata( $session_id, $metadata );

        return true;
    }

    public function list_temp_photos( string $session_id ): array {
        return array_values( $this->read_temp_metadata( $session_id ) );
    }

    /**
     * Move matched photos from temp to permanent storage for a site.
     */
    public function move_temp_to_permanent( string $session_id, array $filenames, int $user_id, int $site_id ): array {
        $temp_dir = $this->get_temp_dir( $session_id );
        $site_dir = $this->base_dir . '/' . $user_id . '/' . $site_id;

        if ( ! wp_mkdir_p( $site_dir ) ) {
            return array( 'moved' => array(), 'errors' => array( __( 'Failed to create upload directory.', 'wp-ednasurvey' ) ) );
        }

        $index = $site_dir . '/index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, '<?php // Silence is golden.' );
        }

        $metadata = $this->read_temp_metadata( $session_id );
        $moved    = array();
        $errors   = array();

        foreach ( $filenames as $fn ) {
            $src = $temp_dir . '/' . sanitize_file_name( $fn );
            if ( ! file_exists( $src ) ) {
                $errors[] = sprintf( __( 'Temp photo %s not found.', 'wp-ednasurvey' ), $fn );
                continue;
            }

            $dest_name = wp_unique_filename( $site_dir, $fn );
            $dest      = $site_dir . '/' . $dest_name;

            if ( ! rename( $src, $dest ) ) {
                $errors[] = sprintf( __( 'Failed to move %s.', 'wp-ednasurvey' ), $fn );
                continue;
            }

            $meta          = $metadata[ $fn ] ?? array();
            $relative_path = $user_id . '/' . $site_id . '/' . $dest_name;

            $moved[] = array(
                'original_filename' => $meta['original_filename'] ?? $fn,
                'stored_filename'   => $dest_name,
                'file_path'         => $relative_path,
                'file_url'          => $this->base_url . '/' . $relative_path,
                'mime_type'         => 'image/jpeg',
                'exif_latitude'     => $meta['exif_latitude'] ?? null,
                'exif_longitude'    => $meta['exif_longitude'] ?? null,
            );
        }

        return array( 'moved' => $moved, 'errors' => $errors );
    }

    /**
     * Delete a temp session directory entirely.
     */
    public function delete_temp_dir( string $session_id ): void {
        $dir = $this->get_temp_dir( $session_id );
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $files = glob( $dir . '/*' );
        if ( $files ) {
            foreach ( $files as $f ) {
                if ( is_file( $f ) ) {
                    unlink( $f );
                }
            }
        }
        rmdir( $dir );
    }

    /**
     * Clean up temp directories older than max_age_seconds.
     */
    public function cleanup_temp_dirs( int $max_age_seconds = 86400 ): int {
        $temp_base = $this->base_dir . '/temp';
        if ( ! is_dir( $temp_base ) ) {
            return 0;
        }

        $cleaned = 0;
        $dirs    = glob( $temp_base . '/*', GLOB_ONLYDIR );
        $now     = time();

        foreach ( $dirs as $dir ) {
            if ( $now - filemtime( $dir ) > $max_age_seconds ) {
                $files = glob( $dir . '/*' );
                if ( $files ) {
                    foreach ( $files as $f ) {
                        if ( is_file( $f ) ) {
                            unlink( $f );
                        }
                    }
                }
                rmdir( $dir );
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Extract DateTimeOriginal from EXIF.
     */
    public function extract_datetime_from_exif( string $file_path ): ?string {
        // PHP exif
        if ( function_exists( 'exif_read_data' ) ) {
            $exif = @exif_read_data( $file_path );
            $dt   = $exif['DateTimeOriginal'] ?? $exif['DateTimeDigitized'] ?? null;
            if ( $dt ) {
                // EXIF format: "Y:m:d H:i:s" → "Y-m-d H:i:s"
                return str_replace( ':', '-', substr( $dt, 0, 10 ) ) . substr( $dt, 10 );
            }
        }

        // exiftool fallback
        $exiftool = EdnaSurvey_Admin_Settings::resolve_command( 'cmd_exiftool' );
        if ( $exiftool ) {
            $output = array();
            $result = 0;
            @exec( escapeshellarg( $exiftool ) . ' -DateTimeOriginal -s3 ' . escapeshellarg( $file_path ) . ' 2>/dev/null', $output, $result );
            if ( 0 === $result && ! empty( $output[0] ) ) {
                $dt = trim( $output[0] );
                return str_replace( ':', '-', substr( $dt, 0, 10 ) ) . substr( $dt, 10 );
            }
        }

        return null;
    }

    // ── Existing methods ──────────────────────────────────────────────

    public function delete_site_photos( int $user_id, int $site_id ): void {
        $site_dir = $this->base_dir . '/' . $user_id . '/' . $site_id;
        if ( ! is_dir( $site_dir ) ) {
            return;
        }

        $files = glob( $site_dir . '/*' );
        if ( $files ) {
            foreach ( $files as $file ) {
                if ( is_file( $file ) ) {
                    unlink( $file );
                }
            }
        }
        rmdir( $site_dir );
    }
}
