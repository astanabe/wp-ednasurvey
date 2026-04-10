(function($) {
    'use strict';

    var map, marker;
    var i18n = (window.ednasurveyAjax && window.ednasurveyAjax.i18n) ? window.ednasurveyAjax.i18n : {};

    function initMap() {
        if (!ednasurveyFormConfig.hasLocation) return;

        var mapEl = document.getElementById('ednasurvey-map');
        if (!mapEl) return;

        var settings = window.ednasurveyMap || {};
        var centerLat = settings.centerLat || 35.6762;
        var centerLng = settings.centerLng || 139.6503;
        var zoom = settings.defaultZoom || 5;

        map = L.map('ednasurvey-map').setView([centerLat, centerLng], zoom);

        L.tileLayer(settings.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: settings.attribution || '',
            maxZoom: 18
        }).addTo(map);

        // If copy data has coordinates, set marker
        if (ednasurveyFormConfig.copyLat && ednasurveyFormConfig.copyLng) {
            setMarker(ednasurveyFormConfig.copyLat, ednasurveyFormConfig.copyLng);
            map.setView([ednasurveyFormConfig.copyLat, ednasurveyFormConfig.copyLng], 15);
        } else {
            // Try to get current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    map.setView([pos.coords.latitude, pos.coords.longitude], 15);
                }, function() {
                    // Geolocation failed, keep default view
                });
            }
        }

        // Click to set pin
        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
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

    // Photo preview
    function initPhotoPreview() {
        $('#photos').on('change', function() {
            var preview = $('#ednasurvey-photo-preview');
            preview.empty();

            var files = this.files;
            var limit = ednasurveyFormConfig.photoLimit || 10;

            if (files.length > limit) {
                alert(i18n.photoLimit || 'Too many photos selected.');
                this.value = '';
                return;
            }

            for (var j = 0; j < files.length; j++) {
                (function(file) {
                    if (file.type.match(/image\/(jpeg|heic|heif)/)) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            preview.append('<img src="' + e.target.result + '" alt="Preview">');
                        };
                        reader.readAsDataURL(file);
                    }
                })(files[j]);
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

            // Client-side env_local conflict check
            var conflictErrors = checkEnvLocalConflicts();
            if (conflictErrors.length > 0) {
                showErrors(conflictErrors);
                return;
            }

            // Build confirmation table from form fields
            var rows = '';
            $form.find('.ednasurvey-fieldset').each(function() {
                var legend = $(this).find('legend').text();
                $(this).find('.ednasurvey-field-row').each(function() {
                    var label = $(this).find('label').first().clone().children('.required').remove().end().text().trim();
                    var $input = $(this).find('input, select, textarea').first();
                    var val = '';

                    if ($input.is('select')) {
                        val = $input.find('option:selected').text().trim();
                        if (val === (i18n.selectPlaceholder || '-- Select --')) val = '';
                    } else if ($input.is('input[type="file"]')) {
                        var files = $input[0].files;
                        if (files && files.length > 0) {
                            var names = [];
                            for (var fi = 0; fi < files.length; fi++) names.push(files[fi].name);
                            val = names.join(', ');
                        }
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
            $btn.prop('disabled', true).text(i18n.submitting || 'Submitting...');
            $messages.empty();

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
        initPhotoPreview();
        initFormSubmission();
    });
})(jQuery);
