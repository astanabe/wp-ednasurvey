<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['sites'];
$content_callback = function () use ( $username, $sites, $settings ) {
    $fields_config = $settings['default_fields_config'] ?? array();
    ?>
    <?php if ( empty( $sites ) ) : ?>
        <p><?php esc_html_e( 'You have not submitted any survey sites yet.', 'wp-ednasurvey' ); ?></p>
    <?php else : ?>
        <div class="ednasurvey-site-cards">
            <?php foreach ( $sites as $i => $site ) : ?>
            <div class="ednasurvey-site-card">
                <?php if ( ! empty( $site->photos ) ) : ?>
                <div class="ednasurvey-site-card-thumbs">
                    <?php foreach ( array_slice( $site->photos, 0, 3 ) as $photo ) : ?>
                        <img src="<?php echo esc_url( $photo->file_url ); ?>"
                             alt="<?php echo esc_attr( $photo->original_filename ); ?>"
                             loading="lazy">
                    <?php endforeach; ?>
                    <?php if ( count( $site->photos ) > 3 ) : ?>
                        <span class="ednasurvey-thumb-more">+<?php echo (int) ( count( $site->photos ) - 3 ); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="ednasurvey-site-card-body">
                    <span class="ednasurvey-site-card-num">#<?php echo (int) ( $i + 1 ); ?></span>
                    <?php if ( ! empty( $fields_config['site_name'] ) ) : ?>
                        <strong><?php echo esc_html( EdnaSurvey_I18n::get_localized_field( $site->sitename_local ?? '', $site->sitename_en ?? '' ) ); ?></strong>
                    <?php endif; ?>
                    <?php if ( ! empty( $fields_config['survey_datetime'] ) ) : ?>
                        <span class="ednasurvey-site-card-detail"><?php echo esc_html( ( $site->survey_date ?? '' ) . ' ' . substr( $site->survey_time ?? '', 0, 5 ) ); ?></span>
                    <?php endif; ?>
                    <?php if ( ! empty( $fields_config['sample_id'] ) && ! empty( $site->sample_id ) ) : ?>
                        <span class="ednasurvey-site-card-detail"><?php esc_html_e( 'Sample ID', 'wp-ednasurvey' ); ?>: <?php echo esc_html( $site->sample_id ); ?></span>
                    <?php endif; ?>
                    <?php if ( ! empty( $fields_config['location'] ) && ! empty( $site->latitude ) ) : ?>
                        <span class="ednasurvey-site-card-detail"><?php echo esc_html( $site->latitude . ', ' . $site->longitude ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="ednasurvey-site-card-actions">
                    <a href="<?php echo esc_url( home_url( '/' . $username . '/site/' . $site->internal_sample_id ) ); ?>"
                       class="button">
                        <?php esc_html_e( 'Detail', 'wp-ednasurvey' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/' . $username . '/onlinesubmission?copy_from=' . $site->internal_sample_id ) ); ?>"
                       class="button ednasurvey-resubmit-btn">
                        <?php esc_html_e( 'Edit & Resubmit', 'wp-ednasurvey' ); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="ednasurvey-form-actions">
        <a href="<?php echo esc_url( home_url( '/' . $username . '/' ) ); ?>" class="button">
            <?php esc_html_e( 'Back to Dashboard', 'wp-ednasurvey' ); ?>
        </a>
    </div>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
