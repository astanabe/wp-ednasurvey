<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Add Users', 'wp-ednasurvey' ); ?></h1>

    <div id="ednasurvey-import-messages"></div>

    <p><?php esc_html_e( 'Upload a CSV or TSV file to bulk register users as Subscribers.', 'wp-ednasurvey' ); ?></p>
    <p><?php esc_html_e( 'File format: email, firstname, lastname (one user per row, with header row).', 'wp-ednasurvey' ); ?></p>
    <p><strong><?php esc_html_e( 'Note: Welcome emails will NOT be sent. Use a separate plugin to send welcome emails at the appropriate time.', 'wp-ednasurvey' ); ?></strong></p>

    <form id="ednasurvey-import-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'ednasurvey_nonce', 'nonce' ); ?>
        <input type="hidden" name="action" value="ednasurvey_import_users">

        <table class="form-table">
            <tr>
                <th scope="row"><label for="user_csv"><?php esc_html_e( 'CSV/TSV File', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="file" id="user_csv" name="user_csv" accept=".csv,.tsv,.txt" required>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e( 'Import Users', 'wp-ednasurvey' ); ?>
            </button>
        </p>
    </form>

    <div id="ednasurvey-import-results" style="display:none;">
        <h3><?php esc_html_e( 'Import Results', 'wp-ednasurvey' ); ?></h3>
        <div id="ednasurvey-import-summary"></div>
        <div id="ednasurvey-import-skipped"></div>
    </div>
</div>
