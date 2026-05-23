<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP-CLI commands for eDNA Survey site provisioning.
 *
 * Registered only when running under WP-CLI (see wp-ednasurvey.php).
 * These apply settings that contain typed booleans / nested arrays, which
 * `wp option patch` cannot set reliably (it stores values as strings).
 */
class EdnaSurvey_CLI {

    /**
     * Apply the recommended GeneratePress, Login Customizer and Powered Cache
     * settings for an eDNA Survey site.
     *
     * Only intended to be run once at install time; values can be changed
     * afterwards from the admin UI. Existing keys are preserved (merged).
     *
     * ## EXAMPLES
     *
     *     wp ednasurvey apply-recommended-settings
     *
     * @subcommand apply-recommended-settings
     *
     * @param array $args       Positional args (unused).
     * @param array $assoc_args Associative args (unused).
     */
    public function apply_recommended_settings( $args, $assoc_args ): void {
        $this->configure_generatepress();
        $this->configure_login_customizer();
        $this->configure_powered_cache();

        if ( class_exists( 'WP_CLI' ) ) {
            WP_CLI::success( 'Recommended theme/plugin settings applied.' );
        }
    }

    /**
     * GeneratePress Layout settings (option: generate_settings).
     * Keys verified against GeneratePress 3.6 inc/defaults.php.
     */
    private function configure_generatepress(): void {
        $overrides = array(
            // Layout > Container
            'container_width'          => '1200',
            'content_layout_setting'   => 'separate-containers',
            'container_alignment'      => 'text',
            // Layout > Header
            'header_layout_setting'    => 'fluid-header',  // Header Width: Full
            'header_inner_width'       => 'contained',
            'header_alignment_setting' => 'left',
            // Layout > Primary Navigation
            'nav_position_setting'     => 'nav-float-right',
            'nav_dropdown_type'        => 'hover',
            'nav_dropdown_direction'   => 'right',
            'nav_search_modal'         => false,
            // Layout > Sidebars
            'layout_setting'           => 'no-sidebar',
            'blog_layout_setting'      => 'no-sidebar',
            'single_layout_setting'    => 'no-sidebar',
            // Layout > Footer
            'footer_layout_setting'    => 'fluid-footer',  // Footer Width: Full
            'footer_inner_width'       => 'contained',
            'footer_widget_setting'    => '0',
            'back_to_top'              => '',              // Disable
            // Layout > Blog
            'post_content'             => 'excerpt',
        );

        $existing = get_option( 'generate_settings', array() );
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }
        update_option( 'generate_settings', array_merge( $existing, $overrides ) );
        $this->log( 'GeneratePress layout settings applied (generate_settings).' );
    }

    /**
     * Login Customizer settings (option: login_customizer_options).
     * Keys verified against the login-customizer plugin source (logincust_*).
     */
    private function configure_login_customizer(): void {
        $overrides = array(
            // Logo
            'logincust_logo_show'            => true,   // "Disable Logo?" = ON
            'logincust_login_title'          => '',
            // Form
            'logincust_form_width'           => '350px',
            'logincust_form_height'          => '300px',
            'logincust_form_padding'         => '30px 25px 35px 25px',
            'logincust_form_radius'          => '0px',
            'logincust_form_shadow_spread'   => '3px',
            // Fields
            'logincust_field_remember_me'    => false,  // "Disable Remember Me?" = OFF
            'logincust_field_width'          => '300px',
            'logincust_field_font_size'      => '24px',
            'logincust_field_border_width'   => '1px',
            'logincust_field_radius'         => '0px',
            'logincust_field_box_shadow'     => false,  // "Disable Box Shadow?" = OFF
            'logincust_field_margin'         => '0px 0px 15px 0px',
            'logincust_field_padding'        => '3px 3px 3px 3px',
            'logincust_field_label_font_size' => '14px',
            // Button
            'logincust_button_height_width'  => 'auto',  // Button Size: Auto
            'logincust_button_font_size'     => '13px',
            'logincust_button_padding'       => '0px 12px 2px 12px',
            'logincust_button_border_width'  => '1px',
            'logincust_button_shadow_spread' => '0px',
            // Other
            'logincust_field_lost_password'  => false,  // "Disable Lost Password?" = OFF
            'logincust_privacy_policy_link'  => true,   // "Disable Privacy policy?" = ON
            'logincust_field_back_blog'      => true,   // "Disable Back to Website?" = ON
            'logincust_other_font_size'      => '13px',
            // Custom CSS & JavaScript
            'logincust_other_css'            => ".cf-turnstile {\n    margin: 20px 0px 20px 0px !important;\n    transform: translateZ(0);\n}",
            'logincust_other_js'             => '',
        );

        $existing = get_option( 'login_customizer_options', array() );
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }
        update_option( 'login_customizer_options', array_merge( $existing, $overrides ) );
        $this->log( 'Login Customizer settings applied (login_customizer_options).' );
    }

    /**
     * Powered Cache settings (option: powered_cache_settings).
     * Keys verified against poweredcache/powered-cache.
     */
    private function configure_powered_cache(): void {
        $existing = get_option( 'powered_cache_settings', array() );
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }
        $existing['disable_wp_embeds']     = true;  // Media Optimization > Disable WordPress Embeds
        $existing['disable_emoji_scripts'] = true;  // Media Optimization > Remove Emoji Scripts
        update_option( 'powered_cache_settings', $existing );
        $this->log( 'Powered Cache media optimization settings applied (powered_cache_settings).' );
    }

    private function log( string $message ): void {
        if ( class_exists( 'WP_CLI' ) ) {
            WP_CLI::log( '  - ' . $message );
        }
    }
}
