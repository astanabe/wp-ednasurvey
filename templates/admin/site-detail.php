<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$fields_config = $settings['default_fields_config'] ?? array();
$lang = EdnaSurvey_I18n::get_current_language();
$site_name = 'ja' === $lang ? ( $site->sitename_local ?: $site->sitename_en ) : ( $site->sitename_en ?: $site->sitename_local );
?>
<div class="wrap">
    <h1><?php echo esc_html( $site_name ?: __( 'Site Detail', 'wp-ednasurvey' ) ); ?></h1>

    <?php if ( ! empty( $photos ) ) : ?>
    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1.5em;">
        <?php foreach ( $photos as $photo ) : ?>
            <a href="<?php echo esc_url( $photo->file_url ); ?>" target="_blank">
                <img src="<?php echo esc_url( $photo->file_url ); ?>"
                     alt="<?php echo esc_attr( $photo->original_filename ); ?>"
                     style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;"
                     loading="lazy">
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <table class="form-table">
        <tr>
            <th><?php esc_html_e( 'Internal Sample ID', 'wp-ednasurvey' ); ?></th>
            <td style="word-break: break-all;"><code><?php echo esc_html( $site->internal_sample_id ); ?></code></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'User', 'wp-ednasurvey' ); ?></th>
            <td><?php echo esc_html( $user ? $user->user_login : '' ); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Submitted', 'wp-ednasurvey' ); ?></th>
            <td><?php echo esc_html( substr( $site->submitted_at ?? '', 0, 16 ) ); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Method', 'wp-ednasurvey' ); ?></th>
            <td><?php echo esc_html( $site->submitted_method ?? '' ); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'IP / Geo', 'wp-ednasurvey' ); ?></th>
            <td>
                <?php echo esc_html( $site->submitted_ip ?? '' ); ?>
                <?php if ( ! empty( $site->submitted_hostname ) ) : ?>
                    (<?php echo esc_html( $site->submitted_hostname ); ?>)
                <?php endif; ?>
                <?php if ( ! empty( $site->submitted_geo ) ) : ?>
                    <br><?php echo esc_html( $site->submitted_geo ); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'User Agent', 'wp-ednasurvey' ); ?></th>
            <td style="font-size: 0.85em; word-break: break-all;"><?php echo esc_html( $site->submitted_user_agent ?? '' ); ?></td>
        </tr>

        <?php if ( ! empty( $fields_config['survey_datetime'] ) ) : ?>
        <tr>
            <th><?php esc_html_e( 'Survey Date', 'wp-ednasurvey' ); ?></th>
            <td><?php echo esc_html( $site->survey_date ?? '' ); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Survey Time', 'wp-ednasurvey' ); ?></th>
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
            <th><?php printf( esc_html__( 'Collector %d', 'wp-ednasurvey' ), $i ); ?></th>
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

        <?php foreach ( $custom_data as $cd ) :
            $cf_label = 'ja' === $lang ? $cd['field']->label_ja : $cd['field']->label_en;
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
    </table>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=edna-survey-all-sites' ) ); ?>" class="button">
            &larr; <?php esc_html_e( 'All Sites', 'wp-ednasurvey' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=edna-survey-sites-map' ) ); ?>" class="button">
            &larr; <?php esc_html_e( 'Sites Map', 'wp-ednasurvey' ); ?>
        </a>
    </p>
</div>
