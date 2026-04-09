<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Ajax_Chat extends EdnaSurvey_Ajax_Handler {

    public function register(): void {
        add_action( 'wp_ajax_ednasurvey_send_message', array( $this, 'handle_send_message' ) );
    }

    public function register_rest_routes(): void {
        register_rest_route( 'ednasurvey/v1', '/messages/(?P<user_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_messages' ),
            'permission_callback' => array( $this, 'check_message_permission' ),
            'args'                => array(
                'user_id' => array( 'type' => 'integer', 'required' => true ),
                'after'   => array( 'type' => 'integer', 'default' => 0 ),
            ),
        ) );

        register_rest_route( 'ednasurvey/v1', '/messages/conversations', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_conversations' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    public function check_message_permission( WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        $target_user_id = (int) $request->get_param( 'user_id' );
        $current_user   = wp_get_current_user();
        return $current_user->ID === $target_user_id || current_user_can( 'manage_options' );
    }

    public function rest_get_messages( WP_REST_Request $request ): WP_REST_Response {
        $user_id    = (int) $request->get_param( 'user_id' );
        $after_id   = (int) $request->get_param( 'after' );
        $chat_model = new EdnaSurvey_Chat_Model();

        // Mark as read
        $current_user = wp_get_current_user();
        $chat_model->mark_as_read( $user_id, $current_user->ID );

        $messages = $chat_model->get_messages( $user_id, $after_id );

        return new WP_REST_Response( array( 'messages' => $messages ), 200 );
    }

    public function rest_get_conversations( WP_REST_Request $request ): WP_REST_Response {
        $chat_model    = new EdnaSurvey_Chat_Model();
        $conversations = $chat_model->get_conversations();
        return new WP_REST_Response( array( 'conversations' => $conversations ), 200 );
    }

    public function handle_send_message(): void {
        $this->verify_nonce();
        $user = $this->require_login();

        $conversation_user_id = isset( $_POST['conversation_user_id'] ) ? absint( $_POST['conversation_user_id'] ) : 0;
        $message              = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        if ( empty( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'Message cannot be empty.', 'wp-ednasurvey' ) ) );
        }

        // Permission: user can only post to their own conversation, admin to any
        if ( $user->ID !== $conversation_user_id && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-ednasurvey' ) ), 403 );
        }

        $chat_model = new EdnaSurvey_Chat_Model();
        $msg_id     = $chat_model->insert( array(
            'conversation_user_id' => $conversation_user_id,
            'sender_id'            => $user->ID,
            'message'              => $message,
        ) );

        if ( ! $msg_id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to send message.', 'wp-ednasurvey' ) ) );
        }

        // Notify admin if sender is a subscriber
        if ( ! current_user_can( 'manage_options' ) ) {
            $notification = new EdnaSurvey_Notification_Service();
            $notification->notify_admin_new_message( $user->ID, $message );
        }

        wp_send_json_success( array(
            'message_id' => $msg_id,
            'created_at' => current_time( 'mysql' ),
        ) );
    }
}
