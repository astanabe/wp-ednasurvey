<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['offlinesubmission'];
$content_callback = function () use ( $username, $photo_limit, $photo_time_threshold ) {
    $lang = EdnaSurvey_I18n::get_current_language();
    ?>
    <div id="ednasurvey-submission-messages"></div>

    <!-- Step 0: Number of sites -->
    <div id="ednasurvey-offline-step0">
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( 'ja' === $lang ? '報告地点数' : 'Number of Survey Sites' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="ednasurvey-num-sites"><?php echo esc_html( 'ja' === $lang ? '今回アップロードする地点数を入力してください' : 'How many survey sites are included in this upload?' ); ?></label>
                <input type="number" id="ednasurvey-num-sites" min="1" max="200" value="1" style="width: 6em;">
            </div>
        </fieldset>
        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-step0-next" class="button button-primary">
                <?php echo esc_html( 'ja' === $lang ? '次へ' : 'Next' ); ?>
            </button>
        </div>
    </div>

    <!-- Step 1: Photo upload -->
    <div id="ednasurvey-offline-step1" style="display:none;">
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( 'ja' === $lang ? '写真' : 'Photos' ); ?></legend>
            <p class="description" id="ednasurvey-photo-limit-msg"></p>
            <p class="description"><?php echo esc_html( 'ja' === $lang ? '写真を撮影しなかった場合はそのまま次に進めます。' : 'You can proceed without photos if none were taken.' ); ?></p>

            <div class="ednasurvey-file-select">
                <input type="file" id="ednasurvey-photos-input" multiple accept=".jpg,.jpeg,.heic,.heif">
            </div>

            <div id="ednasurvey-photo-list"></div>
        </fieldset>
        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-step1-next" class="button button-primary">
                <?php echo esc_html( 'ja' === $lang ? 'Excelアップロードへ' : 'Proceed to Excel Upload' ); ?>
            </button>
            <button type="button" id="ednasurvey-step1-back" class="button">
                <?php echo esc_html( 'ja' === $lang ? '戻る' : 'Back' ); ?>
            </button>
        </div>
    </div>

    <!-- Step 2: Excel upload -->
    <div id="ednasurvey-offline-step2" style="display:none;">
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( 'ja' === $lang ? 'Excelファイル' : 'Excel File' ); ?></legend>
            <div class="ednasurvey-file-select">
                <input type="file" id="ednasurvey-excel-input" accept=".xlsx">
            </div>
        </fieldset>
        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-step2-upload" class="button button-primary" disabled>
                <?php echo esc_html( 'ja' === $lang ? 'アップロードして解析' : 'Upload & Analyze' ); ?>
            </button>
            <button type="button" id="ednasurvey-step2-back" class="button">
                <?php echo esc_html( 'ja' === $lang ? '戻る' : 'Back' ); ?>
            </button>
        </div>
    </div>

    <!-- Step 3: Map confirmation -->
    <div id="ednasurvey-offline-step3" style="display:none;">
        <h3><?php echo esc_html( 'ja' === $lang ? '地点の位置を確認' : 'Confirm Site Locations' ); ?></h3>
        <div id="ednasurvey-offline-map" style="height: 500px;"></div>
        <div id="ednasurvey-offline-sites-list" style="margin-top: 1em;"></div>

        <h3><?php echo esc_html( 'ja' === $lang ? 'データの確認' : 'Review Data' ); ?></h3>
        <div id="ednasurvey-offline-data-review" style="margin-bottom: 1em;"></div>

        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-offline-confirm" class="button button-primary">
                <?php echo esc_html( 'ja' === $lang ? '全地点を送信' : 'Submit All Sites' ); ?>
            </button>
            <button type="button" id="ednasurvey-step3-back" class="button">
                <?php echo esc_html( 'ja' === $lang ? '戻る' : 'Back' ); ?>
            </button>
        </div>
    </div>

    <div class="ednasurvey-form-actions" style="margin-top: 2em;">
        <a href="<?php echo esc_url( home_url( '/' . $username . '/' ) ); ?>" class="button">
            <?php echo esc_html( 'ja' === $lang ? 'ダッシュボードに戻る' : 'Back to Dashboard' ); ?>
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
