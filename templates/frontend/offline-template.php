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

        <div class="ednasurvey-form-actions">
            <a href="<?php echo esc_url( $download_url ); ?>" class="button button-primary">
                <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Download Excel Template (.xlsx)', 'wp-ednasurvey' ); ?>
            </a>
        </div>

        <h3><?php esc_html_e( 'Instructions', 'wp-ednasurvey' ); ?></h3>
        <ol>
            <li><?php esc_html_e( 'Each row represents one survey site.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'Latitude and longitude can be left blank if your photos contain GPS data.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'Enter photo filenames in the photo column (matching the actual file names).', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'If you leave the photo filename column blank, photos will be automatically matched by comparing EXIF shooting time with the survey time you entered.', 'wp-ednasurvey' ); ?></li>
            <li><?php esc_html_e( 'Save the file and upload it together with your photos.', 'wp-ednasurvey' ); ?></li>
        </ol>

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
