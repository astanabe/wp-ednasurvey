<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Sites Map', 'wp-ednasurvey' ); ?></h1>
    <div id="ednasurvey-admin-map" style="height: 600px; margin-top: 1em;"></div>
</div>

<script>
    var ednasurveyAdminSites = <?php echo wp_json_encode( array_values( array_filter( array_map( function( $site ) {
        if ( empty( $site->latitude ) || empty( $site->longitude ) ) {
            return null;
        }
        return array(
            'lat'                => (float) $site->latitude,
            'lng'                => (float) $site->longitude,
            'name'               => EdnaSurvey_I18n::get_localized_field( $site->sitename_local ?? '', $site->sitename_en ?? '' ),
            'date'               => $site->survey_date ?? '',
            'user_login'         => $site->user_login ?? '',
            'correspondence'     => $site->correspondence ?? '',
            'detail_url' => admin_url( 'admin.php?page=edna-survey-site-detail&site=' . rawurlencode( $site->internal_sample_id ) ),
        );
    }, $sites ) ) ) ); ?>;
    var ednasurveyAdminMapI18n = {
        detail: <?php echo wp_json_encode( __( 'Detail', 'wp-ednasurvey' ) ); ?>
    };
</script>
