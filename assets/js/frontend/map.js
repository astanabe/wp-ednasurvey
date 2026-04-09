(function($) {
    'use strict';

    $(document).ready(function() {
        var mapEl = document.getElementById('ednasurvey-user-map');
        if (!mapEl || typeof ednasurveyUserSites === 'undefined') return;

        var settings = window.ednasurveyMap || {};
        var map = L.map('ednasurvey-user-map').setView(
            [settings.centerLat || 35.6762, settings.centerLng || 139.6503],
            settings.defaultZoom || 5
        );

        L.tileLayer(settings.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: settings.attribution || '',
            maxZoom: 18
        }).addTo(map);

        var bounds = [];

        ednasurveyUserSites.forEach(function(site) {
            if (!site.lat || !site.lng) return;

            var cfg = window.ednasurveyMapConfig || {};
            var popup = '<strong>' + escapeHtml(site.name || 'Unnamed') + '</strong>' +
                '<div class="ednasurvey-popup-detail">' +
                (site.date ? 'Date: ' + escapeHtml(site.date) + '<br>' : '') +
                (site.time ? 'Time: ' + escapeHtml(site.time) + '<br>' : '') +
                (site.sample_id ? 'Sample ID: ' + escapeHtml(site.sample_id) + '<br>' : '') +
                '</div>' +
                '<a href="' + escapeHtml(cfg.detailBaseUrl + site.internal_sample_id) + '" class="ednasurvey-popup-btn">' +
                escapeHtml(cfg.detailLabel) + '</a> ' +
                '<a href="' + escapeHtml(cfg.resubmitBaseUrl + site.internal_sample_id) + '" class="ednasurvey-popup-btn">' +
                escapeHtml(cfg.resubmitLabel) + '</a>';

            L.marker([site.lat, site.lng])
                .addTo(map)
                .bindPopup(popup);

            bounds.push([site.lat, site.lng]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
})(jQuery);
