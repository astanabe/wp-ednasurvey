<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['map'];
$content_callback = function () use ( $username, $sites, $settings ) {

    ?>
    <div id="ednasurvey-user-map" style="height: 600px;"></div>

    <div class="ednasurvey-form-actions" style="margin-top: 1em;">
        <a href="<?php echo esc_url( home_url( '/' . $username . '/' ) ); ?>" class="button">
            <?php esc_html_e( 'Back to Dashboard', 'wp-ednasurvey' ); ?>
        </a>
    </div>

    <script>
        var ednasurveyUserSites = <?php echo wp_json_encode( array_map( function( $site ) {
            return array(
                'internal_sample_id' => $site->internal_sample_id,
                'lat'                => (float) $site->latitude,
                'lng'                => (float) $site->longitude,
                'name'               => EdnaSurvey_I18n::get_localized_field( $site->sitename_local ?? '', $site->sitename_en ?? '' ),
                'date'               => $site->survey_date ?? '',
                'time'               => $site->survey_time ?? '',
                'sample_id'          => $site->sample_id ?? '',
            );
        }, $sites ) ); ?>;
        var ednasurveyMapConfig = {
            resubmitBaseUrl: <?php echo wp_json_encode( home_url( '/' . $username . '/onlinesubmission?copy_from=' ) ); ?>,
            resubmitLabel: <?php echo wp_json_encode( __( 'Edit & Resubmit', 'wp-ednasurvey' ) ); ?>,
            detailBaseUrl: <?php echo wp_json_encode( home_url( '/' . $username . '/site/' ) ); ?>,
            detailLabel: <?php echo wp_json_encode( __( 'Detail', 'wp-ednasurvey' ) ); ?>
        };
    </script>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
