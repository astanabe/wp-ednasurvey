<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Chat_Model {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ednasurvey_messages';
    }

    public function insert( array $data ): int|false {
        global $wpdb;
        $result = $wpdb->insert( $this->table, $data );
        if ( false === $result ) {
            return false;
        }
        return $wpdb->insert_id;
    }

    public function get_messages( int $conversation_user_id, int $after_id = 0, int $limit = 100 ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, u.display_name AS sender_name
                 FROM {$this->table} m
                 JOIN {$wpdb->users} u ON m.sender_id = u.ID
                 WHERE m.conversation_user_id = %d AND m.id > %d
                 ORDER BY m.created_at ASC
                 LIMIT %d",
                $conversation_user_id,
                $after_id,
                $limit
            )
        );
    }

    public function mark_as_read( int $conversation_user_id, int $reader_id ): void {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table} SET is_read = 1
                 WHERE conversation_user_id = %d AND sender_id != %d AND is_read = 0",
                $conversation_user_id,
                $reader_id
            )
        );
    }

    public function get_conversations(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT
                m.conversation_user_id,
                u.user_login,
                u.display_name,
                MAX(m.created_at) AS last_message_at,
                SUM(CASE WHEN m.is_read = 0 AND m.sender_id = m.conversation_user_id THEN 1 ELSE 0 END) AS unread_count,
                (SELECT message FROM {$this->table} m2 WHERE m2.conversation_user_id = m.conversation_user_id ORDER BY m2.created_at DESC LIMIT 1) AS last_message
             FROM {$this->table} m
             JOIN {$wpdb->users} u ON m.conversation_user_id = u.ID
             GROUP BY m.conversation_user_id, u.user_login, u.display_name
             ORDER BY last_message_at DESC"
        );
    }

    public function get_unread_count_for_admin(): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE is_read = 0 AND sender_id = conversation_user_id"
        );
    }
}
