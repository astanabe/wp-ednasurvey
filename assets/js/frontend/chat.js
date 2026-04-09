(function($) {
    'use strict';

    var pollInterval;

    function initChat() {
        if (typeof ednasurveyChat === 'undefined') return;

        scrollToBottom();

        // Send message
        $('#ednasurvey-chat-form, #ednasurvey-admin-chat-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $input = $form.find('textarea[name="message"]');
            var message = $input.val().trim();
            if (!message) return;

            var $btn = $form.find('button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: ednasurveyAjax ? ednasurveyAjax.ajaxUrl : (ednasurveyAdmin ? ednasurveyAdmin.ajaxUrl : ''),
                type: 'POST',
                data: {
                    action: 'ednasurvey_send_message',
                    nonce: ednasurveyAjax ? ednasurveyAjax.nonce : (ednasurveyAdmin ? ednasurveyAdmin.nonce : ''),
                    conversation_user_id: ednasurveyChat.conversationUserId,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        $input.val('');
                        // Immediately poll for new messages
                        pollMessages();
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Start polling
        pollInterval = setInterval(pollMessages, 15000);
    }

    function pollMessages() {
        var restUrl = ednasurveyAjax ? ednasurveyAjax.restUrl : (ednasurveyAdmin ? ednasurveyAdmin.restUrl : '');
        var restNonce = ednasurveyAjax ? ednasurveyAjax.restNonce : (ednasurveyAdmin ? ednasurveyAdmin.restNonce : '');

        $.ajax({
            url: restUrl + 'messages/' + ednasurveyChat.conversationUserId + '?after=' + ednasurveyChat.lastMessageId,
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', restNonce);
            },
            success: function(response) {
                if (response.messages && response.messages.length > 0) {
                    // Remove empty message if present
                    $('.ednasurvey-chat-empty').remove();

                    response.messages.forEach(function(msg) {
                        var isSent = parseInt(msg.sender_id) === ednasurveyChat.currentUserId;
                        var html = '<div class="ednasurvey-chat-message ' + (isSent ? 'sent' : 'received') + '">' +
                            '<div class="ednasurvey-chat-meta">' +
                            '<strong>' + escapeHtml(msg.sender_name) + '</strong> ' +
                            '<time>' + escapeHtml(msg.created_at) + '</time>' +
                            '</div>' +
                            '<div class="ednasurvey-chat-text">' + escapeHtml(msg.message).replace(/\n/g, '<br>') + '</div>' +
                            '</div>';
                        $('#ednasurvey-chat-messages').append(html);
                        ednasurveyChat.lastMessageId = parseInt(msg.id);
                    });

                    scrollToBottom();
                }
            }
        });
    }

    function scrollToBottom() {
        var container = document.getElementById('ednasurvey-chat-messages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text || ''));
        return div.innerHTML;
    }

    $(document).ready(function() {
        initChat();
    });
})(jQuery);
