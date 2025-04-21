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
        const $header = $('.openai-chat-header');

        // Check if required elements exist
        if (!$container.length || !$messages.length || !$form.length || !$input.length || !$submit.length || !$toggle.length || !$header.length) {
            console.error('OpenAI Chat: Required elements not found');
            return;
        }

        console.log('OpenAI Chat: Elements found, setting up event handlers');

        // Load chat state from localStorage
        const chatState = JSON.parse(localStorage.getItem('openaiChatState') || '{"isOpen":false,"messages":[]}');
        
        // Set initial state
        if (chatState.isOpen) {
            $container.removeClass('minimized');
            updateToggleIcon();
        }
        
        // Load messages
        chatState.messages.forEach(message => {
            addMessage(message.type, message.content);
        });

        // Toggle chat window
        function toggleChat(e) {
            if (e) {
                e.preventDefault();
            }
            console.log('OpenAI Chat: Toggle clicked');
            console.log('Current state:', $container.hasClass('minimized') ? 'minimized' : 'expanded');
            $container.toggleClass('minimized');
            console.log('New state:', $container.hasClass('minimized') ? 'minimized' : 'expanded');
            
            // Update toggle icon
            updateToggleIcon();
            
            // Save chat state
            saveChatState();
            
            // Focus input if expanded
            if (!$container.hasClass('minimized')) {
                setTimeout(() => {
                    $input.focus();
                }, 300);
            }
        }

        // Update toggle icon based on chat state
        function updateToggleIcon() {
            const $icon = $toggle.find('.dashicons');
            if ($container.hasClass('minimized')) {
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            } else {
                $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            }
        }

        // Add click handlers
        $toggle.on('click', toggleChat);
        $header.on('click', function(e) {
            // Only toggle if clicking on header, not on toggle button
            if (!$(e.target).closest('.openai-chat-toggle').length) {
                toggleChat(e);
            }
        });

        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const message = $input.val().trim();
            if (!message) {
                return;
            }

            // Disable form while processing
            $input.prop('disabled', true);
            $submit.prop('disabled', true);

            // Add user message
            addMessage('user', message);
            $input.val('');

            // Show thinking message
            const thinkingMessage = addMessage('assistant', openaiChat.i18n.thinking, true);

            // Send message to server
            sendMessage(message);
        });

        /**
         * Add a message to the chat
         * @param {string} type - Message type (user or assistant)
         * @param {string} content - Message content
         */
        function addMessage(type, content, isTemporary = false) {
            console.log(`OpenAI Chat: Adding ${type} message`);
            const $message = $('<div>')
                .addClass('openai-chat-message')
                .addClass(`openai-chat-message-${type}`)
                .html(content);
            $messages.append($message);
            $messages.scrollTop($messages[0].scrollHeight);

            // Only save non-temporary messages
            if (!isTemporary) {
                saveChatState();
            }

            return $message;
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
                        // Remove thinking message
                        thinkingMessage.remove();
                        
                        // Add assistant message
                        addMessage('assistant', response.data.response);
                        
                        // Save chat state
                        saveChatState();
                    } else {
                        // Remove thinking message
                        thinkingMessage.remove();
                        
                        console.error('OpenAI Chat: Error response', response);
                        addMessage('assistant', openaiChat.i18n.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('OpenAI Chat: AJAX error', error);
                    // Remove thinking message
                    thinkingMessage.remove();
                    
                    // Show error message
                    addMessage('error', openaiChat.i18n.error);
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

        // Save chat state to localStorage
        function saveChatState() {
            const messages = Array.from($messages.find('.openai-chat-message')).map(message => ({
                type: message.classList.contains('openai-chat-message-user') ? 'user' :
                      message.classList.contains('openai-chat-message-assistant') ? 'assistant' : 'error',
                content: message.innerHTML
            }));

            localStorage.setItem('openaiChatState', JSON.stringify({
                isOpen: !$container.hasClass('minimized'),
                messages: messages
            }));
        }
    });
})(jQuery); 