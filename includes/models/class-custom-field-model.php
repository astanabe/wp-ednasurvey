<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Custom_Field_Model {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ednasurvey_custom_fields';
    }

    /**
     * Get fields that are not disabled (have data or require input).
     */
    public function get_active_fields(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$this->table} WHERE field_mode != 'disabled' ORDER BY sort_order ASC, id ASC"
        );
    }

    public function get_all_fields(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC"
        );
    }

    public function get_by_id( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
        );
    }

    public function insert( array $data ): int|false {
        global $wpdb;
        $result = $wpdb->insert( $this->table, $data );
        if ( false === $result ) {
            return false;
        }
        return $wpdb->insert_id;
    }

    public function update( int $id, array $data ): bool {
        global $wpdb;
        return (bool) $wpdb->update( $this->table, $data, array( 'id' => $id ) );
    }

    public function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
    }
}
