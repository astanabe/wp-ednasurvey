(function($) {
    'use strict';

    // Add custom field row
    $('#add-custom-field').on('click', function() {
        var mainRow = '<tr class="custom-field-row" data-field-id="">' +
            '<td><input type="text" class="cf-key" placeholder="field_key"></td>' +
            '<td><input type="text" class="cf-label-local"></td>' +
            '<td><input type="text" class="cf-label-en"></td>' +
            '<td><select class="cf-type">' +
                '<option value="text">Text</option>' +
                '<option value="number">Number</option>' +
                '<option value="select">Select</option>' +
                '<option value="date">Date</option>' +
                '<option value="textarea">Textarea</option>' +
            '</select></td>' +
            '<td><select class="cf-mode">' +
                '<option value="required">' + (ednasurveyAdmin.modeLabels?.required || 'Required') + '</option>' +
                '<option value="enabled" selected>' + (ednasurveyAdmin.modeLabels?.enabled || 'Enabled') + '</option>' +
                '<option value="required_hidden">' + (ednasurveyAdmin.modeLabels?.required_hidden || 'Required (hidden)') + '</option>' +
                '<option value="disabled">' + (ednasurveyAdmin.modeLabels?.disabled || 'Disabled') + '</option>' +
            '</select></td>' +
            '<td><input type="text" class="cf-default"></td>' +
            '<td><input type="text" class="cf-options" placeholder=\'{"choices":["a","b"]}\'></td>' +
            '<td><button type="button" class="button button-small cf-remove">Remove</button></td>' +
            '</tr>';

        var detailRow = '<tr class="custom-field-detail-row" data-field-id="">' +
            '<td></td>' +
            '<td colspan="7">' +
                '<div class="ednasurvey-detail-grid" style="margin-top:0;">' +
                    '<label>Description (XX)<input type="text" class="cf-desc-local"></label>' +
                    '<label>Description (EN)<input type="text" class="cf-desc-en"></label>' +
                    '<label>Example (XX)<input type="text" class="cf-example-local"></label>' +
                    '<label>Example (EN)<input type="text" class="cf-example-en"></label>' +
                '</div>' +
            '</td>' +
            '</tr>';

        $('#custom-fields-body').append(mainRow + detailRow);
    });

    // Remove custom field row (both main and detail rows)
    $(document).on('click', '.cf-remove', function() {
        var $mainRow = $(this).closest('tr.custom-field-row');
        var $detailRow = $mainRow.next('tr.custom-field-detail-row');
        $mainRow.add($detailRow).addClass('removing').fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Save custom fields via AJAX
    $('#save-custom-fields').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        var fields = [];

        $('#custom-fields-body .custom-field-row').each(function() {
            var $row = $(this);
            var $detail = $row.next('tr.custom-field-detail-row');
            var optionsStr = $row.find('.cf-options').val().trim();
            var options = null;

            if (optionsStr) {
                try {
                    options = JSON.parse(optionsStr);
                } catch (e) {
                    options = null;
                }
            }

            fields.push({
                id: $row.data('field-id') || '',
                field_key: $row.find('.cf-key').val(),
                label_local: $row.find('.cf-label-local').val(),
                label_en: $row.find('.cf-label-en').val(),
                field_type: $row.find('.cf-type').val(),
                field_mode: $row.find('.cf-mode').val(),
                default_value: $row.find('.cf-default').val(),
                field_options: options,
                description_local: $detail.find('.cf-desc-local').val() || '',
                description_en: $detail.find('.cf-desc-en').val() || '',
                example_local: $detail.find('.cf-example-local').val() || '',
                example_en: $detail.find('.cf-example-en').val() || ''
            });
        });

        $.ajax({
            url: ednasurveyAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ednasurvey_save_custom_fields',
                nonce: ednasurveyAdmin.nonce,
                fields: JSON.stringify(fields)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || 'Saved!');
                    location.reload();
                } else {
                    alert(response.data.message || 'Error saving fields.');
                }
            },
            error: function() {
                alert('Server error.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
})(jQuery);
