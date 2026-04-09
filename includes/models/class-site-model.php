<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Site_Model {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ednasurvey_sites';
    }

    public function insert( array $data ): int|false {
        global $wpdb;

        $result = $wpdb->insert( $this->table, $data );
        if ( false === $result ) {
            return false;
        }
        return $wpdb->insert_id;
    }

    public function get_by_id( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
        );
    }

    public function get_by_internal_id( string $internal_sample_id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE internal_sample_id = %s", $internal_sample_id )
        );
    }

    public function get_by_user( int $user_id, string $orderby = 'created_at', string $order = 'DESC' ): array {
        global $wpdb;

        $allowed_orderby = array( 'id', 'survey_date', 'sitename_local', 'sitename_en', 'sample_id', 'created_at' );
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'created_at';
        }
        $order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY {$orderby} {$order}",
                $user_id
            )
        );
    }

    public function get_all( array $filters = array() ): array {
        global $wpdb;

        $where  = array( '1=1' );
        $params = array();

        if ( ! empty( $filters['user_id'] ) ) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $filters['user_id'];
        }
        if ( ! empty( $filters['date_from'] ) ) {
            $where[]  = 'survey_date >= %s';
            $params[] = $filters['date_from'];
        }
        if ( ! empty( $filters['date_to'] ) ) {
            $where[]  = 'survey_date <= %s';
            $params[] = $filters['date_to'];
        }
        if ( ! empty( $filters['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $where[]  = '(sitename_local LIKE %s OR sitename_en LIKE %s OR sample_id LIKE %s OR correspondence LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_clause = implode( ' AND ', $where );
        $sql          = "SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY created_at DESC";

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, ...$params );
        }

        return $wpdb->get_results( $sql );
    }

    public function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
    }

    public function count_by_user( int $user_id ): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d", $user_id )
        );
    }
}
