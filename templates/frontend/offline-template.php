<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['offlinetemplate'];
$content_callback = function () use ( $username ) {
    $download_url = home_url( '/' . $username . '/offlinetemplate?download=1&_wpnonce=' . wp_create_nonce( 'ednasurvey_download_template' ) );
    ?>
    <div class="ednasurvey-template-download">
        <p><?php esc_html_e( 'Download an Excel template for recording survey data in the field without internet connectivity.', 'wp-ednasurvey' ); ?></p>
        <p><?php esc_html_e( 'Fill in the template during your field survey, then upload it along with your photos using the "Upload Offline Data" page.', 'wp-ednasurvey' ); ?></p>

        <div class="ednasurvey-form-actions" style="margin-bottom: 0.5em;">
            <a href="<?php echo esc_url( $download_url ); ?>" class="button button-primary">
                <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Download Excel Template (.xlsx)', 'wp-ednasurvey' ); ?>
            </a>
        </div>
        <p class="ednasurvey-help" style="margin-bottom: 1.5em;">
            <?php echo esc_html( 'ja' === EdnaSurvey_I18n::get_current_language()
                ? 'ダウンロード開始まで時間がかかることがあります。'
                : 'It may take a moment before the download starts.' ); ?>
        </p>

        <h3><?php esc_html_e( 'Instructions', 'wp-ednasurvey' ); ?></h3>
        <ol>
            <li><?php esc_html_e( 'Each row represents one survey site.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'Latitude and longitude can be left blank if your photos contain GPS data.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'Enter photo filenames in the photo column (matching the actual file names).', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'If you leave the photo filename column blank, photos will be automatically matched by comparing EXIF shooting time with the survey time you entered.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'Save the file and upload it together with your photos.', 'wp-ednasurvey' ); ?></li>
        </ol>

        <h3><?php echo esc_html( 'ja' === EdnaSurvey_I18n::get_current_language() ? '環境・天候・風について' : 'About Environment, Weather, and Wind' ); ?></h3>
        <ul>
            <li><?php echo esc_html( 'ja' === EdnaSurvey_I18n::get_current_language()
                ? '「環境(大)」欄の「河川感潮域」: 河口から外は近くても含まない。'
                : '"estuarine" in the Environment (Broad) field: does not include areas outside the river mouth, even if nearby.' ); ?></li>
            <li><?php echo esc_html( 'ja' === EdnaSurvey_I18n::get_current_language()
                ? '「環境(大)」欄の「マングローブ」: 河川感潮域のマングローブはマングローブを選択。'
                : '"mangrove" in the Environment (Broad) field: mangroves in estuarine areas should be classified as mangrove.' ); ?></li>
            <li><?php echo esc_html( 'ja' === EdnaSurvey_I18n::get_current_language()
                ? '「環境(大)」欄の「大河川下流部」の判定基準: 遊覧船が運行できるかどうか（急流下り船は含まない）。'
                : '"large river" in the Environment (Broad) field: whether a sightseeing boat can operate (rapids boats do not count).' ); ?></li>
            <li><?php echo esc_html( 'ja' === EdnaSurvey_I18n::get_current_language()
                ? '「環境(大)」欄の「塩湖」: 汽水湖や潟湖は含まない。'
                : '"saline lake" in the Environment (Broad) field: does not include brackish lakes or lagoons.' ); ?></li>
            <li><?php echo esc_html( 'ja' === EdnaSurvey_I18n::get_current_language()
                ? '「環境(大)」欄の「滅菌水」: ブランク・ネガティブコントロール用。'
                : '"sterile water" in the Environment (Broad) field: for blanks / negative controls.' ); ?></li>
            <li><?php echo esc_html( 'ja' === EdnaSurvey_I18n::get_current_language()
                ? '「風」欄の「強風」の判定基準: 濾過に使用するシリンジまたはフィルターホルダーが風で継続的に動いていくかどうかを基準に選択してください。'
                : '"windy" in the Wind field: select "windy" if a syringe or filter holder used for filtration is continuously moved by the wind.' ); ?></li>
        </ul>

        <h3><?php esc_html_e( 'Note on Photo Filenames (Android)', 'wp-ednasurvey' ); ?></h3>
        <p><?php esc_html_e( 'On Android devices, the filename shown in the file manager may differ from the filename the browser sends during upload. If you enter photo filenames in the Excel template, use the filenames displayed on the upload screen (Step 1), not the names shown in your file manager. Alternatively, leave the photo filename column blank and let the system match photos automatically by EXIF shooting time.', 'wp-ednasurvey' ); ?></p>

        <h3><?php esc_html_e( 'Using on a Smartphone', 'wp-ednasurvey' ); ?></h3>

        <h4><?php esc_html_e( 'Google Sheets (Android / iPhone)', 'wp-ednasurvey' ); ?></h4>
        <ol>
            <li><?php esc_html_e( 'Install the Google Sheets app and sign in with your Google account.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'While online, download this template and open it in Google Sheets.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'Verify that the file opens correctly and the column headers are displayed.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'To use offline: open the file, tap the three-dot menu (⋮), and enable "Make available offline".', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'You can now enter data in the field without internet. Changes will sync automatically when you reconnect.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'After syncing, download the file as .xlsx from Google Sheets (File → Download → Microsoft Excel) and upload it here.', 'wp-ednasurvey' ); ?></li>
        </ol>

        <h4><?php esc_html_e( 'Microsoft Excel (Android / iPhone)', 'wp-ednasurvey' ); ?></h4>
        <ol>
            <li><?php esc_html_e( 'Install the Microsoft Excel app and sign in (a free Microsoft account is sufficient).', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'While online, download this template and open it in Excel.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'Verify that the file opens correctly and the column headers are displayed.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'If you save the file locally on the device, it can be edited without internet but make sure to back up the file.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'If you save the file to OneDrive, it can be edited on your phone. Note that offline editing requires a Microsoft 365 subscription.', 'wp-ednasurvey' ); ?></li>
        </ol>
    </div>

    <div class="ednasurvey-form-actions">
        <a href="<?php echo esc_url( home_url( '/' . $username . '/' ) ); ?>" class="button">
            <?php esc_html_e( 'Back to Dashboard', 'wp-ednasurvey' ); ?>
        </a>
    </div>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
