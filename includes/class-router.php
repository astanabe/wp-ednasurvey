<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Router {

    /** @var string|null Current eDNA survey page slug, set early for asset enqueuing. */
    public static ?string $current_page = null;

    /** @var string|null Current target username, set early for asset enqueuing. */
    public static ?string $current_username = null;

    /** @var string|null Site detail slug (internal_sample_id), set for /USERNAME/site/{slug}. */
    public static ?string $current_site_slug = null;

    private const SUBPAGES = array(
        'onlinesubmission',
        'offlinetemplate',
        'offlinesubmission',
        'sites',
        'map',
        'chat',
    );

    /**
     * Rewrite rules are no longer used.
     * All routing is handled by parsing REQUEST_URI in handle_request().
     */
    public function add_rewrite_rules(): void {
        // intentionally empty
    }

    public function add_query_vars( array $vars ): array {
        $vars[] = 'ednasurvey_user';
        $vars[] = 'ednasurvey_page';
        return $vars;
    }

    /**
     * Called on 'parse_request' to detect the page early (before wp_enqueue_scripts).
     */
    public function detect_early(): void {
        $parsed = $this->parse_request();
        if ( null !== $parsed ) {
            self::$current_username  = $parsed['username'];
            self::$current_page     = $parsed['page'];
            self::$current_site_slug = $parsed['site_slug'] ?? null;

            // Set HTML <title> for plugin pages
            add_filter( 'document_title_parts', array( $this, 'filter_title' ) );
        }
    }

    /**
     * Page titles — same values used for both <title> and <h1>.
     */
    public static function get_page_titles(): array {
        return array(
            'dashboard'         => __( 'eDNA Survey Dashboard', 'wp-ednasurvey' ),
            'onlinesubmission'  => __( 'Submit Survey Data', 'wp-ednasurvey' ),
            'offlinetemplate'   => __( 'Download Offline Template', 'wp-ednasurvey' ),
            'offlinesubmission' => __( 'Upload Offline Data', 'wp-ednasurvey' ),
            'sites'             => __( 'My Survey Sites', 'wp-ednasurvey' ),
            'map'               => __( 'My Sites Map', 'wp-ednasurvey' ),
            'chat'              => __( 'Messages', 'wp-ednasurvey' ),
            'sitedetail'        => __( 'Site Detail', 'wp-ednasurvey' ),
        );
    }

    public function filter_title( array $title_parts ): array {
        $titles = self::get_page_titles();
        $page   = self::$current_page;

        if ( $page && isset( $titles[ $page ] ) ) {
            $title_parts['title'] = $titles[ $page ];
        }

        return $title_parts;
    }

    public function handle_request(): void {
        $username = self::$current_username;
        $page     = self::$current_page;

        if ( null === $username || null === $page ) {
            return;
        }

        // Verify the username corresponds to a real WordPress user
        $target_user = get_user_by( 'login', $username );
        if ( ! $target_user ) {
            return;
        }

        // Must be logged in
        if ( ! is_user_logged_in() ) {
            $return_path = 'dashboard' === $page
                ? '/' . $username . '/'
                : '/' . $username . '/' . $page;
            wp_safe_redirect( wp_login_url( home_url( $return_path ) ) );
            exit;
        }

        // Access control: only the user themselves or admins
        $current_user = wp_get_current_user();
        if ( $current_user->ID !== $target_user->ID && ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__( 'You do not have permission to view this page.', 'wp-ednasurvey' ),
                403
            );
        }

        // WordPress may have set 404 internally before the router intercepted.
        // Reset the 404 state so themes render correctly.
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        status_header( 200 );

        $this->render_page( $page, $target_user );
        exit;
    }

    /**
     * Parse REQUEST_URI to detect /USERNAME/ or /USERNAME/SUBPAGE patterns.
     * Returns ['username' => ..., 'page' => ...] or null if not an eDNA survey URL.
     */
    private function parse_request(): ?array {
        $home_path   = wp_parse_url( home_url(), PHP_URL_PATH ) ?: '';
        $request_uri = isset( $_SERVER['REQUEST_URI'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
            : '';
        $request_path = wp_parse_url( $request_uri, PHP_URL_PATH ) ?: '';

        // Strip the home path prefix (e.g. /wordpress/) if the site is in a subdirectory
        if ( '' !== $home_path && str_starts_with( $request_path, $home_path ) ) {
            $request_path = substr( $request_path, strlen( $home_path ) );
        }

        $request_path = trim( $request_path, '/' );

        if ( empty( $request_path ) ) {
            return null;
        }

        // Don't intercept known WordPress paths
        $first_segment = explode( '/', $request_path )[0];
        $reserved = array(
            'wp-admin', 'wp-login.php', 'wp-content', 'wp-includes',
            'wp-json', 'feed', 'xmlrpc.php', 'wp-cron.php', 'favicon.ico',
        );
        if ( in_array( $first_segment, $reserved, true ) ) {
            return null;
        }

        $segments = explode( '/', $request_path );

        // /USERNAME/site/{internal_sample_id}
        if ( count( $segments ) === 3 && 'site' === $segments[1] ) {
            return array(
                'username'  => $segments[0],
                'page'      => 'sitedetail',
                'site_slug' => $segments[2],
            );
        }

        // /USERNAME/SUBPAGE
        if ( count( $segments ) === 2 && in_array( $segments[1], self::SUBPAGES, true ) ) {
            return array(
                'username' => $segments[0],
                'page'     => $segments[1],
            );
        }

        // /USERNAME  (single segment = dashboard)
        if ( count( $segments ) === 1 ) {
            return array(
                'username' => $segments[0],
                'page'     => 'dashboard',
            );
        }

        return null;
    }

    private function render_page( string $page, WP_User $target_user ): void {
        $controllers = array(
            'dashboard'         => 'EdnaSurvey_Dashboard_Controller',
            'onlinesubmission'  => 'EdnaSurvey_Online_Submission_Controller',
            'offlinetemplate'   => 'EdnaSurvey_Offline_Template_Controller',
            'offlinesubmission' => 'EdnaSurvey_Offline_Submission_Controller',
            'sites'             => 'EdnaSurvey_Sites_Controller',
            'map'               => 'EdnaSurvey_Map_Controller',
            'chat'              => 'EdnaSurvey_Chat_Controller',
            'sitedetail'        => 'EdnaSurvey_Site_Detail_Controller',
        );

        if ( ! isset( $controllers[ $page ] ) ) {
            wp_die( esc_html__( 'Page not found.', 'wp-ednasurvey' ), 404 );
        }

        $class      = $controllers[ $page ];
        $controller = new $class();
        $controller->render( $target_user );
    }
}
