<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$fields_config = $settings['default_fields_config'] ?? array();
?>
<div class="wrap">
    <h1><?php esc_html_e( 'eDNA Survey Settings', 'wp-ednasurvey' ); ?></h1>

    <?php settings_errors( 'ednasurvey_settings' ); ?>

    <!-- Environment Check -->
    <h2><?php esc_html_e( 'Server Environment', 'wp-ednasurvey' ); ?></h2>
    <table class="widefat striped" style="max-width: 900px;">
        <thead>
            <tr>
                <th style="width: 40px;"><?php esc_html_e( 'Status', 'wp-ednasurvey' ); ?></th>
                <th style="width: 160px;"><?php esc_html_e( 'Component', 'wp-ednasurvey' ); ?></th>
                <th style="width: 160px;"><?php esc_html_e( 'Version', 'wp-ednasurvey' ); ?></th>
                <th><?php esc_html_e( 'Details', 'wp-ednasurvey' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $env_checks as $check ) :
                $icon  = 'ok' === $check['status'] ? '&#x2705;' : ( 'warning' === $check['status'] ? '&#x26A0;&#xFE0F;' : '&#x274C;' );
                $color = 'ok' === $check['status'] ? '#00a32a' : ( 'warning' === $check['status'] ? '#dba617' : '#d63638' );
            ?>
            <tr>
                <td style="text-align: center; font-size: 1.2em;"><?php echo $icon; ?></td>
                <td><strong><?php echo esc_html( $check['name'] ); ?></strong></td>
                <td><code><?php echo esc_html( $check['version'] ); ?></code></td>
                <td style="color: <?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $check['message'] ); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="description" style="max-width: 900px;">
        <?php esc_html_e( 'Items marked with a red X are required for core functionality. Items marked with a warning may limit certain features but the plugin will still operate.', 'wp-ednasurvey' ); ?>
    </p>
    <hr style="margin: 1.5em 0;">

    <form method="post">
        <?php wp_nonce_field( 'ednasurvey_save_settings', 'ednasurvey_settings_nonce' ); ?>

        <!-- Map Settings -->
        <h2><?php esc_html_e( 'Map Settings', 'wp-ednasurvey' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="tile_server_url"><?php esc_html_e( 'Tile Server URL', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="text" id="tile_server_url" name="tile_server_url" class="large-text"
                           value="<?php echo esc_attr( $settings['tile_server_url'] ?? '' ); ?>">
                    <p class="description"><?php esc_html_e( 'e.g. https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 'wp-ednasurvey' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="tile_attribution"><?php esc_html_e( 'Tile Attribution', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="text" id="tile_attribution" name="tile_attribution" class="large-text"
                           value="<?php echo esc_attr( $settings['tile_attribution'] ?? '' ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="map_center_lat"><?php esc_html_e( 'Default Center Latitude', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="map_center_lat" name="map_center_lat" step="0.000001"
                           value="<?php echo esc_attr( $settings['map_center_lat'] ?? 35.6762 ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="map_center_lng"><?php esc_html_e( 'Default Center Longitude', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="map_center_lng" name="map_center_lng" step="0.000001"
                           value="<?php echo esc_attr( $settings['map_center_lng'] ?? 139.6503 ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="map_default_zoom"><?php esc_html_e( 'Default Zoom Level', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="map_default_zoom" name="map_default_zoom" min="1" max="18"
                           value="<?php echo esc_attr( $settings['map_default_zoom'] ?? 5 ); ?>">
                </td>
            </tr>
        </table>

        <!-- Photo Settings -->
        <h2><?php esc_html_e( 'Photo Settings', 'wp-ednasurvey' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="photo_upload_limit"><?php esc_html_e( 'Photo Upload Limit (per site)', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="photo_upload_limit" name="photo_upload_limit" min="1" max="50"
                           value="<?php echo esc_attr( $settings['photo_upload_limit'] ?? 10 ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="photo_time_threshold"><?php esc_html_e( 'Photo Time Matching Threshold (minutes)', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="photo_time_threshold" name="photo_time_threshold" min="1" max="1440"
                           value="<?php echo esc_attr( $settings['photo_time_threshold'] ?? 30 ); ?>">
                    <p class="description"><?php esc_html_e( 'When photo_files is omitted in Excel, photos are matched to samples by comparing EXIF DateTimeOriginal with survey_date + survey_time within this threshold.', 'wp-ednasurvey' ); ?></p>
                </td>
            </tr>
        </table>

        <!-- External Command Paths -->
        <h2><?php esc_html_e( 'External Command Paths', 'wp-ednasurvey' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Specify full paths to external commands. Leave blank to auto-detect from PATH.', 'wp-ednasurvey' ); ?>
        </p>
        <table class="form-table">
            <?php
            $cmd_fields = array(
                'cmd_imagemagick'  => array(
                    'label' => __( 'ImageMagick (magick / convert)', 'wp-ednasurvey' ),
                    'desc'  => __( 'HEIC to JPEG conversion. Auto-detects "magick" then "convert".', 'wp-ednasurvey' ),
                    'placeholder' => '/usr/bin/magick',
                ),
                'cmd_heif_convert' => array(
                    'label' => __( 'heif-dec / heif-convert (libheif)', 'wp-ednasurvey' ),
                    'desc'  => __( 'HEIC to JPEG conversion. Auto-detects "heif-dec" then "heif-convert". Install: apt install libheif-examples', 'wp-ednasurvey' ),
                    'placeholder' => '/usr/bin/heif-dec',
                ),
                'cmd_ffmpeg' => array(
                    'label' => __( 'FFmpeg', 'wp-ednasurvey' ),
                    'desc'  => __( 'HEIC to JPEG conversion. Install: apt install ffmpeg', 'wp-ednasurvey' ),
                    'placeholder' => '/usr/bin/ffmpeg',
                ),
                'cmd_exiftool' => array(
                    'label' => __( 'exiftool', 'wp-ednasurvey' ),
                    'desc'  => __( 'GPS extraction fallback when PHP exif extension is unavailable. Install: apt install libimage-exiftool-perl', 'wp-ednasurvey' ),
                    'placeholder' => '/usr/bin/exiftool',
                ),
            );
            foreach ( $cmd_fields as $key => $field ) :
                $resolved = EdnaSurvey_Admin_Settings::resolve_command( $key );
            ?>
            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                <td>
                    <input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
                           class="large-text" value="<?php echo esc_attr( $settings[ $key ] ?? '' ); ?>"
                           placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>">
                    <p class="description">
                        <?php echo esc_html( $field['desc'] ); ?>
                        <?php if ( $resolved ) : ?>
                            <br><span style="color: #00a32a;">&#x2705; <?php
                                /* translators: %s: resolved path */
                                printf( esc_html__( 'Resolved: %s', 'wp-ednasurvey' ), esc_html( $resolved ) );
                            ?></span>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Default Field Configuration -->
        <h2><?php esc_html_e( 'Default Data Fields', 'wp-ednasurvey' ); ?></h2>
        <table class="form-table">
            <?php
            $default_fields = array(
                'survey_datetime' => __( 'Date & Time', 'wp-ednasurvey' ),
                'location'        => __( 'Location (Lat/Lon)', 'wp-ednasurvey' ),
                'site_name'       => __( 'Site Name (Local/EN)', 'wp-ednasurvey' ),
                'correspondence'  => __( 'Representative Name', 'wp-ednasurvey' ),
                'collectors'      => __( 'Collectors (up to 5)', 'wp-ednasurvey' ),
                'sample_id'       => __( 'Sample ID', 'wp-ednasurvey' ),
                'water_volume'    => __( 'Filtered Water Volume', 'wp-ednasurvey' ),
                'env_broad'       => __( 'Environment (Broad)', 'wp-ednasurvey' ),
                'weather'         => __( 'Weather', 'wp-ednasurvey' ),
                'wind'            => __( 'Wind', 'wp-ednasurvey' ),
                'notes'           => __( 'Notes/Remarks', 'wp-ednasurvey' ),
                'photos'          => __( 'Photos', 'wp-ednasurvey' ),
            );
            foreach ( $default_fields as $key => $label ) : ?>
            <tr>
                <th scope="row"><?php echo esc_html( $label ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="field_<?php echo esc_attr( $key ); ?>" value="1"
                               <?php checked( ! empty( $fields_config[ $key ] ) ); ?>>
                        <?php esc_html_e( 'Enabled', 'wp-ednasurvey' ); ?>
                    </label>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Custom Fields -->
        <h2><?php esc_html_e( 'Custom Data Fields', 'wp-ednasurvey' ); ?></h2>
        <div id="ednasurvey-custom-fields-builder">
            <table class="wp-list-table widefat" id="custom-fields-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Key', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Label (JA)', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Label (EN)', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Required', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Active', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Options (JSON)', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wp-ednasurvey' ); ?></th>
                    </tr>
                </thead>
                <tbody id="custom-fields-body">
                    <?php foreach ( $custom_fields as $cf ) : ?>
                    <tr class="custom-field-row" data-field-id="<?php echo (int) $cf->id; ?>">
                        <td><input type="text" class="cf-key" value="<?php echo esc_attr( $cf->field_key ); ?>"></td>
                        <td><input type="text" class="cf-label-ja" value="<?php echo esc_attr( $cf->label_ja ); ?>"></td>
                        <td><input type="text" class="cf-label-en" value="<?php echo esc_attr( $cf->label_en ); ?>"></td>
                        <td>
                            <select class="cf-type">
                                <option value="text" <?php selected( $cf->field_type, 'text' ); ?>>Text</option>
                                <option value="number" <?php selected( $cf->field_type, 'number' ); ?>>Number</option>
                                <option value="select" <?php selected( $cf->field_type, 'select' ); ?>>Select</option>
                                <option value="date" <?php selected( $cf->field_type, 'date' ); ?>>Date</option>
                                <option value="textarea" <?php selected( $cf->field_type, 'textarea' ); ?>>Textarea</option>
                            </select>
                        </td>
                        <td><input type="checkbox" class="cf-required" <?php checked( $cf->is_required ); ?>></td>
                        <td><input type="checkbox" class="cf-active" <?php checked( $cf->is_active ); ?>></td>
                        <td><input type="text" class="cf-options" value="<?php echo esc_attr( $cf->field_options ?? '' ); ?>"></td>
                        <td><button type="button" class="button button-small cf-remove"><?php esc_html_e( 'Remove', 'wp-ednasurvey' ); ?></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" id="add-custom-field" class="button">
                    <?php esc_html_e( 'Add Custom Field', 'wp-ednasurvey' ); ?>
                </button>
                <button type="button" id="save-custom-fields" class="button button-primary">
                    <?php esc_html_e( 'Save Custom Fields', 'wp-ednasurvey' ); ?>
                </button>
            </p>
        </div>

        <?php submit_button( __( 'Save Settings', 'wp-ednasurvey' ) ); ?>
    </form>
</div>
