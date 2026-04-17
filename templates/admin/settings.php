<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$field_defs   = EdnaSurvey_Field_Registry::get_standard_field_definitions();
$field_config = $settings['field_config'] ?? array();
$local_lang   = $settings['local_language'] ?? 'ja';
$lang_options = EdnaSurvey_Field_Registry::get_available_languages();
$lang_label   = strtoupper( $local_lang );
$mode_labels  = EdnaSurvey_Field_Registry::get_mode_labels();

// Group fields
$group_a           = array_filter( $field_defs, fn( $f ) => $f['group'] === EdnaSurvey_Field_Registry::GROUP_A );
$group_b           = array_filter( $field_defs, fn( $f ) => $f['group'] === EdnaSurvey_Field_Registry::GROUP_B );
$group_c_individual = array_filter( $field_defs, fn( $f ) => $f['group'] === EdnaSurvey_Field_Registry::GROUP_C && empty( $f['group_key'] ) );
$group_c_collectors = array_filter( $field_defs, fn( $f ) => ( $f['group_key'] ?? '' ) === 'collectors' );
$group_c_env_local  = array_filter( $field_defs, fn( $f ) => ( $f['group_key'] ?? '' ) === 'env_local' );

/**
 * Render the label/description/example detail block for a field.
 */
function ednasurvey_render_field_detail( string $key, array $def, array $field_config, string $lang_label ): void {
    $saved = $field_config[ $key ] ?? array();
    $vals  = array(
        'label_local'       => $saved['label_local'] ?? $def['label_local'],
        'label_en'          => $saved['label_en'] ?? $def['label_en'],
        'description_local' => $saved['description_local'] ?? $def['description_local'],
        'description_en'    => $saved['description_en'] ?? $def['description_en'],
        'example_local'     => $saved['example_local'] ?? $def['example_local'],
        'example_en'        => $saved['example_en'] ?? $def['example_en'],
    );
    ?>
    <div class="ednasurvey-detail-grid">
        <label>Label (<?php echo esc_html( $lang_label ); ?>)
            <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][label_local]"
                   value="<?php echo esc_attr( $vals['label_local'] ); ?>" class="regular-text">
        </label>
        <label>Label (EN)
            <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][label_en]"
                   value="<?php echo esc_attr( $vals['label_en'] ); ?>" class="regular-text">
        </label>
        <label>Description (<?php echo esc_html( $lang_label ); ?>)
            <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][description_local]"
                   value="<?php echo esc_attr( $vals['description_local'] ); ?>" class="regular-text">
        </label>
        <label>Description (EN)
            <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][description_en]"
                   value="<?php echo esc_attr( $vals['description_en'] ); ?>" class="regular-text">
        </label>
        <label>Example (<?php echo esc_html( $lang_label ); ?>)
            <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][example_local]"
                   value="<?php echo esc_attr( $vals['example_local'] ); ?>" class="regular-text">
        </label>
        <label>Example (EN)
            <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][example_en]"
                   value="<?php echo esc_attr( $vals['example_en'] ); ?>" class="regular-text">
        </label>
    </div>
    <?php
}
?>
<style>
.ednasurvey-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 16px;
    margin-top: 6px;
    padding: 8px;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
}
.ednasurvey-detail-grid label {
    display: block;
    font-size: 12px;
    color: #666;
}
.ednasurvey-detail-grid input.regular-text {
    width: 100%;
    margin-top: 2px;
}
.ednasurvey-field-table {
    max-width: 1100px;
}
.ednasurvey-field-table td,
.ednasurvey-field-table th {
    vertical-align: top;
    padding: 8px;
}
.ednasurvey-field-table .field-name {
    font-weight: 600;
    white-space: nowrap;
}
.ednasurvey-field-table .field-name code {
    font-weight: normal;
    font-size: 11px;
    color: #888;
    display: block;
}
.ednasurvey-field-table details {
    margin-top: 4px;
}
.ednasurvey-field-table details summary {
    cursor: pointer;
    color: #2271b1;
    font-size: 12px;
}
.ednasurvey-group-mode {
    margin-bottom: 12px;
    padding: 8px 12px;
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
}
.ednasurvey-cf-table th,
.ednasurvey-cf-table td {
    padding: 6px 8px;
    vertical-align: top;
}
.ednasurvey-cf-table input[type="text"],
.ednasurvey-cf-table select {
    width: 100%;
}
</style>

