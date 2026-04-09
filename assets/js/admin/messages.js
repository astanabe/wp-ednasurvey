(function($) {
    'use strict';

    $(document).ready(function() {
        // Admin chat form submission
        $('#ednasurvey-admin-chat-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $input = $form.find('textarea[name="message"]');
            var message = $input.val().trim();
            if (!message) return;

            var $btn = $form.find('button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: ednasurveyAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ednasurvey_send_message',
                    nonce: ednasurveyAdmin.nonce,
                    conversation_user_id: ednasurveyChat.conversationUserId,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        $input.val('');
                        pollAdminMessages();
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Poll for new messages on admin chat page
        if (typeof ednasurveyChat !== 'undefined') {
            setInterval(pollAdminMessages, 15000);
        }
    });

    function pollAdminMessages() {
        if (typeof ednasurveyChat === 'undefined') return;

        $.ajax({
            url: ednasurveyAdmin.restUrl + 'messages/' + ednasurveyChat.conversationUserId + '?after=' + ednasurveyChat.lastMessageId,
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', ednasurveyAdmin.restNonce);
            },
            success: function(response) {
                if (response.messages && response.messages.length > 0) {
                    $('.ednasurvey-chat-empty').remove();

                    response.messages.forEach(function(msg) {
                        var isSent = parseInt(msg.sender_id) === ednasurveyChat.currentUserId;
                        var style = isSent
                            ? 'background: #e8f4fd; text-align: right;'
                            : 'background: #f0f0f0;';
                        var html = '<div class="ednasurvey-chat-message" style="margin-bottom: 0.75em; padding: 0.5em; border-radius: 4px; ' + style + '">' +
                            '<div class="ednasurvey-chat-meta" style="font-size: 0.85em; color: #666;">' +
                            '<strong>' + escapeHtml(msg.sender_name) + '</strong> &mdash; ' +
                            '<time>' + escapeHtml(msg.created_at) + '</time>' +
                            '</div>' +
                            '<div class="ednasurvey-chat-text">' + escapeHtml(msg.message).replace(/\n/g, '<br>') + '</div>' +
                            '</div>';
                        $('#ednasurvey-chat-messages').append(html);
                        ednasurveyChat.lastMessageId = parseInt(msg.id);
                    });

                    var container = document.getElementById('ednasurvey-chat-messages');
                    if (container) container.scrollTop = container.scrollHeight;
                }
            }
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text || ''));
        return div.innerHTML;
    }
})(jQuery);
