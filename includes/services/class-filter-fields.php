<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Water / air / container filtration & measurement fields.
 *
 * Each "type" is a repeating group of (ID field + value field). The number of
 * instances per type is a global setting (0–100). Data is stored in the
 * ednasurvey_site_filters child table, not as columns on the sites table.
 *
 * The ID fields auto-fill with "<sample_id>-N", where N is a global running
 * number across all types in display order (water → air → container).
 */
class EdnaSurvey_Filter_Fields {

    const MAX_COUNT = 100;

    /**
     * Static metadata for the three types, in canonical display order.
     */
    public static function types(): array {
        return array(
            'water' => array(
                'count_key'       => 'water_filter_count',
                'id_key_base'     => 'waterfilter',
                'val_key_base'    => 'watervol',
                'id_label_local'  => '水ろ過フィルターID',
                'id_label_en'     => 'Water filter ID',
                'val_label_local' => 'ろ過水量',
                'val_label_en'    => 'Filtered water volume',
                'unit'            => 'mL',
                'val_type'        => 'number',  // integer
            ),
            'air' => array(
                'count_key'       => 'air_filter_count',
                'id_key_base'     => 'airfilter',
                'val_key_base'    => 'airvol',
                'id_label_local'  => '空気ろ過フィルターID',
                'id_label_en'     => 'Air filter ID',
                'val_label_local' => '濾過空気量',
                'val_label_en'    => 'Filtered air volume',
                'unit'            => 'mL',
                'val_type'        => 'number',  // integer
            ),
            'container' => array(
                'count_key'       => 'container_count',
                'id_key_base'     => 'container',
                'val_key_base'    => 'weight',
                'id_label_local'  => '容器ID',
                'id_label_en'     => 'Container ID',
                'val_label_local' => 'サンプル重量',
                'val_label_en'    => 'Sample weight',
                'unit'            => 'g',
                'val_type'        => 'decimal',
            ),
        );
    }

    /**
     * Default instance count per type (water visible, air/container hidden).
     */
    public static function default_count( string $type ): int {
        return 'water' === $type ? 2 : 0;
    }

    /**
     * Configured instance count for a type, clamped to 0..MAX_COUNT.
     */
    public static function get_count( string $type ): int {
        $types = self::types();
        if ( ! isset( $types[ $type ] ) ) {
            return 0;
        }
        $settings = get_option( 'ednasurvey_settings', array() );
        $key      = $types[ $type ]['count_key'];
        $val      = isset( $settings[ $key ] ) ? (int) $settings[ $key ] : self::default_count( $type );
        return max( 0, min( self::MAX_COUNT, $val ) );
    }

    /**
     * @return array<string,int> type => count
     */
    public static function get_counts(): array {
        $out = array();
        foreach ( array_keys( self::types() ) as $type ) {
            $out[ $type ] = self::get_count( $type );
        }
        return $out;
    }

    /**
     * Ordered list of all visible instances across all types, with a global
     * running number (seq) used for "<sample_id>-N" and column ordering.
     *
     * @return array<int,array{type:string,index:int,seq:int,id_key:string,
     *         val_key:string,id_label:string,val_label:string,val_type:string,
     *         val_step:string,unit:string}>
     */
    public static function get_instances(): array {
        $is_ja = ( 'ja' === EdnaSurvey_I18n::get_current_language() );
        $out   = array();
        $seq   = 0;
        foreach ( self::types() as $type => $meta ) {
            $count = self::get_count( $type );
            for ( $i = 1; $i <= $count; $i++ ) {
                $seq++;
                $id_label  = ( $is_ja ? $meta['id_label_local'] : $meta['id_label_en'] ) . $i;
                $val_label = ( $is_ja ? $meta['val_label_local'] : $meta['val_label_en'] ) . $i . ' (' . $meta['unit'] . ')';
                $out[] = array(
                    'type'      => $type,
                    'index'     => $i,
                    'seq'       => $seq,
                    'id_key'    => $meta['id_key_base'] . $i,
                    'val_key'   => $meta['val_key_base'] . $i,
                    'id_label'  => $id_label,
                    'val_label' => $val_label,
                    'val_type'  => $meta['val_type'],
                    'val_step'  => 'decimal' === $meta['val_type'] ? '0.01' : '1',
                    'unit'      => $meta['unit'],
                );
            }
        }
        return $out;
    }
}