<div class="wrap">
    <h1><?php esc_html_e( 'eDNA Survey Settings', 'wp-ednasurvey' ); ?></h1>

    <?php settings_errors( 'ednasurvey_settings' ); ?>

    <!-- Server Environment -->
    <h2><?php esc_html_e( 'Server Environment', 'wp-ednasurvey' ); ?></h2>
    <table class="widefat striped" style="max-width: 900px;">
        <thead>
            <tr>
                <th style="width: 40px;"><?php esc_html_e( 'Status', 'wp-ednasurvey' ); ?></th>
                <th style="width: 160px;"><?php esc_html_e( 'Component', 'wp-ednasurvey' ); ?></th>
                <th style="width: 160px;"><?php esc_html_e( 'Version', 'wp-ednasurvey' ); ?></th>
                <th><?php esc_html_e( 'Details', 'wp-ednasurvey' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $env_checks as $check ) :
                $icon  = 'ok' === $check['status'] ? '&#x2705;' : ( 'warning' === $check['status'] ? '&#x26A0;&#xFE0F;' : '&#x274C;' );
                $color = 'ok' === $check['status'] ? '#00a32a' : ( 'warning' === $check['status'] ? '#dba617' : '#d63638' );
            ?>
            <tr>
                <td style="text-align: center; font-size: 1.2em;"><?php echo $icon; ?></td>
                <td><strong><?php echo esc_html( $check['name'] ); ?></strong></td>
                <td><code><?php echo esc_html( $check['version'] ); ?></code></td>
                <td style="color: <?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $check['message'] ); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="description" style="max-width: 900px;">
        <?php esc_html_e( 'Items marked with a red X are required for core functionality. Items marked with a warning may limit certain features but the plugin will still operate.', 'wp-ednasurvey' ); ?>
    </p>
    <hr style="margin: 1.5em 0;">

    <form method="post">
        <?php wp_nonce_field( 'ednasurvey_save_settings', 'ednasurvey_settings_nonce' ); ?>

        <!-- Language Settings -->
        <h2><?php esc_html_e( 'Language Settings', 'wp-ednasurvey' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="local_language"><?php esc_html_e( 'Local Language (XX)', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <select id="local_language" name="local_language">
                        <?php foreach ( $lang_options as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $local_lang, $code ); ?>><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'The local language used for Label (XX), Description (XX), and Example (XX) fields below.', 'wp-ednasurvey' ); ?></p>
                </td>
            </tr>
        </table>

        <!-- ============================================================ -->
        <!-- Field Configuration -->
        <!-- ============================================================ -->
        <h2><?php esc_html_e( 'Field Configuration', 'wp-ednasurvey' ); ?></h2>

        <!-- Group A: Always Required -->
        <h3><?php esc_html_e( 'Always Required Fields', 'wp-ednasurvey' ); ?></h3>
        <p class="description"><?php esc_html_e( 'These fields are always required and visible. You can customize their labels, descriptions, and examples.', 'wp-ednasurvey' ); ?></p>
        <table class="widefat striped ednasurvey-field-table">
            <thead>
                <tr>
                    <th style="width: 180px;"><?php esc_html_e( 'Field', 'wp-ednasurvey' ); ?></th>
                    <th style="width: 140px;"><?php esc_html_e( 'Mode', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Labels & Descriptions', 'wp-ednasurvey' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $group_a as $key => $def ) : ?>
                <tr>
                    <td class="field-name"><?php echo esc_html( $def['label_en'] ); ?><code><?php echo esc_html( $key ); ?></code></td>
                    <td><em><?php esc_html_e( 'Required (fixed)', 'wp-ednasurvey' ); ?></em></td>
                    <td>
                        <details>
                            <summary><?php esc_html_e( 'Edit labels', 'wp-ednasurvey' ); ?></summary>
                            <?php ednasurvey_render_field_detail( $key, $def, $field_config, $lang_label ); ?>
                        </details>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Group B: Required with Default Values -->
        <h3><?php esc_html_e( 'Required Fields with Default Values', 'wp-ednasurvey' ); ?></h3>
        <p class="description"><?php esc_html_e( 'These fields are always required. You can set default values that pre-fill the form.', 'wp-ednasurvey' ); ?></p>
        <table class="widefat striped ednasurvey-field-table">
            <thead>
                <tr>
                    <th style="width: 180px;"><?php esc_html_e( 'Field', 'wp-ednasurvey' ); ?></th>
                    <th style="width: 140px;"><?php esc_html_e( 'Mode', 'wp-ednasurvey' ); ?></th>
                    <th style="width: 200px;"><?php esc_html_e( 'Default Value', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Labels & Descriptions', 'wp-ednasurvey' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $group_b as $key => $def ) :
                    $saved_val = $field_config[ $key ]['default_value'] ?? $def['default_value'] ?? '';
                ?>
                <tr>
                    <td class="field-name"><?php echo esc_html( $def['label_en'] ); ?><code><?php echo esc_html( $key ); ?></code></td>
                    <td><em><?php esc_html_e( 'Required (fixed)', 'wp-ednasurvey' ); ?></em></td>
                    <td>
                        <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][default_value]"
                               value="<?php echo esc_attr( $saved_val ); ?>" class="regular-text">
                    </td>
                    <td>
                        <details>
                            <summary><?php esc_html_e( 'Edit labels', 'wp-ednasurvey' ); ?></summary>
                            <?php ednasurvey_render_field_detail( $key, $def, $field_config, $lang_label ); ?>
                        </details>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Group C: Configurable Fields -->
        <h3><?php esc_html_e( 'Configurable Fields', 'wp-ednasurvey' ); ?></h3>
        <p class="description"><?php esc_html_e( 'Choose the mode for each field: Required (with input), Enabled (optional, with input), Required (no input, default saved), or Disabled.', 'wp-ednasurvey' ); ?></p>

        <!-- Collectors group -->
        <h4><?php esc_html_e( 'Collectors (collector2-5)', 'wp-ednasurvey' ); ?></h4>
        <div class="ednasurvey-group-mode">
            <label><strong><?php esc_html_e( 'Group Mode:', 'wp-ednasurvey' ); ?></strong>
                <select name="collectors_group_mode">
                    <?php foreach ( $mode_labels as $mode_val => $mode_lbl ) : ?>
                    <option value="<?php echo esc_attr( $mode_val ); ?>"
                        <?php selected( $settings['collectors_group_mode'] ?? EdnaSurvey_Field_Registry::MODE_ENABLED, $mode_val ); ?>>
                        <?php echo esc_html( $mode_lbl ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <table class="widefat striped ednasurvey-field-table">
            <thead>
                <tr>
                    <th style="width: 180px;"><?php esc_html_e( 'Field', 'wp-ednasurvey' ); ?></th>
                    <th style="width: 200px;"><?php esc_html_e( 'Default Value', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Labels & Descriptions', 'wp-ednasurvey' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $group_c_collectors as $key => $def ) :
                    $saved_val = $field_config[ $key ]['default_value'] ?? '';
                ?>
                <tr>
                    <td class="field-name"><?php echo esc_html( $def['label_en'] ); ?><code><?php echo esc_html( $key ); ?></code></td>
                    <td>
                        <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][default_value]"
                               value="<?php echo esc_attr( $saved_val ); ?>" class="regular-text">
                    </td>
                    <td>
                        <details>
                            <summary><?php esc_html_e( 'Edit labels', 'wp-ednasurvey' ); ?></summary>
                            <?php ednasurvey_render_field_detail( $key, $def, $field_config, $lang_label ); ?>
                        </details>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Environment (Local) group -->
        <h4><?php esc_html_e( 'Environment Local (env_local2-7)', 'wp-ednasurvey' ); ?></h4>
        <div class="ednasurvey-group-mode">
            <label><strong><?php esc_html_e( 'Group Mode:', 'wp-ednasurvey' ); ?></strong>
                <select name="env_local_group_mode">
                    <?php foreach ( $mode_labels as $mode_val => $mode_lbl ) : ?>
                    <option value="<?php echo esc_attr( $mode_val ); ?>"
                        <?php selected( $settings['env_local_group_mode'] ?? EdnaSurvey_Field_Registry::MODE_ENABLED, $mode_val ); ?>>
                        <?php echo esc_html( $mode_lbl ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <table class="widefat striped ednasurvey-field-table">
            <thead>
                <tr>
                    <th style="width: 180px;"><?php esc_html_e( 'Field', 'wp-ednasurvey' ); ?></th>
                    <th style="width: 200px;"><?php esc_html_e( 'Default Value', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Labels & Descriptions', 'wp-ednasurvey' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $group_c_env_local as $key => $def ) :
                    $saved_val = $field_config[ $key ]['default_value'] ?? '';
                ?>
                <tr>
                    <td class="field-name"><?php echo esc_html( $def['label_en'] ); ?><code><?php echo esc_html( $key ); ?></code></td>
                    <td>
                        <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][default_value]"
                               value="<?php echo esc_attr( $saved_val ); ?>" class="regular-text">
                    </td>
                    <td>
                        <details>
                            <summary><?php esc_html_e( 'Edit labels', 'wp-ednasurvey' ); ?></summary>
                            <?php ednasurvey_render_field_detail( $key, $def, $field_config, $lang_label ); ?>
                        </details>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Individual configurable fields -->
        <h4><?php esc_html_e( 'Other Configurable Fields', 'wp-ednasurvey' ); ?></h4>
        <table class="widefat striped ednasurvey-field-table">
            <thead>
                <tr>
                    <th style="width: 180px;"><?php esc_html_e( 'Field', 'wp-ednasurvey' ); ?></th>
                    <th style="width: 200px;"><?php esc_html_e( 'Mode', 'wp-ednasurvey' ); ?></th>
                    <th style="width: 200px;"><?php esc_html_e( 'Default Value', 'wp-ednasurvey' ); ?></th>
                    <th><?php esc_html_e( 'Labels & Descriptions', 'wp-ednasurvey' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $group_c_individual as $key => $def ) :
                    $saved_mode = $field_config[ $key ]['mode'] ?? $def['mode'];
                    $saved_val  = $field_config[ $key ]['default_value'] ?? $def['default_value'] ?? '';
                ?>
                <tr>
                    <td class="field-name"><?php echo esc_html( $def['label_en'] ); ?><code><?php echo esc_html( $key ); ?></code></td>
                    <td>
                        <select name="field_config[<?php echo esc_attr( $key ); ?>][mode]">
                            <?php foreach ( $mode_labels as $mode_val => $mode_lbl ) : ?>
                            <option value="<?php echo esc_attr( $mode_val ); ?>" <?php selected( $saved_mode, $mode_val ); ?>>
                                <?php echo esc_html( $mode_lbl ); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="field_config[<?php echo esc_attr( $key ); ?>][default_value]"
                               value="<?php echo esc_attr( $saved_val ); ?>" class="regular-text">
                    </td>
                    <td>
                        <details>
                            <summary><?php esc_html_e( 'Edit labels', 'wp-ednasurvey' ); ?></summary>
                            <?php ednasurvey_render_field_detail( $key, $def, $field_config, $lang_label ); ?>
                        </details>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ============================================================ -->
        <!-- Custom Fields -->
        <!-- ============================================================ -->
        <h2><?php esc_html_e( 'Custom Data Fields', 'wp-ednasurvey' ); ?></h2>
        <div id="ednasurvey-custom-fields-builder">
            <table class="wp-list-table widefat ednasurvey-cf-table" id="custom-fields-table">
                <thead>
                    <tr>
                        <th style="width:100px;"><?php esc_html_e( 'Key', 'wp-ednasurvey' ); ?></th>
                        <th><?php echo esc_html( sprintf( __( 'Label (%s)', 'wp-ednasurvey' ), $lang_label ) ); ?></th>
                        <th><?php esc_html_e( 'Label (EN)', 'wp-ednasurvey' ); ?></th>
                        <th style="width:90px;"><?php esc_html_e( 'Type', 'wp-ednasurvey' ); ?></th>
                        <th style="width:180px;"><?php esc_html_e( 'Mode', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Default', 'wp-ednasurvey' ); ?></th>
                        <th><?php esc_html_e( 'Options', 'wp-ednasurvey' ); ?></th>
                        <th style="width:60px;"><?php esc_html_e( 'Actions', 'wp-ednasurvey' ); ?></th>
                    </tr>
                </thead>
                <tbody id="custom-fields-body">
                    <?php foreach ( $custom_fields as $cf ) : ?>
                    <tr class="custom-field-row" data-field-id="<?php echo (int) $cf->id; ?>">
                        <td><input type="text" class="cf-key" value="<?php echo esc_attr( $cf->field_key ); ?>"></td>
                        <td><input type="text" class="cf-label-local" value="<?php echo esc_attr( $cf->label_local ?? $cf->label_ja ?? '' ); ?>"></td>
                        <td><input type="text" class="cf-label-en" value="<?php echo esc_attr( $cf->label_en ); ?>"></td>
                        <td>
                            <select class="cf-type">
                                <option value="text" <?php selected( $cf->field_type, 'text' ); ?>>Text</option>
                                <option value="number" <?php selected( $cf->field_type, 'number' ); ?>>Number</option>
                                <option value="select" <?php selected( $cf->field_type, 'select' ); ?>>Select</option>
                                <option value="date" <?php selected( $cf->field_type, 'date' ); ?>>Date</option>
                                <option value="textarea" <?php selected( $cf->field_type, 'textarea' ); ?>>Textarea</option>
                            </select>
                        </td>
                        <td>
                            <select class="cf-mode">
                                <?php foreach ( $mode_labels as $mv => $ml ) : ?>
                                <option value="<?php echo esc_attr( $mv ); ?>" <?php selected( $cf->field_mode ?? 'enabled', $mv ); ?>><?php echo esc_html( $ml ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" class="cf-default" value="<?php echo esc_attr( $cf->default_value ?? '' ); ?>"></td>
                        <td><input type="text" class="cf-options" value="<?php echo esc_attr( $cf->field_options ?? '' ); ?>"></td>
                        <td><button type="button" class="button button-small cf-remove"><?php esc_html_e( 'Remove', 'wp-ednasurvey' ); ?></button></td>
                    </tr>
                    <!-- Description / Example row -->
                    <tr class="custom-field-detail-row" data-field-id="<?php echo (int) $cf->id; ?>">
                        <td></td>
                        <td colspan="7">
                            <div class="ednasurvey-detail-grid" style="margin-top:0;">
                                <label><?php echo esc_html( sprintf( __( 'Description (%s)', 'wp-ednasurvey' ), $lang_label ) ); ?>
                                    <input type="text" class="cf-desc-local" value="<?php echo esc_attr( $cf->description_local ?? '' ); ?>">
                                </label>
                                <label>Description (EN)
                                    <input type="text" class="cf-desc-en" value="<?php echo esc_attr( $cf->description_en ?? '' ); ?>">
                                </label>
                                <label><?php echo esc_html( sprintf( __( 'Example (%s)', 'wp-ednasurvey' ), $lang_label ) ); ?>
                                    <input type="text" class="cf-example-local" value="<?php echo esc_attr( $cf->example_local ?? '' ); ?>">
                                </label>
                                <label>Example (EN)
                                    <input type="text" class="cf-example-en" value="<?php echo esc_attr( $cf->example_en ?? '' ); ?>">
                                </label>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" id="add-custom-field" class="button">
                    <?php esc_html_e( 'Add Custom Field', 'wp-ednasurvey' ); ?>
                </button>
                <button type="button" id="save-custom-fields" class="button button-primary">
                    <?php esc_html_e( 'Save Custom Fields', 'wp-ednasurvey' ); ?>
                </button>
            </p>
        </div>

        <!-- ============================================================ -->
        <!-- Map Settings -->
        <!-- ============================================================ -->
        <h2><?php esc_html_e( 'Map Settings', 'wp-ednasurvey' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="tile_server_url"><?php esc_html_e( 'Tile Server URL', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="text" id="tile_server_url" name="tile_server_url" class="large-text"
                           value="<?php echo esc_attr( $settings['tile_server_url'] ?? '' ); ?>">
                    <p class="description"><?php esc_html_e( 'e.g. https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 'wp-ednasurvey' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="tile_attribution"><?php esc_html_e( 'Tile Attribution', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="text" id="tile_attribution" name="tile_attribution" class="large-text"
                           value="<?php echo esc_attr( $settings['tile_attribution'] ?? '' ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="map_center_lat"><?php esc_html_e( 'Default Center Latitude', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="map_center_lat" name="map_center_lat" step="0.000001"
                           value="<?php echo esc_attr( $settings['map_center_lat'] ?? 35.6762 ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="map_center_lng"><?php esc_html_e( 'Default Center Longitude', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="map_center_lng" name="map_center_lng" step="0.000001"
                           value="<?php echo esc_attr( $settings['map_center_lng'] ?? 139.6503 ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="map_default_zoom"><?php esc_html_e( 'Default Zoom Level', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="map_default_zoom" name="map_default_zoom" min="1" max="18"
                           value="<?php echo esc_attr( $settings['map_default_zoom'] ?? 5 ); ?>">
                </td>
            </tr>
        </table>

        <!-- Photo Settings -->
        <h2><?php esc_html_e( 'Photo Settings', 'wp-ednasurvey' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="photo_upload_limit"><?php esc_html_e( 'Photo Upload Limit (per site)', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="photo_upload_limit" name="photo_upload_limit" min="1" max="50"
                           value="<?php echo esc_attr( $settings['photo_upload_limit'] ?? 10 ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="photo_time_threshold"><?php esc_html_e( 'Photo Time Matching Threshold (minutes)', 'wp-ednasurvey' ); ?></label></th>
                <td>
                    <input type="number" id="photo_time_threshold" name="photo_time_threshold" min="1" max="1440"
                           value="<?php echo esc_attr( $settings['photo_time_threshold'] ?? 30 ); ?>">
                    <p class="description"><?php esc_html_e( 'When photo_files is omitted in Excel, photos are matched to samples by comparing EXIF DateTimeOriginal with survey_date + survey_time within this threshold.', 'wp-ednasurvey' ); ?></p>
                </td>
            </tr>
        </table>

        <!-- External Command Paths -->
        <h2><?php esc_html_e( 'External Command Paths', 'wp-ednasurvey' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Specify full paths to external commands. Leave blank to auto-detect from PATH.', 'wp-ednasurvey' ); ?>
        </p>
        <table class="form-table">
            <?php
            $cmd_fields = array(
                'cmd_imagemagick'  => array(
                    'label' => __( 'ImageMagick (magick / convert)', 'wp-ednasurvey' ),
                    'desc'  => __( 'HEIC to JPEG conversion. Auto-detects "magick" then "convert".', 'wp-ednasurvey' ),
                    'placeholder' => '/usr/bin/magick',
                ),
                'cmd_heif_convert' => array(
                    'label' => __( 'heif-dec / heif-convert (libheif)', 'wp-ednasurvey' ),
                    'desc'  => __( 'HEIC to JPEG conversion. Auto-detects "heif-dec" then "heif-convert". Install: apt install libheif-examples', 'wp-ednasurvey' ),
                    'placeholder' => '/usr/bin/heif-dec',
                ),
                'cmd_ffmpeg' => array(
                    'label' => __( 'FFmpeg', 'wp-ednasurvey' ),
                    'desc'  => __( 'HEIC to JPEG conversion. Install: apt install ffmpeg', 'wp-ednasurvey' ),
                    'placeholder' => '/usr/bin/ffmpeg',
                ),
                'cmd_exiftool' => array(
                    'label' => __( 'exiftool', 'wp-ednasurvey' ),
                    'desc'  => __( 'GPS extraction fallback when PHP exif extension is unavailable. Install: apt install libimage-exiftool-perl', 'wp-ednasurvey' ),
                    'placeholder' => '/usr/bin/exiftool',
                ),
            );
            foreach ( $cmd_fields as $key => $field ) :
                $resolved = EdnaSurvey_Admin_Settings::resolve_command( $key );
            ?>
            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
                <td>
                    <input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
                           class="large-text" value="<?php echo esc_attr( $settings[ $key ] ?? '' ); ?>"
                           placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>">
                    <p class="description">
                        <?php echo esc_html( $field['desc'] ); ?>
                        <?php if ( $resolved ) : ?>
                            <br><span style="color: #00a32a;">&#x2705; <?php
                                /* translators: %s: resolved path */
                                printf( esc_html__( 'Resolved: %s', 'wp-ednasurvey' ), esc_html( $resolved ) );
                            ?></span>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <?php submit_button( __( 'Save Settings', 'wp-ednasurvey' ) ); ?>
    </form>
</div>
