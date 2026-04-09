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

    // Form submission
    function initFormSubmission() {
        $('#ednasurvey-online-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('.ednasurvey-submit-btn');
            var $messages = $('#ednasurvey-submission-messages');

            var btnLabel = $btn.text();
            $btn.prop('disabled', true).text(i18n.submitting || 'Submitting...');
            $messages.empty();

            var formData = new FormData(this);

            $.ajax({
                url: ednasurveyAjax.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        // Hide the form and show success message prominently
                        $form.hide();
                        // Also hide the warning about copy_from if present
                        $form.prev('.ednasurvey-alert-warning').hide();

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
                error: function(xhr) {
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
