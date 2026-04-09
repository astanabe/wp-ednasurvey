(function($) {
    'use strict';

    // Add custom field row
    $('#add-custom-field').on('click', function() {
        var row = '<tr class="custom-field-row" data-field-id="">' +
            '<td><input type="text" class="cf-key" placeholder="field_key"></td>' +
            '<td><input type="text" class="cf-label-ja" placeholder="Japanese label"></td>' +
            '<td><input type="text" class="cf-label-en" placeholder="English label"></td>' +
            '<td><select class="cf-type">' +
                '<option value="text">Text</option>' +
                '<option value="number">Number</option>' +
                '<option value="select">Select</option>' +
                '<option value="date">Date</option>' +
                '<option value="textarea">Textarea</option>' +
            '</select></td>' +
            '<td><input type="checkbox" class="cf-required"></td>' +
            '<td><input type="checkbox" class="cf-active" checked></td>' +
            '<td><input type="text" class="cf-options" placeholder=\'{"choices":["a","b"]}\'></td>' +
            '<td><button type="button" class="button button-small cf-remove">Remove</button></td>' +
            '</tr>';
        $('#custom-fields-body').append(row);
    });

    // Remove custom field row
    $(document).on('click', '.cf-remove', function() {
        $(this).closest('tr').addClass('removing').fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Save custom fields via AJAX
    $('#save-custom-fields').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        var fields = [];

        $('#custom-fields-body .custom-field-row').each(function() {
            var $row = $(this);
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
                label_ja: $row.find('.cf-label-ja').val(),
                label_en: $row.find('.cf-label-en').val(),
                field_type: $row.find('.cf-type').val(),
                is_required: $row.find('.cf-required').is(':checked') ? 1 : 0,
                is_active: $row.find('.cf-active').is(':checked') ? 1 : 0,
                field_options: options
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
