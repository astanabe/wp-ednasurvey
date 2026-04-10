<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['offlinesubmission'];
$content_callback = function () use ( $username, $photo_limit, $photo_time_threshold ) {
    ?>
    <div id="ednasurvey-submission-messages"></div>

    <!-- Step 0: Number of sites -->
    <div id="ednasurvey-offline-step0">
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Number of Survey Sites', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="ednasurvey-num-sites"><?php esc_html_e( 'How many survey sites are included in this upload?', 'wp-ednasurvey' ); ?></label>
                <input type="number" id="ednasurvey-num-sites" min="1" max="200" value="1" style="width: 6em;">
            </div>
        </fieldset>
        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-step0-next" class="button button-primary">
                <?php esc_html_e( 'Next', 'wp-ednasurvey' ); ?>
            </button>
        </div>
    </div>

    <!-- Step 1: Photo upload -->
    <div id="ednasurvey-offline-step1" style="display:none;">
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Photos', 'wp-ednasurvey' ); ?></legend>
            <p class="description" id="ednasurvey-photo-limit-msg"></p>
            <p class="description"><?php esc_html_e( 'You can proceed without photos if none were taken.', 'wp-ednasurvey' ); ?></p>

            <div class="ednasurvey-file-select">
                <input type="file" id="ednasurvey-photos-input" multiple accept=".jpg,.jpeg,.heic,.heif">
            </div>

            <div id="ednasurvey-photo-list"></div>
        </fieldset>
        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-step1-next" class="button button-primary">
                <?php esc_html_e( 'Proceed to Excel Upload', 'wp-ednasurvey' ); ?>
            </button>
            <button type="button" id="ednasurvey-step1-back" class="button">
                <?php esc_html_e( 'Back', 'wp-ednasurvey' ); ?>
            </button>
        </div>
    </div>

    <!-- Step 2: Excel upload -->
    <div id="ednasurvey-offline-step2" style="display:none;">
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Excel File', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-file-select">
                <input type="file" id="ednasurvey-excel-input" accept=".xlsx">
            </div>
        </fieldset>
        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-step2-upload" class="button button-primary" disabled>
                <?php esc_html_e( 'Upload & Analyze', 'wp-ednasurvey' ); ?>
            </button>
            <button type="button" id="ednasurvey-step2-back" class="button">
                <?php esc_html_e( 'Back', 'wp-ednasurvey' ); ?>
            </button>
        </div>
    </div>

    <!-- Step 3: Map confirmation -->
    <div id="ednasurvey-offline-step3" style="display:none;">
        <h3><?php esc_html_e( 'Confirm Site Locations', 'wp-ednasurvey' ); ?></h3>
        <div id="ednasurvey-offline-map" style="height: 500px;"></div>
        <div id="ednasurvey-offline-sites-list" style="margin-top: 1em;"></div>

        <h3><?php esc_html_e( 'Review Data', 'wp-ednasurvey' ); ?></h3>
        <div id="ednasurvey-offline-data-review" style="margin-bottom: 1em;"></div>

        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-offline-confirm" class="button button-primary">
                <?php esc_html_e( 'Submit All Sites', 'wp-ednasurvey' ); ?>
            </button>
            <button type="button" id="ednasurvey-step3-back" class="button">
                <?php esc_html_e( 'Back', 'wp-ednasurvey' ); ?>
            </button>
        </div>
    </div>

    <div class="ednasurvey-form-actions" style="margin-top: 2em;">
        <a href="<?php echo esc_url( home_url( '/' . $username . '/' ) ); ?>" class="button">
            <?php esc_html_e( 'Back to Dashboard', 'wp-ednasurvey' ); ?>
        </a>
    </div>

    <script>
        var ednasurveyOfflineConfig = {
            photoLimit: <?php echo (int) $photo_limit; ?>,
            username: <?php echo wp_json_encode( $username ); ?>
        };
    </script>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
