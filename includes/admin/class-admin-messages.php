<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Admin_Messages {

    public function render(): void {
        $chat_model = new EdnaSurvey_Chat_Model();

        // If viewing a specific user's chat
        $view_user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

        if ( $view_user_id ) {
            $current_user = wp_get_current_user();
            $chat_model->mark_as_read( $view_user_id, $current_user->ID );
            $messages    = $chat_model->get_messages( $view_user_id );
            $target_user = get_user_by( 'id', $view_user_id );
            include EDNASURVEY_PLUGIN_DIR . 'templates/admin/messages-chat.php';
            return;
        }

        $conversations = $chat_model->get_conversations();
        include EDNASURVEY_PLUGIN_DIR . 'templates/admin/messages.php';
    }
}
