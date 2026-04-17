<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['onlinesubmission'];
$content_callback = function () use ( $username, $settings, $registry, $custom_fields, $copy_data, $target_user ) {
    $photo_limit = (int) ( $settings['photo_upload_limit'] ?? 10 );

    /**
     * Get value for a field: copy_data if available, then registry default, then fallback.
     */
    $fval = function ( string $key, string $fallback = '' ) use ( $copy_data, $registry ) {
        if ( $copy_data && isset( $copy_data->$key ) ) {
            return $copy_data->$key;
        }
        $default = $registry->get_default_value( $key );
        return '' !== $default ? $default : $fallback;
    };

    $req = function ( string $key ) use ( $registry ) {
        return $registry->is_required( $key ) ? ' <span class="required">*</span>' : '';
    };

    $desc = function ( string $key ) use ( $registry ) {
        $d = $registry->get_description( $key );
        if ( '' !== $d ) {
            echo '<p class="ednasurvey-help">' . esc_html( $d ) . '</p>';
        }
    };
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
        <input type="hidden" name="session_id" id="ednasurvey-session-id" value="">

        <!-- Date & Time (Group A: always required) -->
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Date & Time', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="survey_date"><?php echo esc_html( $registry->get_label( 'survey_date' ) ); ?><?php echo $req( 'survey_date' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <?php $desc( 'survey_date' ); ?>
                <input type="date" id="survey_date" name="survey_date"
                       value="<?php echo esc_attr( $fval( 'survey_date', wp_date( 'Y-m-d' ) ) ); ?>" required>
            </div>
            <div class="ednasurvey-field-row">
                <label for="survey_time"><?php echo esc_html( $registry->get_label( 'survey_time' ) ); ?><?php echo $req( 'survey_time' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <?php $desc( 'survey_time' ); ?>
                <input type="time" id="survey_time" name="survey_time"
                       value="<?php echo esc_attr( $fval( 'survey_time', wp_date( 'H:i' ) ) ); ?>" required>
            </div>
        </fieldset>

        <!-- Location (Group A: always required) -->
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

        <!-- Site Name (Group A: always required) -->
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Site Name', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="sitename_local"><?php echo esc_html( $registry->get_label( 'sitename_local' ) ); ?><?php echo $req( 'sitename_local' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <?php $desc( 'sitename_local' ); ?>
                <input type="text" id="sitename_local" name="sitename_local"
                       value="<?php echo esc_attr( $fval( 'sitename_local' ) ); ?>" required>
            </div>
            <div class="ednasurvey-field-row">
                <label for="sitename_en"><?php echo esc_html( $registry->get_label( 'sitename_en' ) ); ?><?php echo $req( 'sitename_en' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <?php $desc( 'sitename_en' ); ?>
                <input type="text" id="sitename_en" name="sitename_en"
                       value="<?php echo esc_attr( $fval( 'sitename_en' ) ); ?>" required>
            </div>
        </fieldset>

        <!-- Sample ID (Group B) -->
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'sample_id' ) ); ?></legend>
            <?php $desc( 'sample_id' ); ?>
            <div class="ednasurvey-field-row">
                <label for="sample_id"><?php echo esc_html( $registry->get_label( 'sample_id' ) ); ?> <span class="required">*</span></label>
                <input type="text" id="sample_id" name="sample_id"
                       value="<?php echo esc_attr( $fval( 'sample_id' ) ); ?>" required>
            </div>
        </fieldset>

        <!-- Representative (Group B) -->
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'correspondence' ) ); ?></legend>
            <?php $desc( 'correspondence' ); ?>
            <div class="ednasurvey-field-row">
                <label for="correspondence"><?php echo esc_html( $registry->get_label( 'correspondence' ) ); ?> <span class="required">*</span></label>
                <input type="text" id="correspondence" name="correspondence"
                       value="<?php echo esc_attr( $fval( 'correspondence' ) ); ?>" required>
            </div>
        </fieldset>

        <!-- Collector 1 (Group B) + Collector 2-5 (Group C grouped) -->
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Collectors', 'wp-ednasurvey' ); ?></legend>
            <div class="ednasurvey-field-row">
                <label for="collector1"><?php echo esc_html( $registry->get_label( 'collector1' ) ); ?> <span class="required">*</span></label>
                <?php $desc( 'collector1' ); ?>
                <input type="text" id="collector1" name="collector1"
                       value="<?php echo esc_attr( $fval( 'collector1' ) ); ?>" required>
            </div>
            <?php if ( $registry->has_input( 'collector2' ) ) :
                for ( $i = 2; $i <= 5; $i++ ) :
                    $ck = 'collector' . $i;
            ?>
            <div class="ednasurvey-field-row">
                <label for="<?php echo esc_attr( $ck ); ?>">
                    <?php echo esc_html( $registry->get_label( $ck ) ); ?><?php echo $req( $ck ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </label>
                <input type="text" id="<?php echo esc_attr( $ck ); ?>" name="<?php echo esc_attr( $ck ); ?>"
                       value="<?php echo esc_attr( $fval( $ck ) ); ?>">
            </div>
            <?php endfor; endif; ?>
        </fieldset>

        <!-- Environment Broad (Group B) -->
        <?php
        $env_broad_choices = EdnaSurvey_I18n::get_env_broad_choices();
        ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'env_broad' ) ); ?></legend>
            <p class="ednasurvey-help">
                <?php esc_html_e( '"estuarine": does not include areas outside the river mouth, even if nearby. Does not include brackish lakes. "mangrove": mangroves in estuarine areas should be classified as mangrove. "large river": whether a sightseeing boat can operate (rapids boats do not count). "saline lake": does not include brackish lakes or lagoons. "sterile water": for blanks / negative controls.', 'wp-ednasurvey' ); ?>
            </p>
            <div class="ednasurvey-field-row">
                <label for="env_broad"><?php echo esc_html( $registry->get_label( 'env_broad' ) ); ?> <span class="required">*</span></label>
                <select id="env_broad" name="env_broad" required>
                    <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                    <?php foreach ( $env_broad_choices as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $fval( 'env_broad' ), $key ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>

        <!-- Environment Local (env_local1 = Group B, env_local2-7 = Group C grouped) -->
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'env_local1' ) ); ?></legend>
            <p class="ednasurvey-help">
                <?php esc_html_e( 'Select 1 to 7 items from the list filtered by Environment (Broad).', 'wp-ednasurvey' ); ?>
            </p>
            <?php
            $env_local_max = $registry->has_input( 'env_local2' ) ? 7 : 1;
            for ( $i = 1; $i <= $env_local_max; $i++ ) :
                $fn = 'env_local' . $i;
            ?>
            <div class="ednasurvey-field-row">
                <label for="<?php echo esc_attr( $fn ); ?>">
                    <?php echo esc_html( $registry->get_label( $fn ) ); ?>
                    <?php if ( 1 === $i ) : ?><span class="required">*</span><?php else : echo $req( $fn ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php endif; ?>
                </label>
                <select id="<?php echo esc_attr( $fn ); ?>" name="<?php echo esc_attr( $fn ); ?>"
                        class="ednasurvey-env-local-select" <?php echo 1 === $i ? 'required' : ''; ?>>
                    <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                </select>
            </div>
            <?php endfor; ?>
        </fieldset>

        <!-- Environment Medium (Group C) -->
        <?php if ( $registry->has_input( 'env_medium' ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'env_medium' ) ); ?></legend>
            <?php $desc( 'env_medium' ); ?>
            <div class="ednasurvey-field-row">
                <label for="env_medium"><?php echo esc_html( $registry->get_label( 'env_medium' ) ); ?><?php echo $req( 'env_medium' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <input type="text" id="env_medium" name="env_medium"
                       value="<?php echo esc_attr( $fval( 'env_medium' ) ); ?>"
                       <?php echo $registry->is_required( 'env_medium' ) ? 'required' : ''; ?>>
            </div>
        </fieldset>
        <?php endif; ?>

        <!-- Weather (Group C) -->
        <?php if ( $registry->has_input( 'weather' ) ) :
            $weather_choices = EdnaSurvey_I18n::get_weather_choices();
        ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'weather' ) ); ?></legend>
            <?php $desc( 'weather' ); ?>
            <div class="ednasurvey-field-row">
                <label for="weather"><?php echo esc_html( $registry->get_label( 'weather' ) ); ?><?php echo $req( 'weather' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <select id="weather" name="weather" <?php echo $registry->is_required( 'weather' ) ? 'required' : ''; ?>>
                    <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                    <?php foreach ( $weather_choices as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $fval( 'weather' ), $key ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <?php endif; ?>

        <!-- Wind (Group C) -->
        <?php if ( $registry->has_input( 'wind' ) ) :
            $wind_choices = EdnaSurvey_I18n::get_wind_choices();
        ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'wind' ) ); ?></legend>
            <p class="ednasurvey-help">
                <?php esc_html_e( 'Criterion for "windy": whether a syringe or filter holder used for filtration is continuously moved by the wind', 'wp-ednasurvey' ); ?>
            </p>
            <div class="ednasurvey-field-row">
                <label for="wind"><?php echo esc_html( $registry->get_label( 'wind' ) ); ?><?php echo $req( 'wind' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <select id="wind" name="wind" <?php echo $registry->is_required( 'wind' ) ? 'required' : ''; ?>>
                    <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                    <?php foreach ( $wind_choices as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $fval( 'wind' ), $key ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>
        <?php endif; ?>

        <!-- Numeric pair fields (Group C) -->
        <?php
        $numeric_pairs = array(
            array( 'watervol1', 'watervol2' ),
            array( 'airvol1', 'airvol2' ),
            array( 'weight1', 'weight2' ),
        );
        foreach ( $numeric_pairs as $pair ) :
            if ( ! $registry->has_input( $pair[0] ) ) continue;
            $field_def = $registry->get_field( $pair[0] );
            $step      = 'decimal' === ( $field_def['field_type'] ?? '' ) ? '0.01' : '1';
        ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php
                // Use a shared legend (strip the trailing number)
                $legend = preg_replace( '/\s*1\b/', '', $registry->get_label( $pair[0] ) );
                echo esc_html( $legend );
            ?></legend>
            <?php foreach ( $pair as $nf ) : ?>
            <div class="ednasurvey-field-row">
                <label for="<?php echo esc_attr( $nf ); ?>"><?php echo esc_html( $registry->get_label( $nf ) ); ?><?php echo $req( $nf ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <?php $desc( $nf ); ?>
                <input type="number" id="<?php echo esc_attr( $nf ); ?>" name="<?php echo esc_attr( $nf ); ?>"
                       step="<?php echo esc_attr( $step ); ?>" min="0"
                       value="<?php echo esc_attr( $fval( $nf ) ); ?>"
                       <?php echo $registry->is_required( $nf ) ? 'required' : ''; ?>>
            </div>
            <?php endforeach; ?>
        </fieldset>
        <?php endforeach; ?>

        <!-- Filter Name (Group C) -->
        <?php if ( $registry->has_input( 'filter_name' ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'filter_name' ) ); ?></legend>
            <?php $desc( 'filter_name' ); ?>
            <div class="ednasurvey-field-row">
                <label for="filter_name"><?php echo esc_html( $registry->get_label( 'filter_name' ) ); ?><?php echo $req( 'filter_name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                <input type="text" id="filter_name" name="filter_name"
                       value="<?php echo esc_attr( $fval( 'filter_name' ) ); ?>"
                       <?php echo $registry->is_required( 'filter_name' ) ? 'required' : ''; ?>>
            </div>
        </fieldset>
        <?php endif; ?>

        <!-- Custom Fields -->
        <?php
        // Filter to only those with input
        $visible_custom = array_filter( $custom_fields, fn( $cf ) => in_array( $cf->field_mode ?? 'enabled', array( 'required', 'enabled' ), true ) );
        if ( ! empty( $visible_custom ) ) :
            $copy_custom_values = array();
            if ( $copy_data && ! empty( $copy_data->custom_fields ) ) {
                foreach ( $copy_data->custom_fields as $cf_val ) {
                    $copy_custom_values[ (int) $cf_val->field_id ] = $cf_val->field_value;
                }
            }
        ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Additional Fields', 'wp-ednasurvey' ); ?></legend>
            <?php foreach ( $visible_custom as $cf ) :
                $field_name = 'custom_' . $cf->id;
                $label      = EdnaSurvey_I18n::get_localized_field( $cf->label_local ?? '', $cf->label_en ?? '' );
                $cf_desc    = EdnaSurvey_I18n::get_localized_field( $cf->description_local ?? '', $cf->description_en ?? '' );
                $value      = $copy_custom_values[ (int) $cf->id ] ?? ( $cf->default_value ?? '' );
                $options    = $cf->field_options ? json_decode( $cf->field_options, true ) : array();
                $is_req     = 'required' === ( $cf->field_mode ?? 'enabled' );
            ?>
            <div class="ednasurvey-field-row">
                <label for="<?php echo esc_attr( $field_name ); ?>">
                    <?php echo esc_html( $label ); ?>
                    <?php if ( $is_req ) : ?><span class="required">*</span><?php endif; ?>
                </label>
                <?php if ( '' !== $cf_desc ) : ?>
                    <p class="ednasurvey-help"><?php echo esc_html( $cf_desc ); ?></p>
                <?php endif; ?>
                <?php if ( 'select' === $cf->field_type && ! empty( $options['choices'] ) ) : ?>
                    <select id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                            <?php echo $is_req ? 'required' : ''; ?>>
                        <option value=""><?php esc_html_e( '-- Select --', 'wp-ednasurvey' ); ?></option>
                        <?php foreach ( $options['choices'] as $choice ) : ?>
                            <option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $value, $choice ); ?>>
                                <?php echo esc_html( $choice ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ( 'textarea' === $cf->field_type ) : ?>
                    <textarea id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                              rows="3" <?php echo $is_req ? 'required' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>
                <?php elseif ( 'number' === $cf->field_type ) : ?>
                    <input type="number" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                           step="any" value="<?php echo esc_attr( $value ); ?>"
                           <?php echo $is_req ? 'required' : ''; ?>>
                <?php elseif ( 'date' === $cf->field_type ) : ?>
                    <input type="date" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                           value="<?php echo esc_attr( $value ); ?>"
                           <?php echo $is_req ? 'required' : ''; ?>>
                <?php else : ?>
                    <input type="text" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>"
                           value="<?php echo esc_attr( $value ); ?>"
                           <?php echo $is_req ? 'required' : ''; ?>>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </fieldset>
        <?php endif; ?>

        <!-- Notes (Group C) -->
        <?php if ( $registry->has_input( 'notes' ) ) : ?>
        <fieldset class="ednasurvey-fieldset">
            <legend><?php echo esc_html( $registry->get_label( 'notes' ) ); ?></legend>
            <?php $desc( 'notes' ); ?>
            <div class="ednasurvey-field-row">
                <textarea id="notes" name="notes" rows="4"><?php echo esc_textarea( $fval( 'notes' ) ); ?></textarea>
            </div>
        </fieldset>
        <?php endif; ?>

        <!-- Photos (always enabled) -->
        <fieldset class="ednasurvey-fieldset">
            <legend><?php esc_html_e( 'Photos', 'wp-ednasurvey' ); ?></legend>
            <p class="ednasurvey-help">
                <?php
                /* translators: %d: maximum number of photos */
                printf( esc_html__( 'Upload up to %d photos (JPEG or HEIC/HEIF).', 'wp-ednasurvey' ), $photo_limit );
                ?>
            </p>
            <div class="ednasurvey-file-select">
                <input type="file" id="photos" multiple accept=".jpg,.jpeg,.heic,.heif">
            </div>
            <div id="ednasurvey-photo-list"></div>
        </fieldset>

        <div class="ednasurvey-form-actions">
            <button type="submit" class="button button-primary ednasurvey-submit-btn">
                <?php esc_html_e( 'Review before submitting', 'wp-ednasurvey' ); ?>
            </button>
            <a href="<?php echo esc_url( home_url( '/' . $username . '/' ) ); ?>" class="button">
                <?php esc_html_e( 'Return to Dashboard without submitting', 'wp-ednasurvey' ); ?>
            </a>
        </div>
    </form>

    <div id="ednasurvey-confirm-review" style="display:none;">
        <h2><?php esc_html_e( 'Please review your submission', 'wp-ednasurvey' ); ?></h2>
        <table id="ednasurvey-confirm-table" class="ednasurvey-site-detail-table">
            <tbody></tbody>
        </table>
        <div class="ednasurvey-form-actions">
            <button type="button" id="ednasurvey-confirm-submit" class="button button-primary ednasurvey-submit-btn">
                <?php esc_html_e( 'Submit', 'wp-ednasurvey' ); ?>
            </button>
            <button type="button" id="ednasurvey-confirm-back" class="button">
                <?php esc_html_e( 'Back to Edit', 'wp-ednasurvey' ); ?>
            </button>
        </div>
    </div>

    <script>
        var ednasurveyFormConfig = {
            hasLocation: true,
            copyLat: <?php echo esc_js( $copy_data->latitude ?? 'null' ); ?>,
            copyLng: <?php echo esc_js( $copy_data->longitude ?? 'null' ); ?>,
            photoLimit: <?php echo (int) $photo_limit; ?>,
            envLocalMax: <?php echo (int) $env_local_max; ?>
        };
        <?php
        // Build env_local mapping: env_broad key => [{key, label}, ...]
        $env_local_choices = EdnaSurvey_I18n::get_env_local_choices();
        $env_local_map    = EdnaSurvey_I18n::get_env_local_for_broad();
        $js_mapping = array();
        foreach ( $env_local_map as $broad_key => $local_keys ) {
            $items = array();
            foreach ( $local_keys as $lk ) {
                if ( isset( $env_local_choices[ $lk ] ) ) {
                    $items[] = array( 'key' => $lk, 'label' => $env_local_choices[ $lk ] );
                }
            }
            $js_mapping[ $broad_key ] = $items;
        }
        // Pre-selected values for copy_from
        $copy_env_locals = array();
        for ( $ci = 1; $ci <= 7; $ci++ ) {
            $f = 'env_local' . $ci;
            $copy_env_locals[] = $copy_data->$f ?? ( $registry->get_default_value( $f ) ?: '' );
        }
        // Conflict groups for client-side validation
        $conflict_groups = EdnaSurvey_I18n::get_env_local_conflict_groups();
        ?>
        (function(){
            var mapping = <?php echo wp_json_encode( $js_mapping ); ?>;
            var envLocalConflicts = <?php echo wp_json_encode( $conflict_groups ); ?>;
            var copyValues = <?php echo wp_json_encode( $copy_env_locals ); ?>;
            var selectLabel = <?php echo wp_json_encode( __( '-- Select --', 'wp-ednasurvey' ) ); ?>;
            var envLocalMax = <?php echo (int) $env_local_max; ?>;
            var broadSel = document.getElementById('env_broad');

            function updateEnvLocal() {
                var choices = mapping[broadSel.value] || [];
                var isSterile = (broadSel.value === 'sterile water');
                for (var i = 1; i <= envLocalMax; i++) {
                    var sel = document.getElementById('env_local' + i);
                    if (!sel) continue;
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
                // Auto-select and lock env_local1 for sterile water
                var sel1 = document.getElementById('env_local1');
                if (isSterile) {
                    sel1.value = 'sterile water environment';
                    sel1.disabled = true;
                } else {
                    sel1.disabled = false;
                }
            }

            broadSel.addEventListener('change', updateEnvLocal);

            // Expose conflict groups and current mapping for JS validation
            window.ednasurveyEnvLocalConflicts = envLocalConflicts;
            window.ednasurveyEnvLocalMapping = mapping;

            // Initialize on load (for copy_from or default)
            if (broadSel.value) {
                updateEnvLocal();
                for (var i = 0; i < envLocalMax; i++) {
                    if (copyValues[i]) {
                        var s = document.getElementById('env_local' + (i + 1));
                        if (s) s.value = copyValues[i];
                    }
                }
            }
        })();
    </script>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
