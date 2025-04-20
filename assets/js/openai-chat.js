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

        // Load chat state from localStorage
        const chatState = JSON.parse(localStorage.getItem('openaiChatState') || '{"isOpen":false,"messages":[]}');
        
        // Set initial state
        if (chatState.isOpen) {
            $container.removeClass('minimized');
        }
        
        // Load messages
        chatState.messages.forEach(message => {
            addMessage(message.type, message.content);
        });

        // Toggle chat window
        $toggle.on('click', function() {
            $container.toggleClass('minimized');
            // Save chat state
            saveChatState();
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

        // Auto-focus input when chat is opened
        $toggle.on('click', function() {
            if (!$container.hasClass('minimized')) {
                setTimeout(() => {
                    $input.focus();
                }, 300);
            }
        });

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

    // Initialize chat
    function initChat() {
        // Get chat container and elements
        const chatContainer = document.querySelector('.openai-chat-container');
        const chatMessages = chatContainer.querySelector('.openai-chat-messages');
        const chatForm = chatContainer.querySelector('.openai-chat-form');
        const chatInput = chatContainer.querySelector('.openai-chat-input');
        const chatToggle = chatContainer.querySelector('.openai-chat-toggle');

        // Load chat state from localStorage
        const chatState = JSON.parse(localStorage.getItem('openaiChatState') || '{"isOpen":false,"messages":[]}');
        
        // Set initial state
        if (chatState.isOpen) {
            chatContainer.classList.remove('minimized');
        }
        
        // Load messages
        chatState.messages.forEach(message => {
            addMessage(message.type, message.content);
        });

        // Toggle chat window
        chatToggle.addEventListener('click', function() {
            chatContainer.classList.toggle('minimized');
            // Save chat state
            saveChatState();
        });

        // Handle form submission
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const message = chatInput.value.trim();
            if (!message) {
                return;
            }

            // Add user message
            addMessage('user', message);
            
            // Clear input
            chatInput.value = '';

            // Send message to server
            jQuery.ajax({
                url: openaiChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'openai_chat_send_message',
                    message: message,
                    nonce: openaiChat.nonce
                },
                success: function(response) {
                    if (response.success) {
                        addMessage('assistant', response.data.response);
                        saveChatState();
                    } else {
                        addMessage('error', response.data || openaiChat.i18n.error);
                    }
                },
                error: function() {
                    addMessage('error', openaiChat.i18n.error);
                }
            });
        });

        // Add message to chat
        function addMessage(type, content) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `openai-chat-message openai-chat-message-${type}`;
            messageDiv.innerHTML = content;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;

            // Save chat state
            saveChatState();
        }

        // Save chat state to localStorage
        function saveChatState() {
            const messages = Array.from(chatMessages.querySelectorAll('.openai-chat-message')).map(message => ({
                type: message.classList.contains('openai-chat-message-user') ? 'user' :
                      message.classList.contains('openai-chat-message-assistant') ? 'assistant' : 'error',
                content: message.innerHTML
            }));

            localStorage.setItem('openaiChatState', JSON.stringify({
                isOpen: !chatContainer.classList.contains('minimized'),
                messages: messages
            }));
        }
    }

    // Initialize chat when DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChat);
    } else {
        initChat();
    }
})(jQuery); 