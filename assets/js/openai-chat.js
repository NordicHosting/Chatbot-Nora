/**
 * OpenAI Chat Frontend JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Cache DOM elements
        const $container = $('.openai-chat-container');
        const $messages = $container.find('.openai-chat-messages');
        const $form = $container.find('.openai-chat-form');
        const $input = $container.find('.openai-chat-input');
        const $submit = $container.find('.openai-chat-submit');
        const $toggle = $('.openai-chat-toggle');
        const $header = $('.openai-chat-header');
        let thinkingMessage = null;

        // Check if required elements exist
        if (!$container.length || !$messages.length || !$form.length || !$input.length || !$submit.length || !$toggle.length || !$header.length) {
            return;
        }

        // Load chat state from localStorage
        const chatState = JSON.parse(localStorage.getItem('openaiChatState') || '{"isOpen":false,"messages":[]}');
        
        // Set initial state
        if (chatState.isOpen) {
            $container.removeClass('minimized');
            updateToggleIcon();
        }
        
        // Load messages
        if (chatState.messages.length) {
            const fragment = document.createDocumentFragment();
            chatState.messages.forEach(message => {
                const $message = createMessageElement(message.type, message.content);
                fragment.appendChild($message[0]);
            });
            $messages.append(fragment);
        }

        // Toggle chat window
        function toggleChat(e) {
            if (e) {
                e.preventDefault();
            }
            $container.toggleClass('minimized');
            updateToggleIcon();
            saveChatState();
            
            if (!$container.hasClass('minimized')) {
                setTimeout(() => $input.focus(), 300);
            }
        }

        // Update toggle icon based on chat state
        function updateToggleIcon() {
            const $icon = $toggle.find('.dashicons');
            $icon.toggleClass('dashicons-arrow-down-alt2', !$container.hasClass('minimized'))
                 .toggleClass('dashicons-arrow-up-alt2', $container.hasClass('minimized'));
        }

        // Add click handlers
        $toggle.on('click', toggleChat);
        $header.on('click', function(e) {
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
            thinkingMessage = addMessage('assistant', openaiChat.i18n.thinking, true);

            // Send message to server
            $.ajax({
                url: openaiChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'openai_chat_send_message',
                    nonce: openaiChat.nonce,
                    message: message
                },
                beforeSend: function() {
                    $submit.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        thinkingMessage.remove();
                        addMessage('assistant', response.data.response);
                        saveChatState();
                    } else {
                        thinkingMessage.remove();
                        addMessage('assistant', response.data || openaiChat.i18n.error);
                    }
                },
                error: function(xhr, status, error) {
                    thinkingMessage.remove();
                    addMessage('assistant', openaiChat.i18n.error);
                },
                complete: function() {
                    $submit.prop('disabled', false);
                    $input.prop('disabled', false);
                    $input.focus();
                    $messages.scrollTop($messages[0].scrollHeight);
                }
            });
        });

        /**
         * Create a message element
         * @param {string} type - Message type (user or assistant)
         * @param {string} content - Message content
         * @returns {jQuery} The message element
         */
        function createMessageElement(type, content) {
            return $('<div>')
                .addClass('openai-chat-message')
                .addClass(`openai-chat-message-${type}`)
                .html(content);
        }

        /**
         * Add a message to the chat
         * @param {string} type - Message type (user or assistant)
         * @param {string} content - Message content
         * @param {boolean} isTemporary - Whether the message is temporary
         */
        function addMessage(type, content, isTemporary = false) {
            const $message = createMessageElement(type, content);
            $messages.append($message);
            
            // Scroll to bottom with smooth animation
            $messages.animate({
                scrollTop: $messages[0].scrollHeight
            }, 300);

            if (!isTemporary) {
                saveChatState();
            }

            return $message;
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

        // Add scroll event listener to save scroll position
        $messages.on('scroll', function() {
            const isAtBottom = $messages[0].scrollHeight - $messages.scrollTop() === $messages.outerHeight();
            if (isAtBottom) {
                $messages.data('auto-scroll', true);
            } else {
                $messages.data('auto-scroll', false);
            }
        });

        // Initial scroll to bottom
        $messages.scrollTop($messages[0].scrollHeight);
    });
})(jQuery); 