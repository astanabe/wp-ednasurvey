(function($) {
    'use strict';

    $(document).ready(function() {
        var pluginSlug = 'wp-ednasurvey/wp-ednasurvey.php';
        var $link = $('tr[data-plugin="' + pluginSlug + '"] .deactivate a');

        if (!$link.length) return;

        var originalHref = $link.attr('href');

        $link.on('click', function(e) {
            e.preventDefault();

            var msg = (ednasurveyDeactivate && ednasurveyDeactivate.confirmMessage)
                ? ednasurveyDeactivate.confirmMessage
                : 'Do you want to delete all eDNA Survey data (database tables and uploaded photos) when the plugin is uninstalled?\n\nClick OK to delete data on uninstall.\nClick Cancel to keep data.';

            var deleteData = confirm(msg);

            $.post(ednasurveyDeactivate.ajaxUrl, {
                action: 'ednasurvey_set_delete_flag',
                nonce: ednasurveyDeactivate.nonce,
                delete_data: deleteData ? '1' : '0'
            }).always(function() {
                window.location.href = originalHref;
            });
        });
    });
})(jQuery);
