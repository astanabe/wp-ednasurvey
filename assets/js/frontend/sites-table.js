(function($) {
    'use strict';

    $(document).ready(function() {
        if ($.fn.DataTable && $('#ednasurvey-sites-table').length) {
            $('#ednasurvey-sites-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    url: '' // DataTables auto-detects or we could load a language file
                },
                responsive: true
            });
        }
    });
})(jQuery);
