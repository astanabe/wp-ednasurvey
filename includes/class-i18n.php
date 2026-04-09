<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_I18n {

    private ?string $detected_locale = null;

    public function __construct() {
        $this->detected_locale = $this->detect_browser_language();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'wp-ednasurvey',
            false,
            dirname( EDNASURVEY_PLUGIN_BASENAME ) . '/languages'
        );
    }

    public function filter_locale( string $locale ): string {
        if ( is_admin() ) {
            return $locale;
        }

        if ( null !== $this->detected_locale ) {
            return $this->detected_locale;
        }

        return $locale;
    }

    private function detect_browser_language(): ?string {
        if ( ! isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
            return null;
        }

        $accept = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) );
        $languages = array();

        // Parse Accept-Language header
        foreach ( explode( ',', $accept ) as $part ) {
            $part = trim( $part );
            $q    = 1.0;
            if ( preg_match( '/;q=([0-9.]+)/', $part, $matches ) ) {
                $q    = (float) $matches[1];
                $part = preg_replace( '/;q=.*$/', '', $part );
            }
            $languages[ trim( $part ) ] = $q;
        }

        arsort( $languages );

        foreach ( array_keys( $languages ) as $lang ) {
            $lang = strtolower( substr( $lang, 0, 2 ) );
            if ( 'ja' === $lang ) {
                return 'ja';
            }
            if ( 'en' === $lang ) {
                return 'en_US';
            }
        }

        return null;
    }

    public static function get_current_language(): string {
        $locale = get_locale();
        if ( str_starts_with( $locale, 'ja' ) ) {
            return 'ja';
        }
        return 'en';
    }
}
