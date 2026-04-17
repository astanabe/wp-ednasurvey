<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Central authority for field definitions, modes, labels, and configuration.
 *
 * All code that needs to know about field visibility, required status, labels,
 * or default values MUST go through this registry.
 */
class EdnaSurvey_Field_Registry {

    // Field groups
    const GROUP_A = 'always_required';       // Always required, always visible
    const GROUP_B = 'required_with_default'; // Always required, default value configurable
    const GROUP_C = 'configurable';          // 4-mode configurable

    // Field modes
    const MODE_REQUIRED        = 'required';        // Required, with input field
    const MODE_ENABLED         = 'enabled';          // Optional, with input field
    const MODE_REQUIRED_HIDDEN = 'required_hidden';  // No input, default value auto-saved
    const MODE_DISABLED        = 'disabled';         // Not shown, not saved

    private array $fields = array();
    private array $settings;
    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Reset singleton (call after saving settings to force reload).
     */
    public static function reset(): void {
        self::$instance = null;
    }

    private function __construct() {
        $this->settings = get_option( 'ednasurvey_settings', array() );
        $this->init_standard_fields();
        $this->init_custom_fields();
    }

    // -----------------------------------------------------------------------
    // Initialization
    // -----------------------------------------------------------------------

    private function init_standard_fields(): void {
        $field_config = $this->settings['field_config'] ?? array();
        $definitions  = self::get_standard_field_definitions();

        foreach ( $definitions as $key => $def ) {
            $saved = $field_config[ $key ] ?? array();
            $field = $def;
            // Overlay any admin-configured values
            foreach ( $saved as $sk => $sv ) {
                if ( null !== $sv ) {
                    $field[ $sk ] = $sv;
                }
            }
            $this->fields[ $key ] = $field;
        }

        // Apply group modes (override individual modes for grouped fields)
        $collectors_mode = $this->settings['collectors_group_mode'] ?? self::MODE_ENABLED;
        if ( ! in_array( $collectors_mode, self::valid_modes(), true ) ) {
            $collectors_mode = self::MODE_ENABLED;
        }
        foreach ( array( 'collector2', 'collector3', 'collector4', 'collector5' ) as $k ) {
            $this->fields[ $k ]['mode'] = $collectors_mode;
        }

        $env_local_mode = $this->settings['env_local_group_mode'] ?? self::MODE_ENABLED;
        if ( ! in_array( $env_local_mode, self::valid_modes(), true ) ) {
            $env_local_mode = self::MODE_ENABLED;
        }
        for ( $i = 2; $i <= 7; $i++ ) {
            $this->fields[ 'env_local' . $i ]['mode'] = $env_local_mode;
        }
    }

    private function init_custom_fields(): void {
        $model         = new EdnaSurvey_Custom_Field_Model();
        $custom_fields = $model->get_all_fields();

        foreach ( $custom_fields as $cf ) {
            $key  = 'custom_' . $cf->field_key;
            $mode = $cf->field_mode ?? self::MODE_ENABLED;
            if ( ! in_array( $mode, self::valid_modes(), true ) ) {
                $mode = self::MODE_ENABLED;
            }

            $this->fields[ $key ] = array(
                'key'               => $key,
                'db_column'         => null, // EAV storage
                'group'             => self::GROUP_C,
                'field_type'        => $cf->field_type ?: 'text',
                'mode'              => $mode,
                'label_local'       => $cf->label_local ?? '',
                'label_en'          => $cf->label_en ?? '',
                'description_local' => $cf->description_local ?? '',
                'description_en'    => $cf->description_en ?? '',
                'example_local'     => $cf->example_local ?? '',
                'example_en'        => $cf->example_en ?? '',
                'default_value'     => $cf->default_value ?? '',
                'field_options'     => $cf->field_options ? json_decode( $cf->field_options, true ) : null,
                'is_custom'         => true,
                'custom_field_id'   => (int) $cf->id,
                'custom_field_key'  => $cf->field_key,
                'sort_order'        => (int) $cf->sort_order,
            );
        }
    }

