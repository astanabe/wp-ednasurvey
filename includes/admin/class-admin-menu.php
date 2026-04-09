<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Admin_Menu {

    public function register_menus(): void {
        add_menu_page(
            __( 'eDNA Survey', 'wp-ednasurvey' ),
            __( 'eDNA Survey', 'wp-ednasurvey' ),
            'manage_options',
            'edna-survey',
            array( $this, 'render_download_data' ),
            'dashicons-location-alt',
            30
        );

        add_submenu_page(
            'edna-survey',
            __( 'Download Data', 'wp-ednasurvey' ),
            __( 'Download Data', 'wp-ednasurvey' ),
            'manage_options',
            'edna-survey',
            array( $this, 'render_download_data' )
        );

        add_submenu_page(
            'edna-survey',
            __( 'Add Users', 'wp-ednasurvey' ),
            __( 'Add Users', 'wp-ednasurvey' ),
            'manage_options',
            'edna-survey-add-users',
            array( $this, 'render_add_users' )
        );

        $all_sites_hook = add_submenu_page(
            'edna-survey',
            __( 'All Sites', 'wp-ednasurvey' ),
            __( 'All Sites', 'wp-ednasurvey' ),
            'manage_options',
            'edna-survey-all-sites',
            array( $this, 'render_all_sites' )
        );
        add_action( 'load-' . $all_sites_hook, array( $this, 'all_sites_screen_options' ) );

        add_submenu_page(
            'edna-survey',
            __( 'Sites Map', 'wp-ednasurvey' ),
            __( 'Sites Map', 'wp-ednasurvey' ),
            'manage_options',
            'edna-survey-sites-map',
            array( $this, 'render_sites_map' )
        );

        add_submenu_page(
            'edna-survey',
            __( 'Settings', 'wp-ednasurvey' ),
            __( 'Settings', 'wp-ednasurvey' ),
            'manage_options',
            'edna-survey-settings',
            array( $this, 'render_settings' )
        );

        add_submenu_page(
            'edna-survey',
            __( 'Messages', 'wp-ednasurvey' ),
            __( 'Messages', 'wp-ednasurvey' ),
            'manage_options',
            'edna-survey-messages',
            array( $this, 'render_messages' )
        );

        // Hidden page (no menu entry) for site detail
        add_submenu_page(
            null,
            __( 'Site Detail', 'wp-ednasurvey' ),
            '',
            'manage_options',
            'edna-survey-site-detail',
            array( $this, 'render_site_detail' )
        );
    }

    public function render_download_data(): void {
        $page = new EdnaSurvey_Admin_Download_Data();
        $page->render();
    }

    public function render_add_users(): void {
        $page = new EdnaSurvey_Admin_Add_Users();
        $page->render();
    }

    public function all_sites_screen_options(): void {
        add_screen_option( 'per_page', array(
            'label'   => __( 'Sites per page', 'wp-ednasurvey' ),
            'default' => 50,
            'option'  => 'ednasurvey_sites_per_page',
        ) );

        // Instantiate table early so WP can read columns for Screen Options
        new EdnaSurvey_All_Sites_Table();

        // Set default hidden columns for new users (first visit)
        add_filter( 'default_hidden_columns', array( $this, 'all_sites_default_hidden' ), 10, 2 );

        // Prevent hiding essential columns
        add_filter( 'hidden_columns', array( $this, 'all_sites_protect_columns' ), 10, 3 );

    }

    public function all_sites_default_hidden( array $hidden, WP_Screen $screen ): array {
        if ( str_contains( $screen->id, 'edna-survey-all-sites' ) ) {
            return array( 'submitted_ip', 'survey_time', 'sitename_en', 'correspondence',
                'collector1', 'collector2', 'collector3', 'collector4', 'collector5',
                'watervol1', 'watervol2', 'notes', 'latitude', 'longitude' );
        }
        return $hidden;
    }

    public function all_sites_protect_columns( array $hidden, WP_Screen $screen, bool $use_defaults ): array {
        if ( str_contains( $screen->id, 'edna-survey-all-sites' ) ) {
            $hidden = array_diff( $hidden, array( 'internal_sample_id' ) );
        }
        return $hidden;
    }

    public function render_all_sites(): void {
        $page = new EdnaSurvey_Admin_All_Sites();
        $page->render();
    }

    public function render_sites_map(): void {
        $page = new EdnaSurvey_Admin_Sites_Map();
        $page->render();
    }

    public function render_settings(): void {
        $page = new EdnaSurvey_Admin_Settings();
        $page->render();
    }

    public function render_messages(): void {
        $page = new EdnaSurvey_Admin_Messages();
        $page->render();
    }

    public function render_site_detail(): void {
        $page = new EdnaSurvey_Admin_Site_Detail();
        $page->render();
    }
}
