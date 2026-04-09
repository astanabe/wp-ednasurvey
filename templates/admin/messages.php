<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Messages', 'wp-ednasurvey' ); ?></h1>

    <?php if ( empty( $conversations ) ) : ?>
        <p><?php esc_html_e( 'No conversations yet.', 'wp-ednasurvey' ); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'User', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Last Message', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Last Activity', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Unread', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'wp-ednasurvey' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $conversations as $conv ) : ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html( $conv->display_name ); ?></strong>
                        <br><small><?php echo esc_html( $conv->user_login ); ?></small>
                    </td>
                    <td><?php echo esc_html( wp_trim_words( $conv->last_message, 20 ) ); ?></td>
                    <td><?php echo esc_html( $conv->last_message_at ); ?></td>
                    <td>
                        <?php if ( (int) $conv->unread_count > 0 ) : ?>
                            <span class="ednasurvey-badge"><?php echo (int) $conv->unread_count; ?></span>
                        <?php else : ?>
                            0
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=edna-survey-messages&user_id=' . $conv->conversation_user_id ) ); ?>"
                           class="button button-small">
                            <?php esc_html_e( 'View Chat', 'wp-ednasurvey' ); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
