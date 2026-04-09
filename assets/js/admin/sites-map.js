(function($) {
    'use strict';

    $(document).ready(function() {
        var mapEl = document.getElementById('ednasurvey-admin-map');
        if (!mapEl || typeof ednasurveyAdminSites === 'undefined') return;

        var settings = window.ednasurveyMap || {};
        var map = L.map('ednasurvey-admin-map').setView(
            [settings.centerLat || 35.6762, settings.centerLng || 139.6503],
            settings.defaultZoom || 5
        );

        L.tileLayer(settings.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: settings.attribution || '',
            maxZoom: 18
        }).addTo(map);

        var bounds = [];

        ednasurveyAdminSites.forEach(function(site) {
            if (!site.lat || !site.lng) return;

            var i18n = window.ednasurveyAdminMapI18n || {};
            var popup = '<strong>' + escapeHtml(site.name || 'Unnamed') + '</strong>' +
                '<div class="ednasurvey-popup-detail">' +
                'User: ' + escapeHtml(site.user_login) + '<br>' +
                (site.date ? 'Date: ' + escapeHtml(site.date) + '<br>' : '') +
                (site.correspondence ? 'Rep: ' + escapeHtml(site.correspondence) + '<br>' : '') +
                '</div>' +
                (site.detail_url ? '<a href="' + escapeHtml(site.detail_url) + '" class="ednasurvey-popup-btn">' +
                escapeHtml(i18n.detail) + '</a>' : '');

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
        div.appendChild(document.createTextNode(text || ''));
        return div.innerHTML;
    }
})(jQuery);
