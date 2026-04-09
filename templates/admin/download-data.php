<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Download Data', 'wp-ednasurvey' ); ?></h1>

    <form id="ednasurvey-download-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
        <?php wp_nonce_field( 'ednasurvey_nonce', 'nonce' ); ?>
        <input type="hidden" name="action" value="ednasurvey_download_data">

        <table class="form-table">
            <tr>
                <th scope="row"><label for="filter_user"><?php esc_html_e( 'User', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <select id="filter_user" name="user_id">
                        <option value=""><?php esc_html_e( 'All Users', 'wp-ednasurvey' ); ?></option>
                        <?php foreach ( $subscribers as $sub ) : ?>
                            <option value="<?php echo (int) $sub->ID; ?>">
                                <?php echo esc_html( $sub->display_name . ' (' . $sub->user_login . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="date_from"><?php esc_html_e( 'Date From', 'wp-ednasurvey' ); ?></label></th>
                <td><input type="date" id="date_from" name="date_from"></td>
            </tr>
            <tr>
                <th scope="row"><label for="date_to"><?php esc_html_e( 'Date To', 'wp-ednasurvey' ); ?></label></th>
                <td><input type="date" id="date_to" name="date_to"></td>
            </tr>
            <tr>
                <th scope="row"><label for="format"><?php esc_html_e( 'Format', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <select id="format" name="format">
                        <option value="csv">CSV</option>
                        <option value="tsv">TSV</option>
                    </select>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="type" value="data" class="button button-primary">
                <?php esc_html_e( 'Download Data', 'wp-ednasurvey' ); ?>
            </button>
            <button type="submit" name="type" value="photo_urls" class="button">
                <?php esc_html_e( 'Download Photo URL List', 'wp-ednasurvey' ); ?>
            </button>
        </p>
    </form>
</div>
