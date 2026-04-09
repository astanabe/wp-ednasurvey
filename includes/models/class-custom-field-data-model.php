<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Custom_Field_Data_Model {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ednasurvey_site_custom_data';
    }

    public function save( int $site_id, int $field_id, string $value ): bool {
        global $wpdb;

        // Upsert: insert or update on duplicate key
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$this->table} (site_id, field_id, field_value) VALUES (%d, %d, %s)
                 ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)",
                $site_id,
                $field_id,
                $value
            )
        );

        return false !== $result;
    }

    public function get_by_site( int $site_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT d.*, f.field_key, f.label_ja, f.label_en, f.field_type
                 FROM {$this->table} d
                 JOIN {$wpdb->prefix}ednasurvey_custom_fields f ON d.field_id = f.id
                 WHERE d.site_id = %d
                 ORDER BY f.sort_order ASC",
                $site_id
            )
        );
    }

    public function delete_by_site( int $site_id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table, array( 'site_id' => $site_id ), array( '%d' ) );
    }

    public function delete_by_field( int $field_id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $this->table, array( 'field_id' => $field_id ), array( '%d' ) );
    }
}
