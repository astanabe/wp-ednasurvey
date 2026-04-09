<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Chat_Controller {

    public function render( WP_User $target_user ): void {
        $username = $target_user->user_login;
        $chat_model = new EdnaSurvey_Chat_Model();
        $current_user = wp_get_current_user();

        // Mark messages as read
        $chat_model->mark_as_read( $target_user->ID, $current_user->ID );

        // Get existing messages
        $messages = $chat_model->get_messages( $target_user->ID );

        include EDNASURVEY_PLUGIN_DIR . 'templates/frontend/chat.php';
    }
}
