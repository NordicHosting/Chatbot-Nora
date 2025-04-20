/**
 * OpenAI Chat Frontend JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('OpenAI Chat: Initializing...');
        
        const $container = $('.openai-chat-container');
        const $messages = $container.find('.openai-chat-messages');
        const $form = $container.find('.openai-chat-form');
        const $input = $container.find('.openai-chat-input');
        const $submit = $container.find('.openai-chat-submit');
        const $toggle = $('.openai-chat-toggle');

        // Check if required elements exist
        if (!$container.length || !$messages.length || !$form.length || !$input.length || !$submit.length || !$toggle.length) {
            console.error('OpenAI Chat: Required elements not found');
            return;
        }

        console.log('OpenAI Chat: Elements found, setting up event handlers');

        // Toggle chat window
        $toggle.on('click', function() {
            $container.toggleClass('minimized');
            const isMinimized = $container.hasClass('minimized');
            $(this).find('.dashicons').toggleClass('dashicons-arrow-up-alt2 dashicons-arrow-down-alt2');
        });

        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            console.log('OpenAI Chat: Form submitted');

            const message = $input.val().trim();
            if (!message) {
                console.log('OpenAI Chat: Empty message');
                return;
            }

            // Disable form while processing
            $input.prop('disabled', true);
            $submit.prop('disabled', true);

            // Add user message to chat
            addMessage('user', message);
            $input.val('');

            // Scroll to bottom
            $messages.scrollTop($messages[0].scrollHeight);

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
                .addClass('openai-chat-message')
                .addClass(`openai-chat-message-${type}`)
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
                    $input.prop('disabled', false);
                    $input.focus();
                    $messages.scrollTop($messages[0].scrollHeight);
                }
            });
        }

        // Auto-focus input when chat is opened
        $toggle.on('click', function() {
            if (!$container.hasClass('minimized')) {
                setTimeout(() => {
                    $input.focus();
                }, 300);
            }
        });
    });
})(jQuery); 