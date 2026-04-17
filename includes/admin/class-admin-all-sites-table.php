<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class EdnaSurvey_All_Sites_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'site',
            'plural'   => 'sites',
            'ajax'     => false,
        ) );
    }

    public function get_columns(): array {
        $registry = EdnaSurvey_Field_Registry::get_instance();

        $columns = array(
            'cb'                 => '<input type="checkbox" />',
            'internal_sample_id' => __( 'Internal Sample ID', 'wp-ednasurvey' ),
            'user_login'         => __( 'User', 'wp-ednasurvey' ),
            'submitted_at'       => __( 'Submitted', 'wp-ednasurvey' ),
            'submitted_method'   => __( 'Method', 'wp-ednasurvey' ),
            'submitted_ip'       => __( 'IP / Geo', 'wp-ednasurvey' ),
        );

        // Group A: always shown
        $columns['survey_date']    = $registry->get_label( 'survey_date' );
        $columns['survey_time']    = $registry->get_label( 'survey_time' );
        $columns['latitude']       = __( 'Lat', 'wp-ednasurvey' );
        $columns['longitude']      = __( 'Lon', 'wp-ednasurvey' );
        $columns['sitename_local'] = $registry->get_label( 'sitename_local' );
        $columns['sitename_en']    = $registry->get_label( 'sitename_en' );

        // Group B: always shown
        $columns['correspondence'] = $registry->get_label( 'correspondence' );
        $columns['sample_id']      = $registry->get_label( 'sample_id' );

        // Collector 1 (Group B) always shown
        $columns['collector1'] = $registry->get_label( 'collector1' );
        // Collectors 2-5 (Group C grouped)
        if ( $registry->is_active( 'collector2' ) ) {
            for ( $i = 2; $i <= 5; $i++ ) {
                $columns[ 'collector' . $i ] = $registry->get_label( 'collector' . $i );
            }
        }

        // Group C: mode-dependent
        $numeric_fields = array( 'watervol1', 'watervol2', 'airvol1', 'airvol2', 'weight1', 'weight2' );
        foreach ( $numeric_fields as $nf ) {
            if ( $registry->is_active( $nf ) ) {
                $columns[ $nf ] = $registry->get_label( $nf );
            }
        }
        if ( $registry->is_active( 'filter_name' ) ) {
            $columns['filter_name'] = $registry->get_label( 'filter_name' );
        }
        if ( $registry->is_active( 'env_medium' ) ) {
            $columns['env_medium'] = $registry->get_label( 'env_medium' );
        }
        if ( $registry->is_active( 'notes' ) ) {
            $columns['notes'] = $registry->get_label( 'notes' );
        }

        $columns['photos'] = __( 'Photos', 'wp-ednasurvey' );

        return $columns;
    }

    /**
     * Columns that cannot be hidden via Screen Options.
     */
    protected function get_primary_column_name(): string {
        return 'internal_sample_id';
    }

    public function get_sortable_columns(): array {
        return array(
            'internal_sample_id' => array( 'internal_sample_id', false ),
            'user_login'         => array( 'user_login', false ),
            'submitted_at'       => array( 'submitted_at', true ),
            'survey_date'        => array( 'survey_date', false ),
            'sitename_local'     => array( 'sitename_local', false ),
            'sample_id'          => array( 'sample_id', false ),
        );
    }

    public function get_bulk_actions(): array {
        return array(
            'delete' => __( 'Delete', 'wp-ednasurvey' ),
        );
    }

    public function prepare_items(): void {
        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'ednasurvey_sites_per_page', 50 );
        $current_page = $this->get_pagenum();

        $site_model  = new EdnaSurvey_Site_Model();
        $photo_model = new EdnaSurvey_Photo_Model();
        $all_sites   = $site_model->get_all();

        // Attach extra data
        foreach ( $all_sites as &$site ) {
            $user              = get_user_by( 'id', $site->user_id );
            $site->user_login  = $user ? $user->user_login : '';
            $photos            = $photo_model->get_by_site( (int) $site->id );
            $site->photo_count = count( $photos );
            $site->first_photo_url = ! empty( $photos ) ? $photos[0]->file_url : '';
        }
        unset( $site );

        // Sort
        $orderby = sanitize_text_field( $_GET['orderby'] ?? 'submitted_at' );
        $order   = 'asc' === strtolower( sanitize_text_field( $_GET['order'] ?? 'desc' ) ) ? SORT_ASC : SORT_DESC;
        usort( $all_sites, function ( $a, $b ) use ( $orderby, $order ) {
            $va = $a->$orderby ?? '';
            $vb = $b->$orderby ?? '';
            $cmp = strnatcasecmp( (string) $va, (string) $vb );
            return SORT_ASC === $order ? $cmp : -$cmp;
        } );

        $total_items = count( $all_sites );
        $this->items = array_slice( $all_sites, ( $current_page - 1 ) * $per_page, $per_page );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );
    }

    public function process_bulk_action(): void {
        if ( 'delete' !== $this->current_action() ) {
            return;
        }

        $ids = array_map( 'absint', (array) ( $_REQUEST['site'] ?? array() ) );
        $ids = array_filter( $ids );

        if ( empty( $ids ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'] ?? '', 'bulk-sites' ) ) {
            return;
        }

        $site_model        = new EdnaSurvey_Site_Model();
        $photo_service     = new EdnaSurvey_Photo_Service();
        $photo_model       = new EdnaSurvey_Photo_Model();
        $custom_data_model = new EdnaSurvey_Custom_Field_Data_Model();

        foreach ( $ids as $site_id ) {
            $site = $site_model->get_by_id( $site_id );
            if ( ! $site ) {
                continue;
            }
            $photo_service->delete_site_photos( (int) $site->user_id, $site_id );
            $photo_model->delete_by_site( $site_id );
            $custom_data_model->delete_by_site( $site_id );
            $site_model->delete( $site_id );
        }
    }

    public function column_cb( $item ): string {
        return '<input type="checkbox" name="site[]" value="' . (int) $item->id . '" />';
    }

    public function column_internal_sample_id( $item ): string {
        $detail_url = admin_url( 'admin.php?page=edna-survey-site-detail&site=' . rawurlencode( $item->internal_sample_id ) );
        $title      = '<strong><a href="' . esc_url( $detail_url ) . '" style="font-size:0.85em;word-break:break-all;">'
            . esc_html( $item->internal_sample_id ) . '</a></strong>';

        $actions = array(
            'view' => '<a href="' . esc_url( $detail_url ) . '">' . esc_html__( 'Detail', 'wp-ednasurvey' ) . '</a>',
        );

        return $title . $this->row_actions( $actions );
    }

    public function column_default( $item, $column_name ): string {
        switch ( $column_name ) {

            case 'user_login':
                return esc_html( $item->user_login );

            case 'submitted_at':
                return esc_html( substr( $item->submitted_at ?? '', 0, 16 ) );

            case 'submitted_method':
                return esc_html( $item->submitted_method ?? '' );

            case 'submitted_ip':
                $out = esc_html( $item->submitted_ip ?? '' );
                if ( ! empty( $item->submitted_geo ) ) {
                    $out .= '<br><small>' . esc_html( $item->submitted_geo ) . '</small>';
                }
                return $out;

            case 'survey_date':
                return esc_html( $item->survey_date ?? '' );

            case 'survey_time':
                return esc_html( substr( $item->survey_time ?? '', 0, 5 ) );

            case 'latitude':
            case 'longitude':
                return esc_html( $item->$column_name ?? '' );

            case 'sitename_local':
            case 'sitename_en':
            case 'correspondence':
            case 'sample_id':
                return esc_html( $item->$column_name ?? '' );

            case 'collector1':
            case 'collector2':
            case 'collector3':
            case 'collector4':
            case 'collector5':
                return esc_html( $item->$column_name ?? '' );

            case 'watervol1':
            case 'watervol2':
                return esc_html( $item->$column_name ?? '' );

            case 'notes':
                $text = $item->notes ?? '';
                return esc_html( mb_substr( $text, 0, 50 ) ) . ( mb_strlen( $text ) > 50 ? '...' : '' );

            case 'photos':
                if ( $item->photo_count > 0 ) {
                    $out = '';
                    if ( ! empty( $item->first_photo_url ) ) {
                        $out .= '<a href="' . esc_url( $item->first_photo_url ) . '" target="_blank">'
                            . '<img src="' . esc_url( $item->first_photo_url ) . '" style="width:40px;height:40px;object-fit:cover;border-radius:3px;vertical-align:middle;" alt="">'
                            . '</a> ';
                    }
                    $out .= '<small>(' . (int) $item->photo_count . ')</small>';
                    return $out;
                }
                return '-';

        }

        return '';
    }

    public function no_items(): void {
        esc_html_e( 'No survey sites have been submitted yet.', 'wp-ednasurvey' );
    }
}
