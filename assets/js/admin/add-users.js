(function($) {
    'use strict';

    $(document).ready(function() {
        $('#ednasurvey-import-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $messages = $('#ednasurvey-import-messages');
            var $results = $('#ednasurvey-import-results');
            $messages.empty();
            $results.hide();

            var formData = new FormData(this);

            $form.find('button[type="submit"]').prop('disabled', true).text('Importing...');

            $.ajax({
                url: ednasurveyAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $messages.html(
                            '<div class="notice notice-success"><p>' +
                            escapeHtml(response.data.message) +
                            '</p></div>'
                        );

                        $results.show();
                        $('#ednasurvey-import-summary').html(
                            '<p><strong>Created:</strong> ' + parseInt(response.data.created) + '</p>' +
                            '<p><strong>Skipped:</strong> ' + response.data.skipped.length + '</p>'
                        );

                        if (response.data.skipped.length > 0) {
                            var list = '<h4>Skipped emails (already exist):</h4><ul>';
                            response.data.skipped.forEach(function(email) {
                                list += '<li>' + escapeHtml(email) + '</li>';
                            });
                            list += '</ul>';
                            $('#ednasurvey-import-skipped').html(list);
                        }

                        $form[0].reset();
                    } else {
                        $messages.html(
                            '<div class="notice notice-error"><p>' +
                            escapeHtml(response.data.message || 'Error') +
                            '</p></div>'
                        );
                    }
                },
                error: function() {
                    $messages.html(
                        '<div class="notice notice-error"><p>Server error. Please try again.</p></div>'
                    );
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false).text('Import Users');
                }
            });
        });
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text || ''));
        return div.innerHTML;
    }
})(jQuery);
