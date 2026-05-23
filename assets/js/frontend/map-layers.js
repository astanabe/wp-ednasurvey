/**
 * Shared base-layer setup for all eDNA Survey Leaflet maps.
 *
 * Reads window.ednasurveyMap (localized by EdnaSurvey_Assets):
 *   tileUrl, attribution, tileUrl2, attribution2, switchLabel
 *
 * When a 2nd tile server is configured, both base layers are added to the
 * single map and kept loaded so they preload tiles for the current view; a
 * "<label> [1] [2]" switch bar is inserted above the map element and toggling
 * swaps the z-index for an instant change. The two layers share the map's
 * view, so position/zoom stay perfectly in sync. Only the active layer's
 * attribution is shown.
 *
 * Usage (replaces `L.tileLayer(...).addTo(map)`):
 *   EdnaSurveyMapLayers.setup(map, 'ednasurvey-map');
 */
(function (window) {
    'use strict';

    var DEFAULT_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var MAX_ZOOM = 18;

    function makeLayer(url) {
        return L.tileLayer(url, { maxZoom: MAX_ZOOM });
    }

    var EdnaSurveyMapLayers = {
        /**
         * @param {L.Map}  map     Leaflet map instance.
         * @param {string} mapElId DOM id of the map container element.
         */
        setup: function (map, mapElId) {
            var s = window.ednasurveyMap || {};
            var url1 = s.tileUrl || DEFAULT_URL;
            var url2 = s.tileUrl2 || '';
            var attrs = [ s.attribution || '', s.attribution2 || '' ];

            var layer1 = makeLayer(url1);
            layer1.addTo(map);

            // Single base layer (backward compatible): no switch bar.
            if (!url2) {
                if (attrs[0] && map.attributionControl) {
                    map.attributionControl.addAttribution(attrs[0]);
                }
                return;
            }

            var layers = [ layer1, makeLayer(url2) ];
            layers[1].addTo(map); // both stay on the map -> both preload tiles

            var buttons = [];
            var active = -1;

            function setActive(idx) {
                active = idx;
                layers[idx].bringToFront();
                if (map.attributionControl) {
                    attrs.forEach(function (a) {
                        if (a) { map.attributionControl.removeAttribution(a); }
                    });
                    if (attrs[idx]) { map.attributionControl.addAttribution(attrs[idx]); }
                }
                buttons.forEach(function (b, i) {
                    if (i === idx) {
                        b.classList.add('is-active');
                        b.setAttribute('aria-pressed', 'true');
                    } else {
                        b.classList.remove('is-active');
                        b.setAttribute('aria-pressed', 'false');
                    }
                });
            }

            // Build the switch bar above the map element.
            var mapEl = document.getElementById(mapElId);
            if (mapEl && mapEl.parentNode) {
                // Remove a previously-inserted bar for this map (e.g. the offline
                // map is rebuilt each time step 3 is shown).
                var prev = mapEl.parentNode.querySelector('.ednasurvey-map-switch[data-for="' + mapElId + '"]');
                if (prev) { prev.parentNode.removeChild(prev); }

                var bar = document.createElement('div');
                bar.className = 'ednasurvey-map-switch';
                bar.setAttribute('data-for', mapElId);

                var label = document.createElement('span');
                label.className = 'ednasurvey-map-switch-label';
                label.textContent = s.switchLabel || 'Map:';
                bar.appendChild(label);

                [1, 2].forEach(function (n, i) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'ednasurvey-map-switch-btn';
                    b.textContent = String(n);
                    b.addEventListener('click', function () { setActive(i); });
                    bar.appendChild(b);
                    buttons.push(b);
                });

                mapEl.parentNode.insertBefore(bar, mapEl);
            }

            setActive(0);
        }
    };

    window.EdnaSurveyMapLayers = EdnaSurveyMapLayers;
})(window);
