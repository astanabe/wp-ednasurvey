<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Notification_Service {

    public function notify_admin_new_message( int $sender_id, string $message_text ): void {
        $sender  = get_user_by( 'id', $sender_id );
        if ( ! $sender ) {
            return;
        }

        $admin_email = get_option( 'admin_email' );
        $site_name   = get_bloginfo( 'name' );
        $subject     = sprintf(
            /* translators: 1: site name, 2: sender display name */
            __( '[%1$s] New message from %2$s', 'wp-ednasurvey' ),
            $site_name,
            $sender->display_name
        );

        $admin_url = admin_url( 'admin.php?page=edna-survey-messages&user_id=' . $sender_id );
        $body      = sprintf(
            /* translators: 1: sender display name, 2: message text, 3: admin URL */
            __( "%1\$s sent a new message:\n\n%2\$s\n\nView and reply: %3\$s", 'wp-ednasurvey' ),
            $sender->display_name,
            $message_text,
            $admin_url
        );

        wp_mail( $admin_email, $subject, $body );
    }
}
