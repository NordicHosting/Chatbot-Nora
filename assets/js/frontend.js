/**
 * OpenAI Chat Frontend JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('OpenAI Chat: Initializing...');
        
        const $container = $('.chatbot-nora-container');
        const $messages = $container.find('.chatbot-nora-messages');
        const $form = $container.find('.chatbot-nora-form');
        const $input = $('#chatbot-nora-textarea');
        const $submit = $container.find('.chatbot-nora-send');

        // Check if required elements exist
        if (!$container.length || !$messages.length || !$form.length || !$input.length || !$submit.length) {
            console.error('OpenAI Chat: Required elements not found');
            return;
        }

        console.log('OpenAI Chat: Elements found, setting up event handlers');

        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            console.log('OpenAI Chat: Form submitted');

            const message = $input.val().trim();
            console.log('OpenAI Chat: Message value:', message);
            
            if (!message) {
                console.log('OpenAI Chat: Empty message');
                return;
            }

            // Add user message to chat
            addMessage('user', message);
            $input.val('');

            // Send message to server
            sendMessage(message);
        });

        /**
         * Add a message to the chat
         * @param {string} type - Message type (user or assistant)
         * @param {string} content - Message content
         */
        function addMessage(type, content) {
            console.log(`OpenAI Chat: Adding ${type} message`);
            const $message = $('<div>')
                .addClass('chatbot-nora-message')
                .addClass(`chatbot-nora-message-${type}`)
                .text(content);
            $messages.append($message);
            $messages.scrollTop($messages[0].scrollHeight);
        }

        /**
         * Send message to server
         * @param {string} message - Message to send
         */
        function sendMessage(message) {
            console.log('OpenAI Chat: Sending message to server');
            
            $.ajax({
                url: openaiChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'openai_chat_send_message',
                    nonce: openaiChat.nonce,
                    message: message
                },
                beforeSend: function() {
                    console.log('OpenAI Chat: AJAX request starting');
                    $submit.prop('disabled', true);
                },
                success: function(response) {
                    console.log('OpenAI Chat: AJAX response received', response);
                    if (response.success) {
                        addMessage('assistant', response.data.response);
                    } else {
                        console.error('OpenAI Chat: Error response', response);
                        addMessage('assistant', openaiChat.i18n.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('OpenAI Chat: AJAX error', error);
                    addMessage('assistant', openaiChat.i18n.error);
                },
                complete: function() {
                    console.log('OpenAI Chat: AJAX request complete');
                    $submit.prop('disabled', false);
                }
            });
        }
    });
})(jQuery); 