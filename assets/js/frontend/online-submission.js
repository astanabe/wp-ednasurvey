(function($) {
    'use strict';

    var map, marker;
    var i18n = (window.ednasurveyAjax && window.ednasurveyAjax.i18n) ? window.ednasurveyAjax.i18n : {};

    var state = {
        sessionId: null,
        photos: [],  // [{stored_filename, original_filename, thumbnail_url, exif_datetime, exif_latitude, exif_longitude}]
        uploadingCount: 0
    };

    function initMap() {
        if (!ednasurveyFormConfig.hasLocation) return;

        var mapEl = document.getElementById('ednasurvey-map');
        if (!mapEl) return;

        var settings = window.ednasurveyMap || {};
        var centerLat = settings.centerLat || 35.6762;
        var centerLng = settings.centerLng || 139.6503;
        // Overview zoom for the initial (location-unknown) view, and a higher
        // input zoom for entering/confirming/adjusting an actual location.
        var overviewZoom = settings.defaultZoom || 5;
        var inputZoom = settings.inputZoom || 18;

        map = L.map('ednasurvey-map').setView([centerLat, centerLng], overviewZoom);

        EdnaSurveyMapLayers.setup(map, 'ednasurvey-map');

        // If copy data has coordinates, set marker
        if (ednasurveyFormConfig.copyLat && ednasurveyFormConfig.copyLng) {
            setMarker(ednasurveyFormConfig.copyLat, ednasurveyFormConfig.copyLng);
            map.setView([ednasurveyFormConfig.copyLat, ednasurveyFormConfig.copyLng], inputZoom);
        } else {
            // Try to get current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    map.setView([pos.coords.latitude, pos.coords.longitude], inputZoom);
                }, function() {
                    // Geolocation failed, keep default view
                });
            }
        }

        // Click to set pin; zoom in to input precision on the first rough click.
        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
            if (map.getZoom() < inputZoom) {
                map.setView([e.latlng.lat, e.latlng.lng], inputZoom);
            }
        });
    }

    function setMarker(lat, lng) {
        lat = Math.round(lat * 1000000) / 1000000;
        lng = Math.round(lng * 1000000) / 1000000;

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function(e) {
                var pos = e.target.getLatLng();
                updateCoords(pos.lat, pos.lng);
            });
        }

        updateCoords(lat, lng);
    }

    function updateCoords(lat, lng) {
        lat = Math.round(lat * 1000000) / 1000000;
        lng = Math.round(lng * 1000000) / 1000000;
        $('#latitude').val(lat);
        $('#longitude').val(lng);
        $('#coords-display').text('Lat: ' + lat + ', Lng: ' + lng);
    }

    // ── Photo upload (temp) ───────────────────────────────────────

    function initPhotoUpload() {
        $('.ednasurvey-photo-input').on('change', function() {
            var files = this.files;
            if (!files || !files.length) return;

            var limit = ednasurveyFormConfig.photoLimit || 10;
            if (files.length + state.photos.length > limit) {
                showErrors([(i18n.tooManyPhotos || 'Maximum {max} photos allowed. You can add {remaining} more.')
                    .replace('{max}', limit).replace('{remaining}', limit - state.photos.length)]);
                this.value = '';
                return;
            }

            var fd = new FormData();
            fd.append('action', 'ednasurvey_upload_temp_photos');
            fd.append('nonce', ednasurveyAjax.nonce);
            fd.append('session_id', state.sessionId || '');
            fd.append('num_sites', '1');
            for (var j = 0; j < files.length; j++) {
                fd.append('photos[]', files[j]);
            }

            var $phTop    = $('<div class="ednasurvey-photo-uploading">' + escapeHtml(i18n.uploading || 'Uploading...') + '</div>');
            var $phBottom = $phTop.clone();
            $('#ednasurvey-photo-list-top').append($phTop);
            $('#ednasurvey-photo-list-bottom').append($phBottom);
            this.value = '';
            state.uploadingCount++;

            $.ajax({
                url: ednasurveyAjax.ajaxUrl, type: 'POST', data: fd,
                processData: false, contentType: false,
                success: function(res) {
                    $phTop.remove();
                    $phBottom.remove();
                    if (res.success) {
                        if (!state.sessionId) state.sessionId = res.data.session_id;
                        $('#ednasurvey-session-id').val(state.sessionId);
                        res.data.photos.forEach(function(p) { state.photos.push(p); });
                        renderPhotoList();
                    } else {
                        showErrors(res.data.messages || [i18n.errorOccurred || 'Upload failed.']);
                    }
                },
                error: function() {
                    $phTop.remove();
                    $phBottom.remove();
                    showErrors([i18n.serverError || 'Server error.']);
                },
                complete: function() {
                    state.uploadingCount = Math.max(0, state.uploadingCount - 1);
                }
            });
        });
    }

    function renderPhotoList() {
        // Sort by exif_datetime ascending; photos without datetime go last
        state.photos.sort(function(a, b) {
            var da = a.exif_datetime || '';
            var db = b.exif_datetime || '';
            if (da && !db) return -1;
            if (!da && db) return 1;
            return da < db ? -1 : da > db ? 1 : 0;
        });

        $('.ednasurvey-photo-list').each(function() {
            var $list = $(this);
            // Detach any "uploading" placeholders so we can re-append them at the end
            var $placeholders = $list.children('.ednasurvey-photo-uploading').detach();
            $list.empty();

            state.photos.forEach(function(p, idx) {
                var gps = (p.exif_latitude && p.exif_longitude)
                    ? p.exif_latitude + ', ' + p.exif_longitude : 'N/A';
                var dt = p.exif_datetime ? p.exif_datetime.substring(0, 16) : 'N/A';

                var $item = $('<div class="ednasurvey-temp-photo-item">');
                var $img  = $('<img>').attr('src', p.thumbnail_url).attr('alt', '');
                var $info = $('<div class="ednasurvey-temp-photo-info">')
                    .append($('<strong>').text(p.original_filename))
                    .append('<br>' + escapeHtml(i18n.exifDatetime || 'Date/Time') + ': ' + escapeHtml(dt))
                    .append('<br>GPS: ' + escapeHtml(gps));
                var $btn  = $('<button type="button" class="button button-small">')
                    .text('\u00D7')
                    .on('click', (function(photoIdx) {
                        return function() { deletePhoto(photoIdx); };
                    })(idx));

                $item.append($img).append($info).append($btn);
                $list.append($item);
            });

            $list.append($placeholders);
        });
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

    // Check env_local conflict groups
    function checkEnvLocalConflicts() {
        var conflicts = window.ednasurveyEnvLocalConflicts || [];
        var mapping = window.ednasurveyEnvLocalMapping || {};
        if (!conflicts.length) return [];

        var selected = [];
        for (var i = 1; i <= 7; i++) {
            var val = $('#env_local' + i).val();
            if (val) selected.push(val);
        }

        // Build key-to-label lookup from current broad's mapping
        var labelMap = {};
        var broadVal = $('#env_broad').val();
        if (broadVal && mapping[broadVal]) {
            mapping[broadVal].forEach(function(item) {
                labelMap[item.key] = item.label;
            });
        }

        var errors = [];
        conflicts.forEach(function(group) {
            var found = [];
            for (var j = 0; j < group.length; j++) {
                if (selected.indexOf(group[j]) !== -1) {
                    found.push(group[j]);
                }
            }
            for (var a = 0; a < found.length - 1; a++) {
                for (var b = a + 1; b < found.length; b++) {
                    var l1 = labelMap[found[a]] || found[a];
                    var l2 = labelMap[found[b]] || found[b];
                    var msg = (i18n.envLocalConflict || 'Environment (Local) "{label1}" and "{label2}" cannot be selected together.')
                        .replace('{label1}', l1).replace('{label2}', l2);
                    errors.push(msg);
                }
            }
        });

        return errors;
    }

    // Form submission with confirmation step
    function initFormSubmission() {
        var $form = $('#ednasurvey-online-form');
        var $confirm = $('#ednasurvey-confirm-review');
        var $confirmTable = $('#ednasurvey-confirm-table');

        // Step 1: Show confirmation
        $form.on('submit', function(e) {
            e.preventDefault();
            $('#ednasurvey-submission-messages').empty();

            // Block while photo uploads are still in flight
            if (state.uploadingCount > 0) {
                showErrors([i18n.photoUploadInProgress || 'Please wait until photo upload is complete.']);
                return;
            }

            // Client-side env_local conflict check
            var conflictErrors = checkEnvLocalConflicts();
            if (conflictErrors.length > 0) {
                showErrors(conflictErrors);
                return;
            }

            // Build confirmation table from form fields (skip duplicated fieldsets)
            var rows = '';
            $form.find('.ednasurvey-fieldset').not('.ednasurvey-skip-in-confirm').each(function() {
                var legend = $(this).find('legend').text();
                $(this).find('.ednasurvey-field-row, .ednasurvey-file-select').each(function() {
                    var label = $(this).find('label').first().clone().children('.required').remove().end().text().trim();
                    var $input = $(this).find('input, select, textarea').first();
                    var val = '';

                    if ($input.is('select')) {
                        val = $input.find('option:selected').text().trim();
                        if (val === (i18n.selectPlaceholder || '-- Select --')) val = '';
                    } else if ($input.is('input[type="file"]')) {
                        if (!label) {
                            label = $(this).closest('.ednasurvey-fieldset').find('legend').text() || '';
                        }
                        var count = state.photos.length;
                        val = (i18n.photoFileCount || '{count} file(s)').replace('{count}', count);
                    } else {
                        val = $input.val() || '';
                    }

                    if (label && (val || $input.prop('required'))) {
                        rows += '<tr><th>' + escapeHtml(label) + '</th><td>' + escapeHtml(val || '-') + '</td></tr>';
                    }
                });
            });

            $confirmTable.find('tbody').html(rows);
            $form.hide();
            $form.prev('.ednasurvey-alert-warning').hide();
            $confirm.show();
            $('html, body').animate({ scrollTop: $confirm.offset().top - 50 }, 300);
        });

        // Back to edit
        $('#ednasurvey-confirm-back').on('click', function() {
            $confirm.hide();
            $form.show();
            $form.prev('.ednasurvey-alert-warning').show();
        });

        // Step 2: Actual submission
        $('#ednasurvey-confirm-submit').on('click', function() {
            var $btn = $(this);
            var $messages = $('#ednasurvey-submission-messages');
            var btnLabel = $btn.text();

            // Safety net: if somehow uploads are still in flight, block here too
            if (state.uploadingCount > 0) {
                showErrors([i18n.photoUploadInProgress || 'Please wait until photo upload is complete.']);
                return;
            }

            $btn.prop('disabled', true).text(i18n.submitting || 'Submitting...');
            $messages.empty();

            // Re-enable disabled selects so their values are included in FormData
            $form.find('select:disabled').prop('disabled', false);
            var formData = new FormData($form[0]);

            $.ajax({
                url: ednasurveyAjax.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        $confirm.hide();

                        var html = '<div class="ednasurvey-alert ednasurvey-alert-success">' +
                            '<p><strong>' + (i18n.submitSuccess || 'Your survey data has been submitted successfully!') + '</strong></p>' +
                            '<p style="margin-top:0.75em;"><a href="' + escapeAttr(response.data.redirect_url) + '" class="button button-primary">' +
                            (i18n.backToDashboard || 'Back to Dashboard') + '</a></p>' +
                            '</div>';
                        $messages.html(html);

                        if (response.data.photo_warnings && response.data.photo_warnings.length > 0) {
                            var warnings = '<div class="ednasurvey-alert ednasurvey-alert-warning"><ul>';
                            response.data.photo_warnings.forEach(function(w) {
                                warnings += '<li>' + escapeHtml(w) + '</li>';
                            });
                            warnings += '</ul></div>';
                            $messages.append(warnings);
                        }

                        $('html, body').animate({ scrollTop: $messages.offset().top - 50 }, 300);
                    } else {
                        var msgs = (response && response.data && response.data.messages)
                            ? response.data.messages
                            : [i18n.errorOccurred || 'An error occurred.'];
                        showErrors(msgs);
                        $btn.prop('disabled', false).text(btnLabel);
                    }
                },
                error: function() {
                    showErrors([i18n.serverError || 'Server error. Please try again.']);
                    $btn.prop('disabled', false).text(btnLabel);
                }
            });
        });
    }

    // ── ID field auto-sync (waterfilter/airfilter/container) ───────
    // Each ID field defaults to "<sample_id>-N" and keeps syncing with the
    // Sample ID until the user manually edits it, which detaches the sync.
    function initFilterIdSync() {
        var $sampleId = $('#sample_id');
        var $filters  = $('.ednasurvey-filter-id');
        if (!$sampleId.length || !$filters.length) return;

        function syncFilters() {
            var sid = $sampleId.val();
            $filters.each(function() {
                var $f = $(this);
                if ($f.data('synced')) {
                    var seq = $f.attr('data-filter-seq');
                    $f.val(sid ? sid + '-' + seq : '');
                }
            });
        }

        // On load: a field that already has a value (e.g. copy/resubmit or an
        // admin default) is treated as detached; an empty one stays synced.
        $filters.each(function() {
            var $f = $(this);
            $f.data('synced', $f.val() === '');
        });

        // Manual edit detaches the field from the Sample ID.
        $filters.on('input', function() {
            $(this).data('synced', false);
        });

        $sampleId.on('input', syncFilters);

        // Populate initial values from the current Sample ID.
        syncFilters();
    }

    function showErrors(messages) {
        var $el = $('#ednasurvey-submission-messages');
        var html = '<div class="ednasurvey-alert ednasurvey-alert-error"><ul>';
        messages.forEach(function(msg) {
            html += '<li>' + escapeHtml(msg) + '</li>';
        });
        html += '</ul></div>';
        $el.html(html);
        $('html, body').animate({ scrollTop: $el.offset().top - 50 }, 300);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text || ''));
        return div.innerHTML;
    }

    function escapeAttr(text) {
        return (text || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    $(document).ready(function() {
        initMap();
        initPhotoUpload();
        initFormSubmission();
        initFilterIdSync();
    });
})(jQuery);
