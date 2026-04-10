<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['onlinesubmission'];
$content_callback = function () use ( $username, $settings, $custom_fields, $copy_data, $target_user ) {
    $fields_config = $settings['default_fields_config'] ?? array();
    $photo_limit   = (int) ( $settings['photo_upload_limit'] ?? 10 );
    $lang          = EdnaSurvey_I18n::get_current_language();
    ?>
    <div id="ednasurvey-submission-messages"></div>

    <?php if ( $copy_data ) : ?>
    <div class="ednasurvey-alert ednasurvey-alert-warning">
        <p><?php esc_html_e( 'The original submission will remain as-is. Please ask the administrator via chat to delete the old submission.', 'wp-ednasurvey' ); ?></p>
    </div>
    <?php endif; ?>

    <form id="ednasurvey-online-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'ednasurvey_nonce', 'nonce' ); ?>
        <input type="hidden" name="action" value="ednasurvey_submit_site">

        <?php if ( ! empty( $fields_config['survey_datetime'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Date & Time', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="survey_date"><?php esc_html_e( 'Date', 'wp-ednasurvey' ); ?> <span class="required">*</span></label>
                <input type="date" id="survey_date" name="survey_date"
                       value="<?php echo esc_attr( $copy_data->survey_date ?? wp_date( 'Y-m-d' ) ); ?>" required>
            </div>
            <div class="ednasurvey-field-row">
                <label for="survey_time"><?php esc_html_e( 'Time', 'wp-ednasurvey' ); ?> <span class="required">*</span></label>
                <input type="time" id="survey_time" name="survey_time"
                       value="<?php echo esc_attr( $copy_data->survey_time ?? wp_date( 'H:i' ) ); ?>" required>
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['location'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Location', 'wp-ednasurvey' ); ?></legend>
            <p class="ednasurvey-help"><?php esc_html_e( 'Click/tap on the map to set the survey location.', 'wp-ednasurvey' ); ?></p>
            <div id="ednasurvey-map" style="height: 400px; margin-bottom: 1em;"></div>
            <input type="hidden" id="latitude" name="latitude"
                   value="<?php echo esc_attr( $copy_data->latitude ?? '' ); ?>">
            <input type="hidden" id="longitude" name="longitude"
                   value="<?php echo esc_attr( $copy_data->longitude ?? '' ); ?>">
            <div class="ednasurvey-coords-display">
                <span id="coords-display"><?php esc_html_e( 'No location set', 'wp-ednasurvey' ); ?></span>
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['site_name'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Site Name', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="sitename_local"><?php esc_html_e( 'Japanese', 'wp-ednasurvey' ); ?></label>
                <input type="text" id="sitename_local" name="sitename_local"
                       value="<?php echo esc_attr( $copy_data->sitename_local ?? '' ); ?>">
            </div>
            <div class="ednasurvey-field-row">
                <label for="sitename_en"><?php esc_html_e( 'English', 'wp-ednasurvey' ); ?></label>
                <input type="text" id="sitename_en" name="sitename_en"
                       value="<?php echo esc_attr( $copy_data->sitename_en ?? '' ); ?>">
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['correspondence'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Representative', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="correspondence"><?php esc_html_e( 'Name', 'wp-ednasurvey' ); ?></label>
                <input type="text" id="correspondence" name="correspondence"
                       value="<?php echo esc_attr( $copy_data->correspondence ?? '' ); ?>">
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['collectors'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Collectors', 'wp-ednasurvey' ); ?></legend>
            <?php for ( $i = 1; $i <= 5; $i++ ) :
                $field_name = 'collector' . $i;
            ?>
            <div class="ednasurvey-field-row">
                <label for="<?php echo esc_attr( $field_name ); ?>">
                    <?php
                    /* translators: %d: collector number */
                    printf( esc_html__( 'Collector %d', 'wp-ednasurvey' ), $i );
                    ?>
                </label>
                <input type="text" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                       value="<?php echo esc_attr( $copy_data->$field_name ?? '' ); ?>">
            </div>
            <?php endfor; ?>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['sample_id'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Sample ID', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="sample_id"><?php esc_html_e( 'Sample ID', 'wp-ednasurvey' ); ?> <span class="required">*</span></label>
                <input type="text" id="sample_id" name="sample_id"
                       value="<?php echo esc_attr( $copy_data->sample_id ?? '' ); ?>" required>
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['water_volume'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Filtered Water Volume (mL)', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="watervol1"><?php esc_html_e( 'Replicate 1', 'wp-ednasurvey' ); ?></label>
                <input type="number" id="watervol1" name="watervol1" step="1" min="0"
                       value="<?php echo esc_attr( $copy_data->watervol1 ?? '' ); ?>">
            </div>
            <div class="ednasurvey-field-row">
                <label for="watervol2"><?php esc_html_e( 'Replicate 2', 'wp-ednasurvey' ); ?></label>
                <input type="number" id="watervol2" name="watervol2" step="1" min="0"
                       value="<?php echo esc_attr( $copy_data->watervol2 ?? '' ); ?>">
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['env_broad'] ) ) :
            $env_broad_choices = EdnaSurvey_I18n::get_env_broad_choices();
        ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( 'ja' === $lang ? '環境(大)' : 'Environment (Broad)' ); ?></legend>
            <p class="ednasurvey-help">
                <?php echo esc_html( 'ja' === $lang
                    ? '「河川感潮域」: 河口から外は近くても含まない。「マングローブ」: 河川感潮域のマングローブはマングローブを選択。「大河川下流部」: 遊覧船が運行できるかどうかが基準（急流下り船は含まない）。「塩湖」: 汽水湖や潟湖は含まない。「滅菌水」: ブランク・ネガティブコントロール用。'
                    : '"estuarine": does not include areas outside the river mouth, even if nearby. "mangrove": mangroves in estuarine areas should be classified as mangrove. "large river": whether a sightseeing boat can operate (rapids boats do not count). "saline lake": does not include brackish lakes or lagoons. "sterile water": for blanks / negative controls.' ); ?>
            </p>
            <div class="ednasurvey-field-row">
                <label for="env_broad"><?php echo esc_html( 'ja' === $lang ? '環境(大)' : 'Environment (Broad)' ); ?> <span class="required">*</span></label>
                <select id="env_broad" name="env_broad" required>
                    <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                    <?php foreach ( $env_broad_choices as $key => $labels ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $copy_data->env_broad ?? '', $key ); ?>>
                            <?php echo esc_html( $labels[ $lang ] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>

        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( 'ja' === $lang ? '環境(小)' : 'Environment (Local)' ); ?></legend>
            <p class="ednasurvey-help">
                <?php echo esc_html( 'ja' === $lang
                    ? '環境(大)の選択に応じた項目から1〜7個選択してください。'
                    : 'Select 1 to 7 items from the list filtered by Environment (Broad).' ); ?>
            </p>
            <?php for ( $i = 1; $i <= 7; $i++ ) :
                $field_name = 'env_local' . $i;
            ?>
            <div class="ednasurvey-field-row">
                <label for="<?php echo esc_attr( $field_name ); ?>">
                    <?php echo esc_html( ( 'ja' === $lang ? '環境(小)' : 'Env. (Local) ' ) . $i ); ?>
                    <?php if ( 1 === $i ) : ?><span class="required">*</span><?php endif; ?>
                </label>
                <select id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                        class="ednasurvey-env-local-select" <?php echo 1 === $i ? 'required' : ''; ?>>
                    <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                </select>
            </div>
            <?php endfor; ?>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['weather'] ) ) :
            $weather_choices = EdnaSurvey_I18n::get_weather_choices();
        ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( 'ja' === $lang ? '天候' : 'Weather' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="weather"><?php echo esc_html( 'ja' === $lang ? '天候' : 'Weather' ); ?> <span class="required">*</span></label>
                <select id="weather" name="weather" required>
                    <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                    <?php foreach ( $weather_choices as $key => $labels ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $copy_data->weather ?? '', $key ); ?>>
                            <?php echo esc_html( $labels[ $lang ] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['wind'] ) ) :
            $wind_choices = EdnaSurvey_I18n::get_wind_choices();
        ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( 'ja' === $lang ? '風' : 'Wind' ); ?></legend>
            <p class="ednasurvey-help">
                <?php echo esc_html( 'ja' === $lang
                    ? '「強風」の判定基準: 濾過に使用するシリンジまたはフィルターホルダーが風で動いていくかどうか'
                    : 'Criterion for "windy": whether a syringe or filter holder used for filtration is moved by the wind' ); ?>
            </p>
            <div class="ednasurvey-field-row">
                <label for="wind"><?php echo esc_html( 'ja' === $lang ? '風' : 'Wind' ); ?> <span class="required">*</span></label>
                <select id="wind" name="wind" required>
                    <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                    <?php foreach ( $wind_choices as $key => $labels ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $copy_data->wind ?? '', $key ); ?>>
                            <?php echo esc_html( $labels[ $lang ] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $custom_fields ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Additional Fields', 'wp-ednasurvey' ); ?></legend>
            <?php
            // Build copy_data custom values map
            $copy_custom_values = array();
            if ( $copy_data && ! empty( $copy_data->custom_fields ) ) {
                foreach ( $copy_data->custom_fields as $cf ) {
                    $copy_custom_values[ (int) $cf->field_id ] = $cf->field_value;
                }
            }
            foreach ( $custom_fields as $cf ) :
                $field_name = 'custom_' . $cf->id;
                $label      = $lang === 'ja' ? $cf->label_ja : $cf->label_en;
                $value      = $copy_custom_values[ (int) $cf->id ] ?? '';
                $options    = $cf->field_options ? json_decode( $cf->field_options, true ) : array();
            ?>
            <div class="ednasurvey-field-row">
                <label for="<?php echo esc_attr( $field_name ); ?>">
                    <?php echo esc_html( $label ); ?>
                    <?php if ( $cf->is_required ) : ?><span class="required">*</span><?php endif; ?>
                </label>
                <?php if ( 'select' === $cf->field_type && ! empty( $options['choices'] ) ) : ?>
                    <select id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                            <?php echo $cf->is_required ? 'required' : ''; ?>>
                        <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                        <?php foreach ( $options['choices'] as $choice ) : ?>
                            <option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $value, $choice ); ?>>
                                <?php echo esc_html( $choice ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ( 'textarea' === $cf->field_type ) : ?>
                    <textarea id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                              rows="3" <?php echo $cf->is_required ? 'required' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>
                <?php elseif ( 'number' === $cf->field_type ) : ?>
                    <input type="number" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                           step="any" value="<?php echo esc_attr( $value ); ?>"
                           <?php echo $cf->is_required ? 'required' : ''; ?>>
                <?php elseif ( 'date' === $cf->field_type ) : ?>
                    <input type="date" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                           value="<?php echo esc_attr( $value ); ?>"
                           <?php echo $cf->is_required ? 'required' : ''; ?>>
                <?php else : ?>
                    <input type="text" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                           value="<?php echo esc_attr( $value ); ?>"
                           <?php echo $cf->is_required ? 'required' : ''; ?>>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['notes'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Notes', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <textarea id="notes" name="notes" rows="4"><?php echo esc_textarea( $copy_data->notes ?? '' ); ?></textarea>
            </div>
        </fieldset>
        <?php endif; ?>

        <?php if ( ! empty( $fields_config['photos'] ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Photos', 'wp-ednasurvey' ); ?></legend>
            <p class="ednasurvey-help">
                <?php
                /* translators: %d: maximum number of photos */
                printf( esc_html__( 'Upload up to %d photos (JPEG or HEIC/HEIF).', 'wp-ednasurvey' ), $photo_limit );
                ?>
            </p>
            <div class="ednasurvey-field-row">
                <input type="file" id="photos" name="photos[]" multiple
                       accept=".jpg,.jpeg,.heic,.heif">
            </div>
            <div id="ednasurvey-photo-preview" class="ednasurvey-photo-preview"></div>
        </fieldset>
        <?php endif; ?>

        <div class="ednasurvey-form-actions">
            <button type="submit" class="button button-primary ednasurvey-submit-btn">
                <?php esc_html_e( 'Submit', 'wp-ednasurvey' ); ?>
            </button>
            <a href="<?php echo esc_url( home_url( '/' . $username . '/' ) ); ?>" class="button">
                <?php esc_html_e( 'Return to Dashboard without submitting', 'wp-ednasurvey' ); ?>
            </a>
        </div>
    </form>

    <script>
        var ednasurveyFormConfig = {
            hasLocation: <?php echo ! empty( $fields_config['location'] ) ? 'true' : 'false'; ?>,
            copyLat: <?php echo esc_js( $copy_data->latitude ?? 'null' ); ?>,
            copyLng: <?php echo esc_js( $copy_data->longitude ?? 'null' ); ?>,
            photoLimit: <?php echo (int) $photo_limit; ?>
        };
        <?php if ( ! empty( $fields_config['env_broad'] ) ) :
            // Build env_local mapping: env_broad key => [{key, label}, ...]
            $env_local_choices = EdnaSurvey_I18n::get_env_local_choices();
            $env_local_map    = EdnaSurvey_I18n::get_env_local_for_broad();
            $js_mapping = array();
            foreach ( $env_local_map as $broad_key => $local_keys ) {
                $items = array();
                foreach ( $local_keys as $lk ) {
                    if ( isset( $env_local_choices[ $lk ] ) ) {
                        $items[] = array( 'key' => $lk, 'label' => $env_local_choices[ $lk ][ $lang ] );
                    }
                }
                $js_mapping[ $broad_key ] = $items;
            }
            // Pre-selected values for copy_from
            $copy_env_locals = array();
            for ( $ci = 1; $ci <= 7; $ci++ ) {
                $f = 'env_local' . $ci;
                $copy_env_locals[] = $copy_data->$f ?? '';
            }
        ?>
        (function(){
            var mapping = <?php echo wp_json_encode( $js_mapping ); ?>;
            var copyValues = <?php echo wp_json_encode( $copy_env_locals ); ?>;
            var selectLabel = <?php echo wp_json_encode( __( '-- Select --', 'wp-ednasurvey' ) ); ?>;
            var broadSel = document.getElementById('env_broad');

            function updateEnvLocal() {
                var choices = mapping[broadSel.value] || [];
                for (var i = 1; i <= 7; i++) {
                    var sel = document.getElementById('env_local' + i);
                    var prev = sel.value;
                    sel.innerHTML = '';
                    var blank = document.createElement('option');
                    blank.value = '';
                    blank.textContent = selectLabel;
                    sel.appendChild(blank);
                    for (var j = 0; j < choices.length; j++) {
                        var opt = document.createElement('option');
                        opt.value = choices[j].key;
                        opt.textContent = choices[j].label;
                        if (choices[j].key === prev) opt.selected = true;
                        sel.appendChild(opt);
                    }
                }
            }

            broadSel.addEventListener('change', updateEnvLocal);

            // Initialize on load (for copy_from or default)
            if (broadSel.value) {
                updateEnvLocal();
                for (var i = 0; i < 7; i++) {
                    if (copyValues[i]) {
                        document.getElementById('env_local' + (i + 1)).value = copyValues[i];
                    }
                }
            }
        })();
        <?php endif; ?>
    </script>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
