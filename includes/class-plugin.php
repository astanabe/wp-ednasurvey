<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Plugin {

    private static ?EdnaSurvey_Plugin $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->check_db_version();
        $this->init_hooks();
    }

    private function check_db_version(): void {
        $installed_version = get_option( 'ednasurvey_db_version', '0' );
        if ( version_compare( $installed_version, EDNASURVEY_DB_VERSION, '<' ) ) {
            EdnaSurvey_Activator::create_tables();
            update_option( 'ednasurvey_db_version', EDNASURVEY_DB_VERSION );
        }

        // Flush rewrite rules once after plugin update or first install
        if ( get_option( 'ednasurvey_flush_rewrite', false ) ) {
            flush_rewrite_rules();
            delete_option( 'ednasurvey_flush_rewrite' );
        }
    }

    private function init_hooks(): void {
        // Temp photo cleanup cron
        if ( ! wp_next_scheduled( 'ednasurvey_cleanup_temp_photos' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'ednasurvey_cleanup_temp_photos' );
        }
        add_action( 'ednasurvey_cleanup_temp_photos', function () {
            ( new EdnaSurvey_Photo_Service() )->cleanup_temp_dirs();
        } );

        // i18n
        $i18n = new EdnaSurvey_I18n();
        add_action( 'init', array( $i18n, 'load_textdomain' ) );
        add_filter( 'locale', array( $i18n, 'filter_locale' ) );

        // Router — detect page early (before wp_enqueue_scripts), render on template_redirect
        $router = new EdnaSurvey_Router();
        add_action( 'parse_request', array( $router, 'detect_early' ) );
        add_action( 'template_redirect', array( $router, 'handle_request' ) );

        // Add body class for CSS overrides on plugin pages
        add_filter( 'body_class', array( $this, 'add_body_class' ) );

        // Login redirect for subscribers
        add_filter( 'login_redirect', array( $this, 'subscriber_login_redirect' ), 10, 3 );

        // Block subscribers from wp-admin and redirect to dashboard
        add_action( 'admin_init', array( $this, 'block_subscriber_admin_access' ) );

        // Hide admin bar for subscribers
        add_action( 'after_setup_theme', array( $this, 'hide_admin_bar_for_subscribers' ) );

        // Remove GeneratePress footer credits to prevent accidental taps during field surveys
        add_action( 'wp_head', array( $this, 'remove_generatepress_credits' ), 99 );

        // Assets
        $assets = new EdnaSurvey_Assets();
        add_action( 'wp_enqueue_scripts', array( $assets, 'enqueue_frontend' ) );
        add_action( 'admin_enqueue_scripts', array( $assets, 'enqueue_admin' ) );

        // Admin
        if ( is_admin() ) {
            $admin_menu = new EdnaSurvey_Admin_Menu();
            add_action( 'admin_menu', array( $admin_menu, 'register_menus' ) );

            // Save Screen Options (per_page)
            add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );

            // Deactivation confirm dialog on plugins page
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_deactivate_script' ) );
        }

        // AJAX handlers
        $this->register_ajax_handlers();

        // Deactivate flag AJAX
        add_action( 'wp_ajax_ednasurvey_set_delete_flag', array( $this, 'ajax_set_delete_flag' ) );

        // REST API
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    public function subscriber_login_redirect( string $redirect_to, string $requested_redirect_to, $user ): string {
        if ( ! is_wp_error( $user ) && $user instanceof WP_User ) {
            if ( in_array( 'subscriber', $user->roles, true ) ) {
                // Always redirect subscribers to their dashboard,
                // unless they were explicitly trying to reach a non-admin frontend URL
                if ( empty( $requested_redirect_to )
                    || str_contains( $requested_redirect_to, 'wp-admin' )
                    || str_contains( $redirect_to, 'wp-admin' )
                ) {
                    return home_url( '/' . $user->user_login . '/' );
                }
            }
        }
        return $redirect_to;
    }

    public function add_body_class( array $classes ): array {
        if ( EdnaSurvey_Router::$current_page ) {
            $classes[] = 'ednasurvey-page';
            $classes[] = 'ednasurvey-page-' . EdnaSurvey_Router::$current_page;
        }
        return $classes;
    }

    public function block_subscriber_admin_access(): void {
        if ( wp_doing_ajax() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( $user->ID && in_array( 'subscriber', $user->roles, true ) ) {
            wp_safe_redirect( home_url( '/' . $user->user_login . '/' ) );
            exit;
        }
    }

    public function hide_admin_bar_for_subscribers(): void {
        if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
            show_admin_bar( false );
        }
    }

    public function remove_generatepress_credits(): void {
        remove_action( 'generate_credits', 'generate_add_footer_info' );
    }

    public function save_screen_option( $status, string $option, $value ) {
        if ( 'ednasurvey_sites_per_page' === $option ) {
            return (int) $value;
        }
        return $status;
    }

    public function enqueue_deactivate_script( string $hook ): void {
        if ( 'plugins.php' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'ednasurvey-deactivate',
            EDNASURVEY_PLUGIN_URL . 'assets/js/admin/deactivate.js',
            array( 'jquery' ),
            EDNASURVEY_VERSION,
            true
        );

        wp_localize_script( 'ednasurvey-deactivate', 'ednasurveyDeactivate', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'ednasurvey_delete_flag' ),
            'confirmMessage' => __( "Do you want to delete all eDNA Survey data (database tables and uploaded photos) when the plugin is uninstalled?\n\nOK = Delete data on uninstall\nCancel = Keep data", 'wp-ednasurvey' ),
        ) );
    }

    public function ajax_set_delete_flag(): void {
        check_ajax_referer( 'ednasurvey_delete_flag', 'nonce' );

        if ( ! current_user_can( 'activate_plugins' ) ) {
            wp_send_json_error();
        }

        $delete = '1' === ( $_POST['delete_data'] ?? '0' );
        update_option( 'ednasurvey_delete_data_on_uninstall', $delete );
        wp_send_json_success();
    }

    private function register_ajax_handlers(): void {
        $submission_ajax = new EdnaSurvey_Ajax_Submission();
        $submission_ajax->register();

        $chat_ajax = new EdnaSurvey_Ajax_Chat();
        $chat_ajax->register();

        $sites_ajax = new EdnaSurvey_Ajax_Sites();
        $sites_ajax->register();

        $admin_ajax = new EdnaSurvey_Ajax_Admin();
        $admin_ajax->register();
    }

    public function register_rest_routes(): void {
        $sites_ajax = new EdnaSurvey_Ajax_Sites();
        $sites_ajax->register_rest_routes();

        $chat_ajax = new EdnaSurvey_Ajax_Chat();
        $chat_ajax->register_rest_routes();
    }
}