    // -----------------------------------------------------------------------
    // Standard field definitions (hardcoded defaults)
    // -----------------------------------------------------------------------

    /**
     * Return the canonical definition for every standard (non-custom) field.
     *
     * These are the defaults used when no admin-configured override exists.
     * The array order is the canonical display order.
     */
    public static function get_standard_field_definitions(): array {
        return array(
            // ----- Group A: Always required -----
            'survey_date'    => array(
                'key'               => 'survey_date',
                'db_column'         => 'survey_date',
                'group'             => self::GROUP_A,
                'field_type'        => 'date',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '調査日',
                'label_en'          => 'Survey date',
                'description_local' => 'YYYY-MM-DD形式',
                'description_en'    => 'YYYY-MM-DD format',
                'example_local'     => '2024-01-15',
                'example_en'        => '2024-01-15',
                'is_custom'         => false,
            ),
            'survey_time'    => array(
                'key'               => 'survey_time',
                'db_column'         => 'survey_time',
                'group'             => self::GROUP_A,
                'field_type'        => 'time',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '調査時刻',
                'label_en'          => 'Survey time',
                'description_local' => 'hh:mm（24時間制）',
                'description_en'    => 'hh:mm (24-hour)',
                'example_local'     => '10:30',
                'example_en'        => '10:30',
                'is_custom'         => false,
            ),
            'latitude'       => array(
                'key'               => 'latitude',
                'db_column'         => 'latitude',
                'group'             => self::GROUP_A,
                'field_type'        => 'decimal',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '緯度',
                'label_en'          => 'Latitude',
                'description_local' => '-90〜90',
                'description_en'    => '-90 to 90',
                'example_local'     => '35.676200',
                'example_en'        => '35.676200',
                'is_custom'         => false,
            ),
            'longitude'      => array(
                'key'               => 'longitude',
                'db_column'         => 'longitude',
                'group'             => self::GROUP_A,
                'field_type'        => 'decimal',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '経度',
                'label_en'          => 'Longitude',
                'description_local' => '-180〜180',
                'description_en'    => '-180 to 180',
                'example_local'     => '139.650300',
                'example_en'        => '139.650300',
                'is_custom'         => false,
            ),
            'sitename_local' => array(
                'key'               => 'sitename_local',
                'db_column'         => 'sitename_local',
                'group'             => self::GROUP_A,
                'field_type'        => 'text',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '地点名',
                'label_en'          => 'Site name (local)',
                'description_local' => '都道府県・地域名と具体的な場所を含むこと',
                'description_en'    => 'Include prefecture/region and specific location',
                'example_local'     => '東京湾 お台場',
                'example_en'        => 'Tokyo Bay Odaiba',
                'is_custom'         => false,
            ),
            'sitename_en'    => array(
                'key'               => 'sitename_en',
                'db_column'         => 'sitename_en',
                'group'             => self::GROUP_A,
                'field_type'        => 'text',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '地点名 (英語)',
                'label_en'          => 'Site name (English)',
                'description_local' => 'アルファベットのみ使用',
                'description_en'    => 'Alphabet characters only',
                'example_local'     => 'Tokyo Bay Odaiba',
                'example_en'        => 'Tokyo Bay Odaiba',
                'is_custom'         => false,
            ),

            // ----- Group B: Always required, default value configurable -----
            'sample_id'      => array(
                'key'               => 'sample_id',
                'db_column'         => 'sample_id',
                'group'             => self::GROUP_B,
                'field_type'        => 'text',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => 'サンプルID',
                'label_en'          => 'Sample ID',
                'description_local' => '任意の識別文字列',
                'description_en'    => 'Assigned identifier string',
                'example_local'     => 'S001',
                'example_en'        => 'S001',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'correspondence' => array(
                'key'               => 'correspondence',
                'db_column'         => 'correspondence',
                'group'             => self::GROUP_B,
                'field_type'        => 'text',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '代表者',
                'label_en'          => 'Representative',
                'description_local' => '氏名',
                'description_en'    => 'Full name',
                'example_local'     => '東北太郎',
                'example_en'        => 'Taro Tohoku',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'collector1'     => array(
                'key'               => 'collector1',
                'db_column'         => 'collector1',
                'group'             => self::GROUP_B,
                'field_type'        => 'text',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '採取者1',
                'label_en'          => 'Collector 1',
                'description_local' => '氏名',
                'description_en'    => 'Full name',
                'example_local'     => '東北太郎',
                'example_en'        => 'Taro Tohoku',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'env_broad'      => array(
                'key'               => 'env_broad',
                'db_column'         => 'env_broad',
                'group'             => self::GROUP_B,
                'field_type'        => 'select',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '環境 (広域)',
                'label_en'          => 'Environment (Broad)',
                'description_local' => 'リストから選択',
                'description_en'    => 'Select from list',
                'example_local'     => '海',
                'example_en'        => 'marine',
                'default_value'     => '',
                'choices_method'    => 'get_env_broad_choices',
                'is_custom'         => false,
            ),
            'env_local1'     => array(
                'key'               => 'env_local1',
                'db_column'         => 'env_local1',
                'group'             => self::GROUP_B,
                'field_type'        => 'select',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '環境 (局所) 1',
                'label_en'          => 'Environment (Local) 1',
                'description_local' => '環境(広域)に基づきリストから選択',
                'description_en'    => 'Select from list based on Env. (Broad)',
                'example_local'     => '湾',
                'example_en'        => 'bay',
                'default_value'     => '',
                'choices_method'    => 'get_env_local_choices',
                'depends_on'        => 'env_broad',
                'is_custom'         => false,
            ),

            // ----- Group C: Configurable (individual) -----
            'env_medium'     => array(
                'key'               => 'env_medium',
                'db_column'         => 'env_medium',
                'group'             => self::GROUP_C,
                'field_type'        => 'text',
                'mode'              => self::MODE_REQUIRED_HIDDEN,
                'label_local'       => '媒体',
                'label_en'          => 'Medium',
                'description_local' => '採集対象',
                'description_en'    => 'Environmental material(s)',
                'example_local'     => 'liquid water',
                'example_en'        => 'liquid water',
                'default_value'     => 'liquid water',
                'is_custom'         => false,
            ),
            'weather'        => array(
                'key'               => 'weather',
                'db_column'         => 'weather',
                'group'             => self::GROUP_C,
                'field_type'        => 'select',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '天気',
                'label_en'          => 'Weather',
                'description_local' => 'リストから選択',
                'description_en'    => 'Select from list',
                'example_local'     => '晴れ',
                'example_en'        => 'sunny',
                'default_value'     => '',
                'choices_method'    => 'get_weather_choices',
                'is_custom'         => false,
            ),
            'wind'           => array(
                'key'               => 'wind',
                'db_column'         => 'wind',
                'group'             => self::GROUP_C,
                'field_type'        => 'select',
                'mode'              => self::MODE_REQUIRED,
                'label_local'       => '風',
                'label_en'          => 'Wind',
                'description_local' => 'リストから選択。基準: ろ過に使用するシリンジやフィルターホルダーが風で継続的に動かされるかどうか',
                'description_en'    => 'Select from list. Criterion: whether a syringe or filter holder used for filtration is continuously moved by the wind',
                'example_local'     => '無風～弱風',
                'example_en'        => 'not windy',
                'default_value'     => '',
                'choices_method'    => 'get_wind_choices',
                'is_custom'         => false,
            ),
            'watervol1'      => array(
                'key'               => 'watervol1',
                'db_column'         => 'watervol1',
                'group'             => self::GROUP_C,
                'field_type'        => 'number',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => 'ろ過水量1 (mL)',
                'label_en'          => 'Filtered water volume 1 (mL)',
                'description_local' => '整数値',
                'description_en'    => 'Integer',
                'example_local'     => '1000',
                'example_en'        => '1000',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'watervol2'      => array(
                'key'               => 'watervol2',
                'db_column'         => 'watervol2',
                'group'             => self::GROUP_C,
                'field_type'        => 'number',
                'mode'              => self::MODE_ENABLED,
                'label_en'          => 'Filtered water volume 2 (mL)',
                'label_local'       => 'ろ過水量2 (mL)',
                'description_local' => '整数値',
                'description_en'    => 'Integer',
                'example_local'     => '1000',
                'example_en'        => '1000',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'airvol1'        => array(
                'key'               => 'airvol1',
                'db_column'         => 'airvol1',
                'group'             => self::GROUP_C,
                'field_type'        => 'number',
                'mode'              => self::MODE_DISABLED,
                'label_local'       => '濾過空気量1 (mL)',
                'label_en'          => 'Filtered air volume 1 (mL)',
                'description_local' => '整数値',
                'description_en'    => 'Integer',
                'example_local'     => '500',
                'example_en'        => '500',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'airvol2'        => array(
                'key'               => 'airvol2',
                'db_column'         => 'airvol2',
                'group'             => self::GROUP_C,
                'field_type'        => 'number',
                'mode'              => self::MODE_DISABLED,
                'label_local'       => '濾過空気量2 (mL)',
                'label_en'          => 'Filtered air volume 2 (mL)',
                'description_local' => '整数値',
                'description_en'    => 'Integer',
                'example_local'     => '500',
                'example_en'        => '500',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'weight1'        => array(
                'key'               => 'weight1',
                'db_column'         => 'weight1',
                'group'             => self::GROUP_C,
                'field_type'        => 'decimal',
                'mode'              => self::MODE_DISABLED,
                'label_local'       => 'サンプル重量1 (g)',
                'label_en'          => 'Sample weight 1 (g)',
                'description_local' => '小数点以下2桁',
                'description_en'    => 'Up to 2 decimal places',
                'example_local'     => '1.50',
                'example_en'        => '1.50',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'weight2'        => array(
                'key'               => 'weight2',
                'db_column'         => 'weight2',
                'group'             => self::GROUP_C,
                'field_type'        => 'decimal',
                'mode'              => self::MODE_DISABLED,
                'label_local'       => 'サンプル重量2 (g)',
                'label_en'          => 'Sample weight 2 (g)',
                'description_local' => '小数点以下2桁',
                'description_en'    => 'Up to 2 decimal places',
                'example_local'     => '1.50',
                'example_en'        => '1.50',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'filter_name'    => array(
                'key'               => 'filter_name',
                'db_column'         => 'filter_name',
                'group'             => self::GROUP_C,
                'field_type'        => 'text',
                'mode'              => self::MODE_REQUIRED_HIDDEN,
                'label_local'       => 'フィルター名',
                'label_en'          => 'Filter name',
                'description_local' => '使用したフィルターの製品名・型番',
                'description_en'    => 'Product name and model of filter used',
                'example_local'     => 'Sterivex-HV',
                'example_en'        => 'Sterivex-HV',
                'default_value'     => 'Sterivex-HV',
                'is_custom'         => false,
            ),
            'notes'          => array(
                'key'               => 'notes',
                'db_column'         => 'notes',
                'group'             => self::GROUP_C,
                'field_type'        => 'textarea',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '備考',
                'label_en'          => 'Notes',
                'description_local' => '自由記述',
                'description_en'    => 'Free text',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'is_custom'         => false,
            ),

            // ----- Group C: Grouped — collectors (mode shared) -----
            'collector2'     => array(
                'key'               => 'collector2',
                'db_column'         => 'collector2',
                'group'             => self::GROUP_C,
                'group_key'         => 'collectors',
                'field_type'        => 'text',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '採取者2',
                'label_en'          => 'Collector 2',
                'description_local' => '氏名',
                'description_en'    => 'Full name',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'collector3'     => array(
                'key'               => 'collector3',
                'db_column'         => 'collector3',
                'group'             => self::GROUP_C,
                'group_key'         => 'collectors',
                'field_type'        => 'text',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '採取者3',
                'label_en'          => 'Collector 3',
                'description_local' => '氏名',
                'description_en'    => 'Full name',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'collector4'     => array(
                'key'               => 'collector4',
                'db_column'         => 'collector4',
                'group'             => self::GROUP_C,
                'group_key'         => 'collectors',
                'field_type'        => 'text',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '採取者4',
                'label_en'          => 'Collector 4',
                'description_local' => '氏名',
                'description_en'    => 'Full name',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'is_custom'         => false,
            ),
            'collector5'     => array(
                'key'               => 'collector5',
                'db_column'         => 'collector5',
                'group'             => self::GROUP_C,
                'group_key'         => 'collectors',
                'field_type'        => 'text',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '採取者5',
                'label_en'          => 'Collector 5',
                'description_local' => '氏名',
                'description_en'    => 'Full name',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'is_custom'         => false,
            ),

            // ----- Group C: Grouped — env_local (mode shared) -----
            'env_local2'     => array(
                'key'               => 'env_local2',
                'db_column'         => 'env_local2',
                'group'             => self::GROUP_C,
                'group_key'         => 'env_local',
                'field_type'        => 'select',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '環境 (局所) 2',
                'label_en'          => 'Environment (Local) 2',
                'description_local' => '環境(広域)に基づきリストから選択',
                'description_en' => 'Select from list based on Env. (Broad)',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'choices_method'    => 'get_env_local_choices',
                'depends_on'        => 'env_broad',
                'is_custom'         => false,
            ),
            'env_local3'     => array(
                'key'               => 'env_local3',
                'db_column'         => 'env_local3',
                'group'             => self::GROUP_C,
                'group_key'         => 'env_local',
                'field_type'        => 'select',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '環境 (局所) 3',
                'label_en'          => 'Environment (Local) 3',
                'description_local' => '環境(広域)に基づきリストから選択',
                'description_en' => 'Select from list based on Env. (Broad)',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'choices_method'    => 'get_env_local_choices',
                'depends_on'        => 'env_broad',
                'is_custom'         => false,
            ),
            'env_local4'     => array(
                'key'               => 'env_local4',
                'db_column'         => 'env_local4',
                'group'             => self::GROUP_C,
                'group_key'         => 'env_local',
                'field_type'        => 'select',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '環境 (局所) 4',
                'label_en'          => 'Environment (Local) 4',
                'description_local' => '環境(広域)に基づきリストから選択',
                'description_en' => 'Select from list based on Env. (Broad)',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'choices_method'    => 'get_env_local_choices',
                'depends_on'        => 'env_broad',
                'is_custom'         => false,
            ),
            'env_local5'     => array(
                'key'               => 'env_local5',
                'db_column'         => 'env_local5',
                'group'             => self::GROUP_C,
                'group_key'         => 'env_local',
                'field_type'        => 'select',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '環境 (局所) 5',
                'label_en'          => 'Environment (Local) 5',
                'description_local' => '環境(広域)に基づきリストから選択',
                'description_en' => 'Select from list based on Env. (Broad)',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'choices_method'    => 'get_env_local_choices',
                'depends_on'        => 'env_broad',
                'is_custom'         => false,
            ),
            'env_local6'     => array(
                'key'               => 'env_local6',
                'db_column'         => 'env_local6',
                'group'             => self::GROUP_C,
                'group_key'         => 'env_local',
                'field_type'        => 'select',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '環境 (局所) 6',
                'label_en'          => 'Environment (Local) 6',
                'description_local' => '環境(広域)に基づきリストから選択',
                'description_en' => 'Select from list based on Env. (Broad)',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'choices_method'    => 'get_env_local_choices',
                'depends_on'        => 'env_broad',
                'is_custom'         => false,
            ),
            'env_local7'     => array(
                'key'               => 'env_local7',
                'db_column'         => 'env_local7',
                'group'             => self::GROUP_C,
                'group_key'         => 'env_local',
                'field_type'        => 'select',
                'mode'              => self::MODE_ENABLED,
                'label_local'       => '環境 (局所) 7',
                'label_en'          => 'Environment (Local) 7',
                'description_local' => '環境(広域)に基づきリストから選択',
                'description_en' => 'Select from list based on Env. (Broad)',
                'example_local'     => '',
                'example_en'        => '',
                'default_value'     => '',
                'choices_method'    => 'get_env_local_choices',
                'depends_on'        => 'env_broad',
                'is_custom'         => false,
            ),
        );
    }

    // -----------------------------------------------------------------------
    // Public query API — field retrieval
    // -----------------------------------------------------------------------

    /**
     * Get a single field definition by key.
     *
     * @return array|null Field definition array, or null if not found.
     */
    public function get_field( string $key ): ?array {
        return $this->fields[ $key ] ?? null;
    }

    /**
     * Get all field definitions (standard + custom), keyed by field key.
     */
    public function get_all_fields(): array {
        return $this->fields;
    }

    /**
     * Get only standard (non-custom) field definitions.
     */
    public function get_standard_fields(): array {
        return array_filter( $this->fields, fn( $f ) => empty( $f['is_custom'] ) );
    }

    /**
     * Get only custom field definitions.
     */
    public function get_custom_fields(): array {
        return array_filter( $this->fields, fn( $f ) => ! empty( $f['is_custom'] ) );
    }

    /**
     * Get fields belonging to a specific group.
     */
    public function get_fields_by_group( string $group ): array {
        return array_filter( $this->fields, fn( $f ) => ( $f['group'] ?? '' ) === $group );
    }

    /**
     * Get fields that have an input element on the form (mode = required or enabled).
     * Includes standard + custom fields in display order.
     */
    public function get_fields_with_input(): array {
        return array_filter( $this->fields, fn( $f ) => $this->field_has_input( $f ) );
    }

    /**
     * Get fields that are "active" (mode != disabled).
     * These fields will have data saved (either from input or default value).
     */
    public function get_active_fields(): array {
        return array_filter( $this->fields, fn( $f ) => ( $f['mode'] ?? self::MODE_DISABLED ) !== self::MODE_DISABLED );
    }

    // -----------------------------------------------------------------------
    // Public query API — mode & visibility
    // -----------------------------------------------------------------------

    public function get_mode( string $key ): string {
        return $this->fields[ $key ]['mode'] ?? self::MODE_DISABLED;
    }

    /**
     * Does this field have a visible input element on the form?
     */
    public function has_input( string $key ): bool {
        $field = $this->fields[ $key ] ?? null;
        return null !== $field && $this->field_has_input( $field );
    }

    /**
     * Is user input required for this field? (mode = required)
     */
    public function is_required( string $key ): bool {
        return self::MODE_REQUIRED === ( $this->fields[ $key ]['mode'] ?? '' );
    }

    /**
     * Is this field active (not disabled)?
     * Active fields have data saved — either from user input or default value.
     */
    public function is_active( string $key ): bool {
        return self::MODE_DISABLED !== ( $this->fields[ $key ]['mode'] ?? self::MODE_DISABLED );
    }

    /**
     * Should this field appear as a column in the Excel template?
     * Same logic as has_input(): only fields with user input get columns.
     */
    public function is_in_excel( string $key ): bool {
        return $this->has_input( $key );
    }

    // -----------------------------------------------------------------------
    // Public query API — labels, descriptions, examples
    // -----------------------------------------------------------------------

    /**
     * Get the configured local language code.
     */
    public function get_local_language(): string {
        return $this->settings['local_language'] ?? 'ja';
    }

    /**
     * Get the localized label for a field.
     *
     * @param string      $key  Field key.
     * @param string|null $lang 'local', 'en', or null for auto-detect.
     */
    public function get_label( string $key, ?string $lang = null ): string {
        $field = $this->fields[ $key ] ?? null;
        if ( null === $field ) {
            return $key;
        }
        return $this->resolve_localized( $field, 'label', $lang );
    }

    /**
     * Get the localized description for a field.
     */
    public function get_description( string $key, ?string $lang = null ): string {
        $field = $this->fields[ $key ] ?? null;
        if ( null === $field ) {
            return '';
        }
        return $this->resolve_localized( $field, 'description', $lang );
    }

    /**
     * Get the localized example for a field.
     */
    public function get_example( string $key, ?string $lang = null ): string {
        $field = $this->fields[ $key ] ?? null;
        if ( null === $field ) {
            return '';
        }
        return $this->resolve_localized( $field, 'example', $lang );
    }

    /**
     * Get the default value for a field.
     */
    public function get_default_value( string $key ): string {
        return (string) ( $this->fields[ $key ]['default_value'] ?? '' );
    }

    /**
     * Get the group mode for a grouped field set.
     *
     * @param string $group_key 'collectors' or 'env_local'.
     */
    public function get_group_mode( string $group_key ): string {
        $setting_key = $group_key . '_group_mode';
        $mode        = $this->settings[ $setting_key ] ?? self::MODE_ENABLED;
        if ( ! in_array( $mode, self::valid_modes(), true ) ) {
            $mode = self::MODE_ENABLED;
        }
        return $mode;
    }

    /**
     * Get the list of available local languages.
     */
    public static function get_available_languages(): array {
        return array(
            'ja' => '日本語 (Japanese)',
        );
    }

    /**
     * Get the valid mode values.
     */
    public static function valid_modes(): array {
        return array(
            self::MODE_REQUIRED,
            self::MODE_ENABLED,
            self::MODE_REQUIRED_HIDDEN,
            self::MODE_DISABLED,
        );
    }

    /**
     * Get human-readable labels for each mode (for Settings UI).
     */
    public static function get_mode_labels(): array {
        return array(
            self::MODE_REQUIRED        => __( 'Required (with input)', 'wp-ednasurvey' ),
            self::MODE_ENABLED         => __( 'Enabled (with input)', 'wp-ednasurvey' ),
            self::MODE_REQUIRED_HIDDEN => __( 'Required (no input, default saved)', 'wp-ednasurvey' ),
            self::MODE_DISABLED        => __( 'Disabled (no input)', 'wp-ednasurvey' ),
        );
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    private function field_has_input( array $field ): bool {
        $mode = $field['mode'] ?? self::MODE_DISABLED;
        return self::MODE_REQUIRED === $mode || self::MODE_ENABLED === $mode;
    }

    /**
     * Resolve a localized text attribute (label, description, example).
     *
     * @param array       $field     Field definition.
     * @param string      $attr_base Attribute base name (e.g. 'label').
     * @param string|null $lang      'local', 'en', or null for auto-detect.
     */
    private function resolve_localized( array $field, string $attr_base, ?string $lang ): string {
        if ( null === $lang ) {
            $lang = ( 'ja' === EdnaSurvey_I18n::get_current_language() ) ? 'local' : 'en';
        }

        $primary   = $field[ $attr_base . '_' . $lang ] ?? '';
        $fallback  = ( 'local' === $lang )
            ? ( $field[ $attr_base . '_en' ] ?? '' )
            : ( $field[ $attr_base . '_local' ] ?? '' );

        return '' !== $primary ? $primary : $fallback;
    }
}
