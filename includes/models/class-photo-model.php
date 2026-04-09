<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Photo_Model {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ednasurvey_photos';
    }

    public function insert( array $data ): int|false {
        global $wpdb;
        $result = $wpdb->insert( $this->table, $data );
        if ( false === $result ) {
            return false;
        }
        return $wpdb->insert_id;
    }

    public function get_by_site( int $site_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE site_id = %d ORDER BY id ASC", $site_id )
        );
    }

    public function get_by_user( int $user_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY created_at DESC", $user_id )
        );
    }

    public function get_urls_by_sites( array $site_ids ): array {
        global $wpdb;
        if ( empty( $site_ids ) ) {
            return array();
        }
        $placeholders = implode( ',', array_fill( 0, count( $site_ids ), '%d' ) );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT site_id, file_url, original_filename FROM {$this->table} WHERE site_id IN ({$placeholders}) ORDER BY site_id, id",
                ...$site_ids
            )
        );
    }

    public function delete_by_site( int $site_id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table, array( 'site_id' => $site_id ), array( '%d' ) );
    }
}
