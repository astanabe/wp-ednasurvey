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
     * Get the localized value from a bilingual DB object.
     * Falls back to the other language if the preferred one is empty.
     *
     * @param string $local_value  Value in the local/Japanese column.
     * @param string $en_value     Value in the English column.
     * @return string The appropriate value for the current locale.
     */
    public static function get_localized_field( string $local_value, string $en_value ): string {
        if ( 'ja' === self::get_current_language() ) {
            return $local_value ?: $en_value;
        }
        return $en_value ?: $local_value;
    }

    /**
     * Environment (broad) choices: English key => localized label.
     */
    public static function get_env_broad_choices(): array {
        return array(
            'marine'          => __( 'marine', 'wp-ednasurvey' ),
            'estuarine'       => __( 'estuarine', 'wp-ednasurvey' ),
            'mangrove'        => __( 'mangrove', 'wp-ednasurvey' ),
            'large river'     => __( 'large river', 'wp-ednasurvey' ),
            'small river'     => __( 'small river', 'wp-ednasurvey' ),
            'freshwater lake' => __( 'freshwater lake', 'wp-ednasurvey' ),
            'brackish lake'   => __( 'brackish lake', 'wp-ednasurvey' ),
            'saline lake'     => __( 'saline lake', 'wp-ednasurvey' ),
            'sterile water'   => __( 'sterile water', 'wp-ednasurvey' ),
        );
    }

    /**
     * Environment (local) choices: English key => localized label.
     */
    public static function get_env_local_choices(): array {
        return array(
            // Lake / pond
            'reservoir'                 => __( 'reservoir', 'wp-ednasurvey' ),
            'natural lake'              => __( 'natural lake', 'wp-ednasurvey' ),
            'farm pond'                 => __( 'farm pond', 'wp-ednasurvey' ),
            'natural pond'              => __( 'natural pond', 'wp-ednasurvey' ),
            'garden pond'               => __( 'garden pond', 'wp-ednasurvey' ),
            'oxbow lake'                => __( 'oxbow lake', 'wp-ednasurvey' ),
            // River / channel
            'ditch'                     => __( 'ditch', 'wp-ednasurvey' ),
            'canal'                     => __( 'canal', 'wp-ednasurvey' ),
            'canalized stream'          => __( 'canalized stream', 'wp-ednasurvey' ),
            'river'                     => __( 'river', 'wp-ednasurvey' ),
            'riffle'                    => __( 'riffle', 'wp-ednasurvey' ),
            'stream pool'               => __( 'stream pool', 'wp-ednasurvey' ),
            'bayou'                     => __( 'bayou', 'wp-ednasurvey' ),
            'headwater'                 => __( 'headwater', 'wp-ednasurvey' ),
            'meander'                   => __( 'meander', 'wp-ednasurvey' ),
            'plunge pool'               => __( 'plunge pool', 'wp-ednasurvey' ),
            'rapids'                    => __( 'rapids', 'wp-ednasurvey' ),
            'stream mouth'              => __( 'stream mouth', 'wp-ednasurvey' ),
            'ditch mouth'               => __( 'ditch mouth', 'wp-ednasurvey' ),
            'tributary'                 => __( 'tributary', 'wp-ednasurvey' ),
            'distributary'              => __( 'distributary', 'wp-ednasurvey' ),
            'anabranch'                 => __( 'anabranch', 'wp-ednasurvey' ),
            'weir'                      => __( 'weir', 'wp-ednasurvey' ),
            // Marine / coastal
            'ocean'                     => __( 'ocean', 'wp-ednasurvey' ),
            'bay'                       => __( 'bay', 'wp-ednasurvey' ),
            'lagoon'                    => __( 'lagoon', 'wp-ednasurvey' ),
            'deep sea'                  => __( 'deep sea', 'wp-ednasurvey' ),
            'hydrothermal vent'         => __( 'hydrothermal vent', 'wp-ednasurvey' ),
            'mangrove swamp'            => __( 'mangrove swamp', 'wp-ednasurvey' ),
            // Wetland
            'salt marsh'                => __( 'salt marsh', 'wp-ednasurvey' ),
            'tidal flat'                => __( 'tidal flat', 'wp-ednasurvey' ),
            'tidal creek'               => __( 'tidal creek', 'wp-ednasurvey' ),
            'freshwater marsh'          => __( 'freshwater marsh', 'wp-ednasurvey' ),
            'freshwater swamp'          => __( 'freshwater swamp', 'wp-ednasurvey' ),
            'bog'                       => __( 'bog', 'wp-ednasurvey' ),
            'fen'                       => __( 'fen', 'wp-ednasurvey' ),
            'floodplain'                => __( 'floodplain', 'wp-ednasurvey' ),
            // Zones
            'freshwater littoral zone'  => __( 'freshwater littoral zone', 'wp-ednasurvey' ),
            'marine littoral zone'      => __( 'marine littoral zone', 'wp-ednasurvey' ),
            'littoral zone'             => __( 'littoral zone', 'wp-ednasurvey' ),
            'limnetic zone'             => __( 'limnetic zone', 'wp-ednasurvey' ),
            'profundal zone'            => __( 'profundal zone', 'wp-ednasurvey' ),
            'marine neritic zone'       => __( 'marine neritic zone', 'wp-ednasurvey' ),
            'marine pelagic zone'       => __( 'marine pelagic zone', 'wp-ednasurvey' ),
            // Shore / substrate
            'sandy beach'               => __( 'sandy beach', 'wp-ednasurvey' ),
            'shingle beach'             => __( 'shingle beach', 'wp-ednasurvey' ),
            'rocky shore'               => __( 'rocky shore', 'wp-ednasurvey' ),
            'revetted shore'            => __( 'revetted shore', 'wp-ednasurvey' ),
            // Reef / vegetation
            'rocky reef'                => __( 'rocky reef', 'wp-ednasurvey' ),
            'coral reef'                => __( 'coral reef', 'wp-ednasurvey' ),
            'mussel reef'               => __( 'mussel reef', 'wp-ednasurvey' ),
            'sea grass bed'             => __( 'sea grass bed', 'wp-ednasurvey' ),
            'kelp forest'               => __( 'kelp forest', 'wp-ednasurvey' ),
            'freshwater algal bloom'    => __( 'freshwater algal bloom', 'wp-ednasurvey' ),
            'marine algal bloom'        => __( 'marine algal bloom', 'wp-ednasurvey' ),
            // Structures
            'artificial harbor'         => __( 'artificial harbor', 'wp-ednasurvey' ),
            'jetty'                     => __( 'jetty', 'wp-ednasurvey' ),
            'groin'                     => __( 'groin', 'wp-ednasurvey' ),
            'breakwater'                => __( 'breakwater', 'wp-ednasurvey' ),
            'quay'                      => __( 'quay', 'wp-ednasurvey' ),
            'tetrapod'                  => __( 'tetrapod', 'wp-ednasurvey' ),
            'gabion'                    => __( 'gabion', 'wp-ednasurvey' ),
            'pier'                      => __( 'pier', 'wp-ednasurvey' ),
            'bridge'                    => __( 'bridge', 'wp-ednasurvey' ),
            'bar'                       => __( 'bar', 'wp-ednasurvey' ),
            'natural harbor'            => __( 'natural harbor', 'wp-ednasurvey' ),
            'tidal pool'                => __( 'tidal pool', 'wp-ednasurvey' ),
            // Spring / groundwater
            'spring'                    => __( 'spring', 'wp-ednasurvey' ),
            'spring pool'               => __( 'spring pool', 'wp-ednasurvey' ),
            'cave water'                => __( 'cave water', 'wp-ednasurvey' ),
            'well'                      => __( 'well', 'wp-ednasurvey' ),
            // Thermal
            'hot spring'                => __( 'hot spring', 'wp-ednasurvey' ),
            'hot spring pool'           => __( 'hot spring pool', 'wp-ednasurvey' ),
            'hot spring stream'         => __( 'hot spring stream', 'wp-ednasurvey' ),
            // Agriculture
            'paddy field'               => __( 'paddy field', 'wp-ednasurvey' ),
            'paddy ditch'               => __( 'paddy ditch', 'wp-ednasurvey' ),
            'spring-fed cultivation'    => __( 'spring-fed cultivation', 'wp-ednasurvey' ),
            // Aquaculture
            'aquaculture pond'          => __( 'aquaculture pond', 'wp-ednasurvey' ),
            'fish cage'                 => __( 'fish cage', 'wp-ednasurvey' ),
            'shellfish farm'            => __( 'shellfish farm', 'wp-ednasurvey' ),
            'seaweed farm'              => __( 'seaweed farm', 'wp-ednasurvey' ),
            // Other
            'protected area'            => __( 'protected area', 'wp-ednasurvey' ),
            // Sterile water
            'sterile water environment' => __( 'sterile water environment', 'wp-ednasurvey' ),
            'roadside'                  => __( 'roadside', 'wp-ednasurvey' ),
            'room'                      => __( 'room', 'wp-ednasurvey' ),
            'paved parking lot'         => __( 'paved parking lot', 'wp-ednasurvey' ),
            'field'                     => __( 'field', 'wp-ednasurvey' ),
        );
    }

    /**
     * Mapping: env_broad key => array of valid env_local keys.
     */
    public static function get_env_local_for_broad(): array {
        return array(
            'marine' => array(
                'ocean', 'bay', 'lagoon', 'deep sea', 'hydrothermal vent',
                'salt marsh', 'tidal flat', 'tidal creek',
                'marine littoral zone', 'marine neritic zone', 'marine pelagic zone',
                'sandy beach', 'shingle beach', 'rocky shore', 'revetted shore',
                'rocky reef', 'coral reef', 'mussel reef', 'sea grass bed',
                'kelp forest', 'marine algal bloom',
                'artificial harbor', 'jetty', 'groin', 'breakwater', 'quay',
                'tetrapod', 'gabion', 'pier', 'bridge', 'bar',
                'natural harbor', 'tidal pool',
                'fish cage', 'shellfish farm', 'seaweed farm',
                'protected area',
            ),
            'estuarine' => array(
                'ditch', 'canal', 'canalized stream', 'river', 'bayou', 'meander',
                'stream mouth', 'ditch mouth', 'tributary', 'distributary', 'anabranch',
                'weir',
                'salt marsh', 'tidal flat', 'tidal creek',
                'lagoon', 'sandy beach', 'shingle beach', 'rocky shore', 'revetted shore',
                'mussel reef', 'sea grass bed', 'marine algal bloom',
                'freshwater littoral zone', 'marine littoral zone', 'littoral zone',
                'artificial harbor', 'jetty', 'groin', 'breakwater', 'quay',
                'tetrapod', 'gabion', 'pier', 'bridge', 'bar',
                'natural harbor', 'tidal pool',
                'shellfish farm',
                'protected area',
            ),
            'mangrove' => array(
                'mangrove swamp', 'river', 'canal', 'stream mouth', 'ditch mouth',
                'tributary', 'distributary',
                'tidal flat', 'tidal creek',
                'bay', 'lagoon', 'sandy beach', 'rocky shore', 'revetted shore',
                'mussel reef', 'sea grass bed', 'tidal pool',
                'marine littoral zone', 'littoral zone',
                'pier', 'bridge', 'jetty', 'breakwater',
                'protected area',
            ),
            'large river' => array(
                'ditch', 'canal', 'canalized stream', 'river', 'riffle', 'stream pool',
                'bayou', 'meander', 'plunge pool', 'rapids',
                'stream mouth', 'ditch mouth', 'tributary', 'distributary', 'anabranch',
                'weir', 'bridge', 'pier', 'bar',
                'freshwater marsh', 'freshwater swamp', 'floodplain', 'oxbow lake',
                'freshwater littoral zone', 'littoral zone', 'freshwater algal bloom',
                'revetted shore', 'sandy beach', 'shingle beach', 'rocky shore',
                'gabion', 'tetrapod',
                'protected area',
            ),
            'small river' => array(
                'ditch', 'canal', 'canalized stream', 'river', 'riffle', 'stream pool',
                'bayou', 'headwater', 'meander', 'plunge pool', 'rapids',
                'stream mouth', 'ditch mouth', 'tributary', 'distributary', 'anabranch',
                'weir', 'bridge', 'pier', 'bar',
                'freshwater marsh', 'freshwater swamp', 'floodplain', 'oxbow lake',
                'bog', 'fen',
                'spring', 'cave water', 'hot spring stream', 'spring-fed cultivation',
                'paddy field', 'paddy ditch',
                'freshwater littoral zone', 'littoral zone', 'freshwater algal bloom',
                'revetted shore', 'sandy beach', 'shingle beach', 'rocky shore',
                'gabion', 'tetrapod',
                'protected area',
            ),
            'freshwater lake' => array(
                'reservoir', 'natural lake', 'lagoon',
                'farm pond', 'natural pond', 'garden pond', 'oxbow lake',
                'ditch', 'canal', 'stream mouth', 'ditch mouth', 'weir',
                'freshwater marsh', 'freshwater swamp', 'bog', 'fen',
                'spring', 'spring pool', 'cave water', 'well',
                'hot spring', 'hot spring pool',
                'paddy field',
                'aquaculture pond', 'fish cage',
                'freshwater littoral zone', 'littoral zone',
                'limnetic zone', 'profundal zone',
                'sandy beach', 'shingle beach', 'rocky shore', 'revetted shore',
                'bar', 'freshwater algal bloom',
                'artificial harbor', 'jetty', 'groin', 'breakwater', 'quay',
                'tetrapod', 'gabion', 'pier', 'bridge',
                'protected area',
            ),
            'brackish lake' => array(
                'reservoir', 'natural lake', 'lagoon',
                'freshwater marsh',
                'aquaculture pond', 'fish cage',
                'freshwater littoral zone', 'marine littoral zone', 'littoral zone',
                'limnetic zone', 'profundal zone',
                'sandy beach', 'shingle beach', 'rocky shore', 'revetted shore',
                'bar', 'freshwater algal bloom', 'marine algal bloom',
                'mussel reef', 'sea grass bed',
                'artificial harbor', 'jetty', 'groin', 'breakwater', 'quay',
                'tetrapod', 'gabion', 'pier', 'bridge',
                'stream mouth', 'ditch mouth', 'weir',
                'protected area',
            ),
            'saline lake' => array(
                'reservoir', 'natural lake', 'lagoon',
                'littoral zone', 'limnetic zone', 'profundal zone',
                'sandy beach', 'shingle beach', 'rocky shore', 'revetted shore',
                'marine algal bloom',
                'artificial harbor', 'jetty', 'groin', 'breakwater', 'quay',
                'tetrapod', 'gabion', 'pier', 'bridge',
                'stream mouth', 'ditch mouth',
                'protected area',
            ),
            'sterile water' => array(
                'sterile water environment', 'roadside', 'room', 'paved parking lot', 'field',
            ),
        );
    }

    /**
     * Conflict groups for env_local: each sub-array lists values
     * that cannot be selected together (at most one from each group).
     */
    public static function get_env_local_conflict_groups(): array {
        return array(
            // Water body type
            array( 'ocean', 'bay' ),
            array( 'ocean', 'lagoon' ),
            // Lake / pond origin (exclusive)
            array( 'reservoir', 'natural lake', 'farm pond', 'natural pond', 'garden pond', 'oxbow lake' ),
            // Harbor origin
            array( 'artificial harbor', 'natural harbor' ),
            // Shore substrate (exclusive)
            array( 'sandy beach', 'shingle beach', 'rocky shore', 'revetted shore' ),
            // Salinity
            array( 'freshwater littoral zone', 'marine littoral zone' ),
            array( 'freshwater algal bloom', 'marine algal bloom' ),
            // Depth / distance zones
            array( 'limnetic zone', 'profundal zone' ),
            array( 'marine neritic zone', 'marine pelagic zone' ),
            // River position / morphology
            array( 'headwater', 'stream mouth' ),
            array( 'headwater', 'meander' ),
            array( 'headwater', 'bayou' ),
            array( 'rapids', 'bayou' ),
            array( 'riffle', 'bayou' ),
            // Flow direction
            array( 'tributary', 'distributary' ),
            // Waterway type (exclusive)
            array( 'river', 'canal', 'ditch', 'canalized stream' ),
            // Peatland type (exclusive)
            array( 'bog', 'fen' ),
            // Spring temperature (exclusive)
            array( 'spring', 'hot spring' ),
            array( 'spring pool', 'hot spring pool' ),
        );
    }

    /**
     * Weather choices: English key => localized label.
     */
    public static function get_weather_choices(): array {
        return array(
            'clear sky' => __( 'clear sky', 'wp-ednasurvey' ),
            'sunny'     => __( 'sunny', 'wp-ednasurvey' ),
            'cloudy'    => __( 'cloudy', 'wp-ednasurvey' ),
            'foggy'     => __( 'foggy', 'wp-ednasurvey' ),
            'rain'      => __( 'rain', 'wp-ednasurvey' ),
            'hail'      => __( 'hail', 'wp-ednasurvey' ),
            'sleet'     => __( 'sleet', 'wp-ednasurvey' ),
            'snow'      => __( 'snow', 'wp-ednasurvey' ),
        );
    }

    /**
     * Wind choices: English key => localized label.
     */
    public static function get_wind_choices(): array {
        return array(
            'windy'     => __( 'windy', 'wp-ednasurvey' ),
            'not windy' => __( 'not windy', 'wp-ednasurvey' ),
        );
    }

    /**
     * Get localized label for a stored value (English key) from a choices array.
     */
    public static function get_choice_label( array $choices, string $value, string $lang = '' ): string {
        if ( isset( $choices[ $value ] ) ) {
            return $choices[ $value ];
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
        // Search reverse map for Japanese label, but validate against provided choices
        $ja_map = self::get_ja_reverse_map();
        if ( isset( $ja_map[ $value ] ) && isset( $choices[ $ja_map[ $value ] ] ) ) {
            return $ja_map[ $value ];
        }
        // Fallback: search by localized label in the provided choices
        $key = array_search( $value, $choices, true );
        if ( false !== $key ) {
            return $key;
        }
        return $value;
    }

    /**
     * Reverse map: Japanese label => English key.
     * Used by normalize_choice_value() for Excel import compatibility.
     */
    private static function get_ja_reverse_map(): array {
        return array(
            // env_broad
            '海'                                 => 'marine',
            '汽水域'                              => 'estuarine',
            '河川感潮域'                          => 'estuarine',
            'マングローブ'                        => 'mangrove',
            '大河川下流部'                        => 'large river',
            '小河川や大河川上流部'                 => 'small river',
            '淡水湖'                              => 'freshwater lake',
            '汽水湖'                              => 'brackish lake',
            '塩湖'                                => 'saline lake',
            '滅菌水'                              => 'sterile water',
            // env_local
            '人造湖'                              => 'reservoir',
            '天然湖沼'                            => 'natural lake',
            'ドブ・溝'                            => 'ditch',
            '用水路・運河'                        => 'canal',
            '護岸された川'                        => 'canalized stream',
            'その他の川'                          => 'river',
            '川の瀬'                              => 'riffle',
            '川の瀬に接している淵'                => 'stream pool',
            '湿地帯のゆったり流れる川'            => 'bayou',
            '源流部'                              => 'headwater',
            '平地を蛇行して流れる川'              => 'meander',
            '滝壺'                                => 'plunge pool',
            '急流の川'                            => 'rapids',
            '川から湖や海や潟への流入口'          => 'stream mouth',
            'ドブ・溝から川や湖や海や潟への流入口' => 'ditch mouth',
            '支流'                                => 'tributary',
            '分流'                                => 'distributary',
            '本流から一旦分かれて再び合流する支流' => 'anabranch',
            '堰・堰堤・頭首工'                    => 'weir',
            '洋'                                  => 'ocean',
            '湾'                                  => 'bay',
            '潟'                                  => 'lagoon',
            'マングローブ湿地帯'                  => 'mangrove swamp',
            '淡水沿岸域'                          => 'freshwater littoral zone',
            '海洋沿岸域'                          => 'marine littoral zone',
            'その他沿岸域'                        => 'littoral zone',
            '湖沼の沖合表層'                      => 'limnetic zone',
            '湖沼の沖合深層'                      => 'profundal zone',
            '浅海域'                              => 'marine neritic zone',
            '外洋域'                              => 'marine pelagic zone',
            '砂浜'                                => 'sandy beach',
            '砂利浜・礫浜'                        => 'shingle beach',
            '磯・岩浜'                            => 'rocky shore',
            '護岸'                                => 'revetted shore',
            '岩礁'                                => 'rocky reef',
            'サンゴ礁'                            => 'coral reef',
            'カキ礁'                              => 'mussel reef',
            '海草藻場'                            => 'sea grass bed',
            '藻場'                                => 'kelp forest',
            '淡水植物プランクトンのブルーム'      => 'freshwater algal bloom',
            '海洋植物プランクトンのブルーム'      => 'marine algal bloom',
            '人工港湾部'                          => 'artificial harbor',
            '突堤'                                => 'jetty',
            '短い突堤'                            => 'groin',
            'その他の防波堤'                      => 'breakwater',
            '埠頭・コンクリート岸壁'              => 'quay',
            '消波ブロック'                        => 'tetrapod',
            '蛇籠'                                => 'gabion',
            '桟橋'                                => 'pier',
            '橋'                                  => 'bridge',
            '砂州'                                => 'bar',
            '天然の港湾'                          => 'natural harbor',
            'タイドプール'                        => 'tidal pool',
            '保護区'                              => 'protected area',
            // New env_local (pond)
            'ため池'                              => 'farm pond',
            '天然池'                              => 'natural pond',
            '庭園池・公園池'                      => 'garden pond',
            '三日月湖'                            => 'oxbow lake',
            // New env_local (marine / deep)
            '深海'                                => 'deep sea',
            '海底熱水噴出孔'                      => 'hydrothermal vent',
            // New env_local (wetland)
            '塩性湿地'                            => 'salt marsh',
            '干潟'                                => 'tidal flat',
            '潮汐クリーク'                        => 'tidal creek',
            '淡水湿地（草本）'                    => 'freshwater marsh',
            '淡水湿地（木本）'                    => 'freshwater swamp',
            '高層湿原'                            => 'bog',
            '低層湿原'                            => 'fen',
            '氾濫原'                              => 'floodplain',
            // New env_local (spring / groundwater)
            '湧水'                                => 'spring',
            '湧水池'                              => 'spring pool',
            '洞窟内水域'                          => 'cave water',
            '井戸'                                => 'well',
            // New env_local (thermal)
            '温泉'                                => 'hot spring',
            '温泉池'                              => 'hot spring pool',
            '温泉流水'                            => 'hot spring stream',
            // New env_local (agriculture)
            '水田・湛水農地'                      => 'paddy field',
            '水田水路'                            => 'paddy ditch',
            '湧水栽培地'                          => 'spring-fed cultivation',
            // New env_local (aquaculture)
            '養殖池'                              => 'aquaculture pond',
            '養殖いけす'                          => 'fish cage',
            '貝類養殖場'                          => 'shellfish farm',
            '海藻養殖場'                          => 'seaweed farm',
            // sterile water
            '道路脇'                              => 'roadside',
            '室内'                                => 'room',
            '舗装された駐車場'                    => 'paved parking lot',
            'その他野外'                          => 'field',
            // weather
            '快晴'                                => 'clear sky',
            '晴れ'                                => 'sunny',
            '曇り'                                => 'cloudy',
            '霧'                                  => 'foggy',
            '雨'                                  => 'rain',
            '霰や雹'                              => 'hail',
            'みぞれ'                              => 'sleet',
            '雪'                                  => 'snow',
            // wind
            '強風'                                => 'windy',
            '無風～弱風'                          => 'not windy',
        );
    }
}
