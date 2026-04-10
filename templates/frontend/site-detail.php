<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$lang          = EdnaSurvey_I18n::get_current_language();
$fields_config = $settings['default_fields_config'] ?? array();
$site_name     = 'ja' === $lang ? ( $site->sitename_local ?: $site->sitename_en ) : ( $site->sitename_en ?: $site->sitename_local );
$page_title    = $site_name ?: EdnaSurvey_Router::get_page_titles()['sitedetail'];

$content_callback = function () use ( $username, $site, $photos, $custom_data, $fields_config, $lang, $site_name ) {
    $is_admin = current_user_can( 'manage_options' );
    ?>
    <div class="ednasurvey-site-detail">

        <?php if ( ! empty( $photos ) ) : ?>
        <div class="ednasurvey-site-detail-photos">
            <?php foreach ( $photos as $photo ) : ?>
                <a href="<?php echo esc_url( $photo->file_url ); ?>" target="_blank">
                    <img src="<?php echo esc_url( $photo->file_url ); ?>"
                         alt="<?php echo esc_attr( $photo->original_filename ); ?>"
                         loading="lazy">
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <table class="ednasurvey-site-detail-table">
            <?php if ( ! empty( $fields_config['survey_datetime'] ) ) : ?>
            <tr>
                <th><?php esc_html_e( 'Date', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->survey_date ?? '' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Time', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( substr( $site->survey_time ?? '', 0, 5 ) ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $fields_config['location'] ) ) : ?>
            <tr>
                <th><?php esc_html_e( 'Latitude', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->latitude ?? '' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Longitude', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->longitude ?? '' ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $fields_config['site_name'] ) ) : ?>
            <tr>
                <th><?php esc_html_e( 'Site Name (Local)', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->sitename_local ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Site Name (EN)', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->sitename_en ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $fields_config['correspondence'] ) ) : ?>
            <tr>
                <th><?php esc_html_e( 'Representative', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->correspondence ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $fields_config['collectors'] ) ) : ?>
            <?php for ( $i = 1; $i <= 5; $i++ ) :
                $col = 'collector' . $i;
                if ( ! empty( $site->$col ) ) : ?>
            <tr>
                <th><?php
                    /* translators: %d: collector number */
                    printf( esc_html__( 'Collector %d', 'wp-ednasurvey' ), $i );
                ?></th>
                <td><?php echo esc_html( $site->$col ); ?></td>
            </tr>
            <?php endif; endfor; endif; ?>

            <?php if ( ! empty( $fields_config['sample_id'] ) ) : ?>
            <tr>
                <th><?php esc_html_e( 'Sample ID', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->sample_id ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $fields_config['water_volume'] ) ) : ?>
            <tr>
                <th><?php esc_html_e( 'Filtered Water Vol. 1 (mL)', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->watervol1 ?? '' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Filtered Water Vol. 2 (mL)', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->watervol2 ?? '' ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $fields_config['env_broad'] ) && ! empty( $site->env_broad ) ) : ?>
            <tr>
                <th><?php echo esc_html( 'ja' === $lang ? '環境(大)' : 'Environment (Broad)' ); ?></th>
                <td><?php echo esc_html( EdnaSurvey_I18n::get_choice_label( EdnaSurvey_I18n::get_env_broad_choices(), $site->env_broad, $lang ) ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $fields_config['env_broad'] ) ) :
                $env_local_all = EdnaSurvey_I18n::get_env_local_choices();
                $env_locals = array();
                for ( $eli = 1; $eli <= 7; $eli++ ) {
                    $f = 'env_local' . $eli;
                    if ( ! empty( $site->$f ) ) {
                        $env_locals[] = EdnaSurvey_I18n::get_choice_label( $env_local_all, $site->$f, $lang );
                    }
                }
                if ( ! empty( $env_locals ) ) : ?>
            <tr>
                <th><?php echo esc_html( 'ja' === $lang ? '環境(小)' : 'Environment (Local)' ); ?></th>
                <td><?php echo esc_html( implode( ' | ', $env_locals ) ); ?></td>
            </tr>
            <?php endif; endif; ?>

            <?php if ( ! empty( $fields_config['weather'] ) && ! empty( $site->weather ) ) : ?>
            <tr>
                <th><?php echo esc_html( 'ja' === $lang ? '天候' : 'Weather' ); ?></th>
                <td><?php echo esc_html( EdnaSurvey_I18n::get_choice_label( EdnaSurvey_I18n::get_weather_choices(), $site->weather, $lang ) ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $fields_config['wind'] ) && ! empty( $site->wind ) ) : ?>
            <tr>
                <th><?php echo esc_html( 'ja' === $lang ? '風' : 'Wind' ); ?></th>
                <td><?php echo esc_html( EdnaSurvey_I18n::get_choice_label( EdnaSurvey_I18n::get_wind_choices(), $site->wind, $lang ) ); ?></td>
            </tr>
            <?php endif; ?>

            <?php foreach ( $custom_data as $cd ) :
                $cf_label = 'ja' === $lang ? $cd['label']->label_ja : $cd['label']->label_en;
            ?>
            <tr>
                <th><?php echo esc_html( $cf_label ); ?></th>
                <td><?php echo esc_html( $cd['value'] ); ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if ( ! empty( $fields_config['notes'] ) && ! empty( $site->notes ) ) : ?>
            <tr>
                <th><?php esc_html_e( 'Notes', 'wp-ednasurvey' ); ?></th>
                <td><?php echo nl2br( esc_html( $site->notes ) ); ?></td>
            </tr>
            <?php endif; ?>

            <?php if ( ! empty( $photos ) ) : ?>
            <tr>
                <th><?php esc_html_e( 'Photos', 'wp-ednasurvey' ); ?></th>
                <td><?php echo (int) count( $photos ); ?> <?php esc_html_e( 'file(s)', 'wp-ednasurvey' ); ?></td>
            </tr>
            <?php endif; ?>

            <?php // Submission metadata — admin only
            if ( $is_admin ) : ?>
            <tr><td colspan="2"><hr></td></tr>
            <tr>
                <th><?php esc_html_e( 'Internal Sample ID', 'wp-ednasurvey' ); ?></th>
                <td style="word-break: break-all;"><?php echo esc_html( $site->internal_sample_id ?? '' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Submitted', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->submitted_at ?? '' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Method', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->submitted_method ?? '' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'IP / Geo', 'wp-ednasurvey' ); ?></th>
                <td><?php echo esc_html( $site->submitted_ip ?? '' );
                    if ( ! empty( $site->submitted_geo ) ) {
                        echo '<br>' . esc_html( $site->submitted_geo );
                    } ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="ednasurvey-form-actions" style="margin-top: 1.5em;">
            <a href="<?php echo esc_url( home_url( '/' . $username . '/onlinesubmission?copy_from=' . $site->internal_sample_id ) ); ?>"
               class="button button-primary">
                <?php esc_html_e( 'Edit & Resubmit', 'wp-ednasurvey' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/' . $username . '/sites' ) ); ?>" class="button">
                <?php esc_html_e( 'Back to Site List', 'wp-ednasurvey' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/' . $username . '/map' ) ); ?>" class="button">
                <?php esc_html_e( 'Back to Map', 'wp-ednasurvey' ); ?>
            </a>
        </div>
    </div>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
