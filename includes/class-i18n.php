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

    /**
     * Environment (broad) choices: English key => array( 'ja' => ..., 'en' => ... )
     */
    public static function get_env_broad_choices(): array {
        return array(
            'marine'          => array( 'ja' => '海',                                           'en' => 'marine' ),
            'estuarine'       => array( 'ja' => '河川感潮域',                                    'en' => 'estuarine' ),
            'mangrove'        => array( 'ja' => 'マングローブ',                                 'en' => 'mangrove' ),
            'large river'     => array( 'ja' => '大河川下流部',                                 'en' => 'large river' ),
            'small river'     => array( 'ja' => '小河川や大河川上流部',                          'en' => 'small river' ),
            'freshwater lake' => array( 'ja' => '淡水湖',                                       'en' => 'freshwater lake' ),
            'brackish lake'   => array( 'ja' => '汽水湖',                                       'en' => 'brackish lake' ),
            'saline lake'     => array( 'ja' => '塩湖',                                         'en' => 'saline lake' ),
            'sterile water'   => array( 'ja' => '滅菌水',                                       'en' => 'sterile water' ),
        );
    }

    /**
     * Environment (local) choices: English key => array( 'ja' => ..., 'en' => ... )
     */
    public static function get_env_local_choices(): array {
        return array(
            'reservoir'                => array( 'ja' => '人造湖',                               'en' => 'reservoir' ),
            'natural lake'             => array( 'ja' => '天然湖沼',                             'en' => 'natural lake' ),
            'ditch'                    => array( 'ja' => 'ドブ・溝',                             'en' => 'ditch' ),
            'canal'                    => array( 'ja' => '用水路・運河',                         'en' => 'canal' ),
            'canalized stream'         => array( 'ja' => '護岸された川',                         'en' => 'canalized stream' ),
            'river'                    => array( 'ja' => 'その他の川',                           'en' => 'river' ),
            'riffle'                   => array( 'ja' => '川の瀬',                               'en' => 'riffle' ),
            'stream pool'              => array( 'ja' => '川の瀬に接している淵',                 'en' => 'stream pool' ),
            'bayou'                    => array( 'ja' => '湿地帯のゆったり流れる川',             'en' => 'bayou' ),
            'headwater'                => array( 'ja' => '源流部',                               'en' => 'headwater' ),
            'meander'                  => array( 'ja' => '平地を蛇行して流れる川',               'en' => 'meander' ),
            'plunge pool'              => array( 'ja' => '滝壺',                                 'en' => 'plunge pool' ),
            'rapids'                   => array( 'ja' => '急流の川',                             'en' => 'rapids' ),
            'stream mouth'             => array( 'ja' => '川から湖や海や潟への流入口',           'en' => 'stream mouth' ),
            'ditch mouth'              => array( 'ja' => 'ditchから川や湖や海や潟への流入口',    'en' => 'ditch mouth' ),
            'tributary'                => array( 'ja' => '支流',                                 'en' => 'tributary' ),
            'distributary'             => array( 'ja' => '分流',                                 'en' => 'distributary' ),
            'anabranch'                => array( 'ja' => '本流から一旦分かれて再び合流する支流',  'en' => 'anabranch' ),
            'weir'                     => array( 'ja' => '堰・堰堤・頭首工',                     'en' => 'weir' ),
            'ocean'                    => array( 'ja' => '洋',                                   'en' => 'ocean' ),
            'bay'                      => array( 'ja' => '湾',                                   'en' => 'bay' ),
            'lagoon'                   => array( 'ja' => '潟',                                   'en' => 'lagoon' ),
            'mangrove swamp'           => array( 'ja' => 'マングローブ湿地帯',                   'en' => 'mangrove swamp' ),
            'freshwater littoral zone' => array( 'ja' => '淡水沿岸域',                           'en' => 'freshwater littoral zone' ),
            'marine littoral zone'     => array( 'ja' => '海洋沿岸域',                           'en' => 'marine littoral zone' ),
            'littoral zone'            => array( 'ja' => 'その他沿岸域',                         'en' => 'littoral zone' ),
            'limnetic zone'            => array( 'ja' => '湖沼の沖合表層',                       'en' => 'limnetic zone' ),
            'profundal zone'           => array( 'ja' => '湖沼の沖合深層',                       'en' => 'profundal zone' ),
            'marine neritic zone'      => array( 'ja' => '浅海域',                               'en' => 'marine neritic zone' ),
            'marine pelagic zone'      => array( 'ja' => '外洋域',                               'en' => 'marine pelagic zone' ),
            'sandy beach'              => array( 'ja' => '砂浜',                                 'en' => 'sandy beach' ),
            'shingle beach'            => array( 'ja' => '砂利浜・礫浜',                         'en' => 'shingle beach' ),
            'rocky shore'              => array( 'ja' => '磯・岩浜',                             'en' => 'rocky shore' ),
            'revetted shore'           => array( 'ja' => '護岸',                                 'en' => 'revetted shore' ),
            'rocky reef'               => array( 'ja' => '岩礁',                                 'en' => 'rocky reef' ),
            'coral reef'               => array( 'ja' => 'サンゴ礁',                             'en' => 'coral reef' ),
            'mussel reef'              => array( 'ja' => 'カキ礁',                               'en' => 'mussel reef' ),
            'sea grass bed'            => array( 'ja' => '海草藻場',                             'en' => 'sea grass bed' ),
            'kelp forest'              => array( 'ja' => '藻場',                                 'en' => 'kelp forest' ),
            'freshwater algal bloom'   => array( 'ja' => '淡水植物プランクトンのブルーム',       'en' => 'freshwater algal bloom' ),
            'marine algal bloom'       => array( 'ja' => '海洋植物プランクトンのブルーム',       'en' => 'marine algal bloom' ),
            'artificial harbor'        => array( 'ja' => '人工港湾部',                           'en' => 'artificial harbor' ),
            'jetty'                    => array( 'ja' => '突堤',                                 'en' => 'jetty' ),
            'groin'                    => array( 'ja' => '短い突堤',                             'en' => 'groin' ),
            'breakwater'               => array( 'ja' => 'その他の防波堤',                       'en' => 'breakwater' ),
            'quay'                     => array( 'ja' => '埠頭・コンクリート岸壁',               'en' => 'quay' ),
            'tetrapod'                 => array( 'ja' => '消波ブロック',                         'en' => 'tetrapod' ),
            'gabion'                   => array( 'ja' => '蛇籠',                                 'en' => 'gabion' ),
            'pier'                     => array( 'ja' => '桟橋',                                 'en' => 'pier' ),
            'bridge'                   => array( 'ja' => '橋',                                   'en' => 'bridge' ),
            'bar'                      => array( 'ja' => '砂州',                                 'en' => 'bar' ),
            'natural harbor'           => array( 'ja' => '天然の港湾',                           'en' => 'natural harbor' ),
            'tidal pool'               => array( 'ja' => 'タイドプール',                         'en' => 'tidal pool' ),
            'protected area'           => array( 'ja' => '保護区',                               'en' => 'protected area' ),
            'sterile water environment' => array( 'ja' => '滅菌水',                              'en' => 'sterile water environment' ),
            'roadside'                 => array( 'ja' => '道路脇',                               'en' => 'roadside' ),
            'room'                     => array( 'ja' => '室内',                                 'en' => 'room' ),
            'paved parking lot'        => array( 'ja' => '舗装された駐車場',                     'en' => 'paved parking lot' ),
            'field'                    => array( 'ja' => 'その他野外',                           'en' => 'field' ),
        );
    }

    /**
     * Mapping: env_broad key => array of valid env_local keys.
     */
    public static function get_env_local_for_broad(): array {
        return array(
            'marine' => array(
                'ocean', 'bay', 'lagoon', 'marine littoral zone', 'marine neritic zone',
                'marine pelagic zone', 'sandy beach', 'shingle beach', 'rocky shore',
                'revetted shore', 'rocky reef', 'coral reef', 'mussel reef', 'sea grass bed',
                'kelp forest', 'marine algal bloom', 'artificial harbor', 'jetty', 'groin',
                'breakwater', 'quay', 'tetrapod', 'gabion', 'pier', 'bridge', 'bar',
                'natural harbor', 'tidal pool', 'protected area',
            ),
            'estuarine' => array(
                'ditch', 'canal', 'canalized stream', 'river', 'bayou', 'meander',
                'stream mouth', 'ditch mouth', 'tributary', 'distributary', 'anabranch',
                'weir', 'lagoon', 'sandy beach', 'shingle beach', 'rocky shore',
                'revetted shore', 'mussel reef', 'sea grass bed', 'marine algal bloom',
                'artificial harbor', 'jetty', 'groin', 'breakwater', 'quay', 'tetrapod',
                'gabion', 'pier', 'bridge', 'bar', 'natural harbor', 'tidal pool',
                'freshwater littoral zone', 'marine littoral zone', 'littoral zone',
                'protected area',
            ),
            'mangrove' => array(
                'mangrove swamp', 'river', 'canal', 'stream mouth', 'ditch mouth',
                'tributary', 'distributary', 'bay', 'lagoon', 'sandy beach', 'rocky shore',
                'revetted shore', 'mussel reef', 'sea grass bed', 'tidal pool',
                'marine littoral zone', 'littoral zone', 'pier', 'bridge', 'jetty',
                'breakwater', 'protected area',
            ),
            'large river' => array(
                'ditch', 'canal', 'canalized stream', 'river', 'riffle', 'stream pool',
                'bayou', 'headwater', 'meander', 'plunge pool', 'rapids', 'stream mouth',
                'ditch mouth', 'tributary', 'distributary', 'anabranch', 'weir', 'bridge',
                'pier', 'bar', 'freshwater littoral zone', 'littoral zone',
                'freshwater algal bloom', 'revetted shore', 'sandy beach', 'shingle beach',
                'rocky shore', 'gabion', 'tetrapod', 'protected area',
            ),
            'small river' => array(
                'ditch', 'canal', 'canalized stream', 'river', 'riffle', 'stream pool',
                'bayou', 'headwater', 'meander', 'plunge pool', 'rapids', 'stream mouth',
                'ditch mouth', 'tributary', 'distributary', 'anabranch', 'weir', 'bridge',
                'pier', 'bar', 'freshwater littoral zone', 'littoral zone',
                'freshwater algal bloom', 'revetted shore', 'sandy beach', 'shingle beach',
                'rocky shore', 'gabion', 'tetrapod', 'protected area',
            ),
            'freshwater lake' => array(
                'reservoir', 'natural lake', 'lagoon', 'freshwater littoral zone',
                'littoral zone', 'limnetic zone', 'profundal zone', 'sandy beach',
                'shingle beach', 'rocky shore', 'revetted shore', 'freshwater algal bloom',
                'artificial harbor', 'jetty', 'groin', 'breakwater', 'quay', 'tetrapod',
                'gabion', 'pier', 'bridge', 'stream mouth', 'ditch mouth', 'weir',
                'protected area',
            ),
            'brackish lake' => array(
                'reservoir', 'natural lake', 'lagoon', 'freshwater littoral zone',
                'marine littoral zone', 'littoral zone', 'limnetic zone', 'profundal zone',
                'sandy beach', 'shingle beach', 'rocky shore', 'revetted shore',
                'freshwater algal bloom', 'marine algal bloom', 'mussel reef', 'sea grass bed',
                'artificial harbor', 'jetty', 'groin', 'breakwater', 'quay', 'tetrapod',
                'gabion', 'pier', 'bridge', 'stream mouth', 'ditch mouth', 'weir',
                'protected area',
            ),
            'saline lake' => array(
                'reservoir', 'natural lake', 'lagoon', 'littoral zone', 'limnetic zone',
                'profundal zone', 'sandy beach', 'shingle beach', 'rocky shore',
                'revetted shore', 'marine algal bloom', 'artificial harbor', 'jetty',
                'groin', 'breakwater', 'quay', 'tetrapod', 'gabion', 'pier', 'bridge',
                'stream mouth', 'ditch mouth', 'protected area',
            ),
            'sterile water' => array(
                'sterile water environment', 'roadside', 'room', 'paved parking lot', 'field',
            ),
        );
    }

    /**
     * Weather choices: English key => array( 'ja' => ..., 'en' => ... )
     */
    public static function get_weather_choices(): array {
        return array(
            'clear sky' => array( 'ja' => '快晴',    'en' => 'clear sky' ),
            'sunny'     => array( 'ja' => '晴れ',    'en' => 'sunny' ),
            'cloudy'    => array( 'ja' => '曇り',    'en' => 'cloudy' ),
            'foggy'     => array( 'ja' => '霧',      'en' => 'foggy' ),
            'rain'      => array( 'ja' => '雨',      'en' => 'rain' ),
            'hail'      => array( 'ja' => '霰や雹',  'en' => 'hail' ),
            'sleet'     => array( 'ja' => 'みぞれ',  'en' => 'sleet' ),
            'snow'      => array( 'ja' => '雪',      'en' => 'snow' ),
        );
    }

    /**
     * Wind choices: English key => array( 'ja' => ..., 'en' => ... )
     */
    public static function get_wind_choices(): array {
        return array(
            'windy'     => array( 'ja' => '強風',        'en' => 'windy' ),
            'not windy' => array( 'ja' => '無風～弱風',  'en' => 'not windy' ),
        );
    }

    /**
     * Get localized label for a stored value (English key) from a choices array.
     */
    public static function get_choice_label( array $choices, string $value, string $lang = '' ): string {
        if ( '' === $lang ) {
            $lang = self::get_current_language();
        }
        if ( isset( $choices[ $value ] ) ) {
            return $choices[ $value ][ $lang ] ?? $value;
        }
        return $value;
    }

    /**
     * Normalize a localized choice value to its English key.
     * Used when parsing Excel files that may contain Japanese labels.
     */
    public static function normalize_choice_value( array $choices, string $value ): string {
        // Already an English key
        if ( isset( $choices[ $value ] ) ) {
            return $value;
        }
        // Search for matching Japanese label
        foreach ( $choices as $key => $labels ) {
            if ( $labels['ja'] === $value ) {
                return $key;
            }
        }
        return $value;
    }
}
