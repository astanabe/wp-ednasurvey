<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CRUD for the ednasurvey_site_filters child table (water/air/container units).
 */
class EdnaSurvey_Site_Filter_Model {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ednasurvey_site_filters';
    }

    /**
     * @return object[] Rows ordered by type then index.
     */
    public function get_by_site( int $site_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE site_id = %d ORDER BY filter_type, filter_index",
                $site_id
            )
        );
    }

    /**
     * Lookup keyed by "<type>_<index>" => row.
     */
    public function get_map_by_site( int $site_id ): array {
        $map = array();
        foreach ( $this->get_by_site( $site_id ) as $row ) {
            $map[ $row->filter_type . '_' . (int) $row->filter_index ] = $row;
        }
        return $map;
    }

    /**
     * Replace all filter rows for a site.
     *
     * @param int   $site_id Site ID.
     * @param array $rows    Each: ['filter_type','filter_index','filter_id','filter_value'].
     */
    public function replace_for_site( int $site_id, array $rows ): void {
        global $wpdb;
        $this->delete_by_site( $site_id );

        foreach ( $rows as $r ) {
            $value = $r['filter_value'] ?? null;
            $value = ( null !== $value && '' !== $value ) ? (float) $value : null;

            $wpdb->insert(
                $this->table,
                array(
                    'site_id'      => $site_id,
                    'filter_type'  => (string) $r['filter_type'],
                    'filter_index' => (int) $r['filter_index'],
                    'filter_id'    => (string) ( $r['filter_id'] ?? '' ),
                    'filter_value' => $value,
                ),
                array( '%d', '%s', '%d', '%s', '%f' )
            );
        }
    }

    public function delete_by_site( int $site_id ): void {
        global $wpdb;
        $wpdb->delete( $this->table, array( 'site_id' => $site_id ), array( '%d' ) );
    }

    /**
     * Highest filter_index that holds an actual value for a type (0 if none).
     * Used to forbid reducing a type's count below stored data.
     */
    public function get_max_value_index( string $filter_type ): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(MAX(filter_index), 0) FROM {$this->table}
                 WHERE filter_type = %s AND filter_value IS NOT NULL",
                $filter_type
            )
        );
    }
}
