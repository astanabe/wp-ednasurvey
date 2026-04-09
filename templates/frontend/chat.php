<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['chat'];
$content_callback = function () use ( $username, $messages, $target_user ) {
    $current_user = wp_get_current_user();
    ?>
    <div id="ednasurvey-chat-container" class="ednasurvey-chat-container">
        <div id="ednasurvey-chat-messages" class="ednasurvey-chat-messages">
            <?php if ( empty( $messages ) ) : ?>
                <p class="ednasurvey-chat-empty"><?php esc_html_e( 'No messages yet. Start a conversation!', 'wp-ednasurvey' ); ?></p>
            <?php else : ?>
                <?php foreach ( $messages as $msg ) : ?>
                    <div class="ednasurvey-chat-message <?php echo (int) $msg->sender_id === $current_user->ID ? 'sent' : 'received'; ?>">
                        <div class="ednasurvey-chat-meta">
                            <strong><?php echo esc_html( $msg->sender_name ); ?></strong>
                            <time><?php echo esc_html( $msg->created_at ); ?></time>
                        </div>
                        <div class="ednasurvey-chat-text"><?php echo nl2br( esc_html( $msg->message ) ); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form id="ednasurvey-chat-form" class="ednasurvey-chat-form">
            <?php wp_nonce_field( 'ednasurvey_nonce', 'nonce' ); ?>
            <input type="hidden" name="action" value="ednasurvey_send_message">
            <input type="hidden" name="conversation_user_id" value="<?php echo (int) $target_user->ID; ?>">
            <div class="ednasurvey-chat-input-row">
                <textarea id="ednasurvey-chat-input" name="message" rows="2"
                          placeholder="<?php esc_attr_e( 'Type a message...', 'wp-ednasurvey' ); ?>"></textarea>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Send', 'wp-ednasurvey' ); ?></button>
            </div>
        </form>
    </div>

    <div class="ednasurvey-form-actions">
        <a href="<?php echo esc_url( home_url( '/' . $username . '/' ) ); ?>" class="button">
            <?php esc_html_e( 'Back to Dashboard', 'wp-ednasurvey' ); ?>
        </a>
    </div>

    <script>
        var ednasurveyChat = {
            conversationUserId: <?php echo (int) $target_user->ID; ?>,
            currentUserId: <?php echo (int) $current_user->ID; ?>,
            lastMessageId: <?php echo ! empty( $messages ) ? (int) end( $messages )->id : 0; ?>
        };
    </script>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
