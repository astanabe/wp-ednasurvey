(function($) {
    'use strict';

    var i18n = (window.ednasurveyAjax && window.ednasurveyAjax.i18n) ? window.ednasurveyAjax.i18n : {};
    var cfg  = window.ednasurveyOfflineConfig || {};

    var state = {
        numSites: 1,
        maxPhotos: 0,
        sessionId: null,
        photos: [],       // [{stored_filename, original_filename, thumbnail_url, exif_datetime, exif_latitude, exif_longitude}]
        parsedSites: [],  // from analyze response
        map: null,
        markers: []
    };

    // ── Utilities ──────────────────────────────────────────────────

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text || ''));
        return div.innerHTML;
    }

    function showStep(step) {
        $('#ednasurvey-submission-messages').empty();
        $('#ednasurvey-offline-step0').toggle(step === 0);
        $('#ednasurvey-offline-step1').toggle(step === 1);
        $('#ednasurvey-offline-step2').toggle(step === 2);
        $('#ednasurvey-offline-step3').toggle(step === 3);

        if (step === 1) renderPhotoList();
        if (step === 3) initMap();

        $('html, body').animate({ scrollTop: $('#ednasurvey-submission-messages').offset().top - 30 }, 300);
    }

    function showErrors(messages) {
        var $el = $('#ednasurvey-submission-messages');
        var html = '<div class="ednasurvey-alert ednasurvey-alert-error"><ul>';
        messages.forEach(function(m) { html += '<li>' + escapeHtml(m) + '</li>'; });
        html += '</ul></div>';
        $el.html(html);
    }

    function showWarnings(messages) {
        if (!messages || !messages.length) return;
        var $el = $('#ednasurvey-submission-messages');
        var html = '<div class="ednasurvey-alert ednasurvey-alert-warning"><ul>';
        messages.forEach(function(m) { html += '<li>' + escapeHtml(m) + '</li>'; });
        html += '</ul></div>';
        $el.append(html);
    }

    // ── Step 0 ─────────────────────────────────────────────────────

    function initStep0() {
        $('#ednasurvey-step0-next').on('click', function() {
            var num = parseInt($('#ednasurvey-num-sites').val(), 10);
            if (isNaN(num) || num < 1) {
                showErrors([i18n.errorOccurred || 'Please enter a valid number.']);
                return;
            }
            state.numSites = num;
            state.maxPhotos = num * cfg.photoLimit;
            updatePhotoLimitMsg();
            showStep(1);
        });
    }

    function updatePhotoLimitMsg() {
        $('#ednasurvey-photo-limit-msg').text(
            (i18n.photoLimitMsg || 'Upload up to {max} photos ({limit} per site).')
                .replace('{max}', state.maxPhotos)
                .replace('{limit}', cfg.photoLimit)
        );
    }

    // ── Step 1: Photo upload ───────────────────────────────────────

    function initStep1() {
        var $input = $('#ednasurvey-photos-input');

        $input.on('change', function() { handlePhotoFiles(this.files); this.value = ''; });

        $('#ednasurvey-step1-next').on('click', function() { showStep(2); });
        $('#ednasurvey-step1-back').on('click', function() { showStep(0); });
    }

    function handlePhotoFiles(files) {
        var remaining = state.maxPhotos - state.photos.length;
        if (files.length > remaining) {
            showErrors([(i18n.tooManyPhotos || 'Maximum {max} photos allowed. You can add {remaining} more.')
                .replace('{max}', state.maxPhotos).replace('{remaining}', remaining)]);
            return;
        }

        // Build a single FormData with all files
        var fd = new FormData();
        fd.append('action', 'ednasurvey_upload_temp_photos');
        fd.append('nonce', ednasurveyAjax.nonce);
        fd.append('session_id', state.sessionId || '');
        fd.append('num_sites', state.numSites);
        for (var i = 0; i < files.length; i++) {
            fd.append('photos[]', files[i]);
        }

        var $ph = $('<div class="ednasurvey-photo-uploading">' + escapeHtml(i18n.uploading || 'Uploading...') + '</div>');
        $('#ednasurvey-photo-list').append($ph);

        $.ajax({
            url: ednasurveyAjax.ajaxUrl, type: 'POST', data: fd,
            processData: false, contentType: false,
            success: function(res) {
                $ph.remove();
                if (res.success) {
                    if (!state.sessionId) state.sessionId = res.data.session_id;
                    res.data.photos.forEach(function(p) { state.photos.push(p); });
                    renderPhotoList();
                } else {
                    showErrors(res.data.messages || [i18n.errorOccurred || 'Upload failed.']);
                }
            },
            error: function() { $ph.remove(); showErrors([i18n.serverError || 'Server error.']); }
        });
    }

    function renderPhotoList() {
        var $list = $('#ednasurvey-photo-list');
        $list.empty();

        state.photos.forEach(function(p, idx) {
            var gps = (p.exif_latitude && p.exif_longitude)
                ? p.exif_latitude + ', ' + p.exif_longitude : 'N/A';
            var dt = p.exif_datetime || 'N/A';

            var $item = $('<div class="ednasurvey-temp-photo-item">');
            var $img  = $('<img>').attr('src', p.thumbnail_url).attr('alt', '');
            var $info = $('<div class="ednasurvey-temp-photo-info">')
                .append($('<strong>').text(p.original_filename))
                .append('<br>EXIF: ' + escapeHtml(dt))
                .append('<br>GPS: ' + escapeHtml(gps));
            var $btn  = $('<button type="button" class="button button-small">')
                .text('\u00D7')
                .on('click', (function(photoIdx) {
                    return function() { deletePhoto(photoIdx); };
                })(idx));

            $item.append($img).append($info).append($btn);
            $list.append($item);
        });

        updatePhotoLimitMsg();
    }

    function deletePhoto(idx) {
        var photo = state.photos[idx];
        if (!photo) return;

        $.post(ednasurveyAjax.ajaxUrl, {
            action: 'ednasurvey_delete_temp_photo',
            nonce: ednasurveyAjax.nonce,
            session_id: state.sessionId,
            stored_filename: photo.stored_filename
        }, function(res) {
            if (res.success) {
                state.photos.splice(idx, 1);
                renderPhotoList();
            }
        });
    }

    // ── Step 2: Excel upload ───────────────────────────────────────

    function initStep2() {
        var $input = $('#ednasurvey-excel-input');
        var $btn   = $('#ednasurvey-step2-upload');

        $input.on('change', function() {
            if (this.files[0]) {
                $btn.prop('disabled', false);
            }
        });

        $btn.on('click', function() {
            var file = $input[0].files[0];
            if (!file) return;

            var $b = $(this).prop('disabled', true);
            var label = $b.text();
            $b.text(i18n.analyzing || 'Analyzing...');

            var fd = new FormData();
            fd.append('action', 'ednasurvey_analyze_offline_excel');
            fd.append('nonce', ednasurveyAjax.nonce);
            fd.append('session_id', state.sessionId || '');
            fd.append('excel_file', file);

            $.ajax({
                url: ednasurveyAjax.ajaxUrl, type: 'POST', data: fd,
                processData: false, contentType: false,
                success: function(res) {
                    if (res.success) {
                        state.parsedSites = res.data.sites;
                        showStep(3);
                        showWarnings(res.data.warnings);
                    } else {
                        // Error: go back to Step 1 with errors
                        showStep(1);
                        showErrors(res.data.messages || [i18n.errorOccurred || 'Analysis failed.']);
                    }
                },
                error: function() { showErrors([i18n.serverError || 'Server error.']); },
                complete: function() { $b.prop('disabled', false).text(label); }
            });
        });

        $('#ednasurvey-step2-back').on('click', function() { showStep(1); });
    }

    // ── Step 3: Map confirmation ───────────────────────────────────

    function initMap() {
        if (state.map) {
            state.map.remove();
            state.map = null;
            state.markers = [];
        }

        var settings = window.ednasurveyMap || {};
        state.map = L.map('ednasurvey-offline-map').setView(
            [settings.centerLat || 35.6762, settings.centerLng || 139.6503],
            settings.defaultZoom || 5
        );
        L.tileLayer(settings.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: settings.attribution || '', maxZoom: 18
        }).addTo(state.map);

        renderSitesOnMap();
    }

    function renderSitesOnMap() {
        var $list  = $('#ednasurvey-offline-sites-list');
        var $review = $('#ednasurvey-offline-data-review');
        $list.empty();
        $review.empty();
        state.markers.forEach(function(m) { m.remove(); });
        state.markers = [];
        var bounds = [];

        // Keys to skip in data review (internal/meta fields)
        var skipKeys = { photo_files: 1 };

        state.parsedSites.forEach(function(site, idx) {
            var name = site.raw_data.sitename_local || site.raw_data.sitename_en || ('Site ' + (idx + 1));
            var noPhotos = site.no_photos;
            var gpsSource = site.gps_from_photo
                ? (i18n.gpsFromPhoto || 'GPS from photo EXIF')
                : (site.has_location ? (i18n.gpsFromExcel || 'GPS from Excel') : '');

            // Site card (map section)
            var cardHtml = '<div class="ednasurvey-offline-site-item">' +
                '<strong>' + escapeHtml(name) + '</strong> (' + escapeHtml(site.raw_data.sample_id || '') + ')' +
                (site.has_location
                    ? ' <span style="color:green;">&#10003; ' + site.latitude + ', ' + site.longitude + '</span>'
                    : ' <span style="color:red;">&#10007; ' + (i18n.noLocation || 'No location - click map') + '</span>') +
                (gpsSource ? ' <span class="ednasurvey-gps-source">' + escapeHtml(gpsSource) + '</span>' : '') +
                (noPhotos ? ' <span class="ednasurvey-no-photo-badge">' + (i18n.noPhotos || 'No photos') + '</span>' : '') +
                '</div>';
            $list.append(cardHtml);

            // Data review table
            var reviewHtml = '<details style="margin-bottom:0.75em;">' +
                '<summary><strong>' + escapeHtml(name) + '</strong> (' + escapeHtml(site.raw_data.sample_id || '') + ')' +
                (noPhotos ? ' <span class="ednasurvey-no-photo-badge">' + (i18n.noPhotos || 'No photos') + '</span>' : '') +
                '</summary>' +
                '<table class="ednasurvey-site-detail-table" style="margin-top:0.5em;"><tbody>';
            var raw = site.raw_data || {};
            for (var key in raw) {
                if (!raw.hasOwnProperty(key) || skipKeys[key]) continue;
                var val = raw[key];
                if (val === null || val === undefined || val === '') continue;
                reviewHtml += '<tr><th>' + escapeHtml(key) + '</th><td>' + escapeHtml(String(val)) + '</td></tr>';
            }
            // Show photo count
            var photoCount = (site.matched_photos || []).length;
            if (photoCount > 0) {
                var photoNames = site.matched_photos.map(function(p) { return p.original_filename; }).join(', ');
                reviewHtml += '<tr><th>photos</th><td>' + photoCount + ' (' + escapeHtml(photoNames) + ')</td></tr>';
            }
            reviewHtml += '</tbody></table></details>';
            $review.append(reviewHtml);

            // Marker
            if (site.has_location) {
                var m = L.marker([site.latitude, site.longitude], { draggable: true })
                    .addTo(state.map)
                    .bindPopup(escapeHtml(name));
                m._siteIdx = idx;
                m.on('dragend', function(e) {
                    var pos = e.target.getLatLng();
                    var s = state.parsedSites[this._siteIdx];
                    s.latitude  = Math.round(pos.lat * 1000000) / 1000000;
                    s.longitude = Math.round(pos.lng * 1000000) / 1000000;
                    renderSitesOnMap();
                });
                state.markers.push(m);
                bounds.push([site.latitude, site.longitude]);
            }
        });

        // Click map for unlocated sites
        state.map.off('click');
        var nextUnlocated = findUnlocated();
        if (nextUnlocated !== null) {
            state.map.on('click', function(e) {
                var idx = findUnlocated();
                if (idx === null) return;
                var s = state.parsedSites[idx];
                s.latitude     = Math.round(e.latlng.lat * 1000000) / 1000000;
                s.longitude    = Math.round(e.latlng.lng * 1000000) / 1000000;
                s.has_location = true;
                renderSitesOnMap();
            });
        }

        if (bounds.length > 0) {
            state.map.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    function findUnlocated() {
        for (var i = 0; i < state.parsedSites.length; i++) {
            if (!state.parsedSites[i].has_location) return i;
        }
        return null;
    }

    function initConfirm() {
        $('#ednasurvey-offline-confirm').on('click', function() {
            var missing = state.parsedSites.filter(function(s) { return !s.has_location; });
            if (missing.length > 0) {
                showErrors([i18n.missingLocations || 'Some sites are missing location. Please set all locations on the map.']);
                return;
            }

            var $btn = $(this).prop('disabled', true);
            var label = $btn.text();
            $btn.text(i18n.submitting || 'Submitting...');

            $.post(ednasurveyAjax.ajaxUrl, {
                action: 'ednasurvey_confirm_offline',
                nonce: ednasurveyAjax.nonce,
                session_id: state.sessionId || '',
                sites: JSON.stringify(state.parsedSites)
            }, function(res) {
                if (res.success) {
                    $('#ednasurvey-offline-step3').hide();
                    var html = '<div class="ednasurvey-alert ednasurvey-alert-success">' +
                        '<p><strong>' + (i18n.submitSuccess || 'All sites submitted successfully!') + '</strong></p>' +
                        '<p><a href="' + escapeHtml(res.data.redirect_url) + '" class="button button-primary">' +
                        (i18n.backToDashboard || 'Back to Dashboard') + '</a></p></div>';
                    $('#ednasurvey-submission-messages').html(html);
                } else {
                    showErrors(res.data.messages || [i18n.errorOccurred || 'Submission failed.']);
                }
            }).fail(function() {
                showErrors([i18n.serverError || 'Server error.']);
            }).always(function() {
                $btn.prop('disabled', false).text(label);
            });
        });

        $('#ednasurvey-step3-back').on('click', function() {
            if (state.map) { state.map.remove(); state.map = null; state.markers = []; }
            showStep(2);
        });
    }

    // ── Init ───────────────────────────────────────────────────────

    $(document).ready(function() {
        initStep0();
        initStep1();
        initStep2();
        initConfirm();
    });

})(jQuery);
