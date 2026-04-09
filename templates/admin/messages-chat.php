<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$current_user = wp_get_current_user();
?>
<div class="wrap">
    <h1>
        <?php
        /* translators: %s: user display name */
        printf( esc_html__( 'Chat with %s', 'wp-ednasurvey' ), esc_html( $target_user->display_name ) );
        ?>
    </h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=edna-survey-messages' ) ); ?>" class="button">
            &larr; <?php esc_html_e( 'Back to All Messages', 'wp-ednasurvey' ); ?>
        </a>
    </p>

    <div id="ednasurvey-admin-chat-container" class="ednasurvey-chat-container" style="max-width: 800px;">
        <div id="ednasurvey-chat-messages" class="ednasurvey-chat-messages" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 1em; margin-bottom: 1em;">
            <?php if ( empty( $messages ) ) : ?>
                <p class="ednasurvey-chat-empty"><?php esc_html_e( 'No messages yet.', 'wp-ednasurvey' ); ?></p>
            <?php else : ?>
                <?php foreach ( $messages as $msg ) : ?>
                    <div class="ednasurvey-chat-message <?php echo (int) $msg->sender_id === $current_user->ID ? 'sent' : 'received'; ?>"
                         style="margin-bottom: 0.75em; padding: 0.5em; border-radius: 4px; <?php echo (int) $msg->sender_id === $current_user->ID ? 'background: #e8f4fd; text-align: right;' : 'background: #f0f0f0;'; ?>">
                        <div class="ednasurvey-chat-meta" style="font-size: 0.85em; color: #666;">
                            <strong><?php echo esc_html( $msg->sender_name ); ?></strong>
                            &mdash; <time><?php echo esc_html( $msg->created_at ); ?></time>
                        </div>
                        <div class="ednasurvey-chat-text"><?php echo nl2br( esc_html( $msg->message ) ); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form id="ednasurvey-admin-chat-form">
            <?php wp_nonce_field( 'ednasurvey_nonce', 'nonce' ); ?>
            <input type="hidden" name="action" value="ednasurvey_send_message">
            <input type="hidden" name="conversation_user_id" value="<?php echo (int) $target_user->ID; ?>">
            <div style="display: flex; gap: 0.5em;">
                <textarea id="ednasurvey-chat-input" name="message" rows="2" style="flex: 1;"
                          placeholder="<?php esc_attr_e( 'Type a reply...', 'wp-ednasurvey' ); ?>"></textarea>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Send', 'wp-ednasurvey' ); ?></button>
            </div>
        </form>
    </div>

    <script>
        var ednasurveyChat = {
            conversationUserId: <?php echo (int) $target_user->ID; ?>,
            currentUserId: <?php echo (int) $current_user->ID; ?>,
            lastMessageId: <?php echo ! empty( $messages ) ? (int) end( $messages )->id : 0; ?>
        };
    </script>
</div>
