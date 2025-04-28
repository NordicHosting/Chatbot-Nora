/**
 * OpenAI Chat Frontend JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Cache DOM elements
        const $container = $('.chatbot-nora-container');
        const $messages = $container.find('.chatbot-nora-messages');
        const $form = $container.find('.chatbot-nora-form');
        const $input = $container.find('.chatbot-nora-input');
        const $submit = $container.find('.chatbot-nora-submit');
        const $end = $container.find('.chatbot-nora-end');
        const $toggle = $('.chatbot-nora-toggle');
        const $header = $('.chatbot-nora-header');
        let thinkingMessage = null;

        // Check if required elements exist
        if (!$container.length || !$messages.length || !$form.length || !$input.length || !$submit.length || !$end.length || !$toggle.length || !$header.length) {
            return;
        }

        // Load chat state from localStorage
        const chatState = JSON.parse(localStorage.getItem('openaiChatState') || '{"isOpen":false,"messages":[]}');
        const userInfo = JSON.parse(localStorage.getItem('openaiChatUserInfo') || '{"name":"","email":""}');
        
        // Set initial state
        if (chatState.isOpen) {
            $container.removeClass('minimized');
            updateToggleIcon();
        }
        
        // Hide chat form initially
        $form.hide();
        
        // Show welcome message if user info is not set
        if (!userInfo.name) {
            showWelcomeMessage();
        } else {
            // Load messages if user is authenticated
            if (chatState.messages.length) {
                const fragment = document.createDocumentFragment();
                chatState.messages.forEach(message => {
                    const $message = createMessageElement(message.type, message.content);
                    fragment.appendChild($message[0]);
                });
                $messages.append(fragment);
            }
            // Show chat form for authenticated users
            $form.show();
        }

        // Show welcome message with user info form
        function showWelcomeMessage() {
            const welcomeHtml = `
                <div class="chatbot-nora-welcome">
                    <h3>${openaiChat.i18n.welcomeTitle}</h3>
                    <p>${openaiChat.i18n.welcomeMessage}</p>
                    <form class="chatbot-nora-user-form">
                        <div class="chatbot-nora-form-group">
                            <label for="user-name">${openaiChat.i18n.nameLabel}</label>
                            <input type="text" id="user-name" required>
                            <div class="chatbot-nora-error" id="name-error"></div>
                        </div>
                        <div class="chatbot-nora-form-group">
                            <label for="user-email">${openaiChat.i18n.emailLabel}</label>
                            <input type="email" id="user-email">
                            <div class="chatbot-nora-error" id="email-error"></div>
                        </div>
                        <button type="submit" class="chatbot-nora-start">${openaiChat.i18n.startChat}</button>
                    </form>
                </div>
            `;
            $messages.html(welcomeHtml);

            // Handle user info form submission
            $('.chatbot-nora-user-form').on('submit', function(e) {
                e.preventDefault();
                
                const name = $('#user-name').val().trim();
                const email = $('#user-email').val().trim();
                let isValid = true;

                // Validate name
                if (!name) {
                    $('#name-error').text(openaiChat.i18n.nameRequired);
                    isValid = false;
                } else {
                    $('#name-error').text('');
                }

                // Validate email if provided
                if (email && !isValidEmail(email)) {
                    $('#email-error').text(openaiChat.i18n.invalidEmail);
                    isValid = false;
                } else {
                    $('#email-error').text('');
                }

                if (isValid) {
                    // Save user info
                    localStorage.setItem('openaiChatUserInfo', JSON.stringify({ name, email }));
                    
                    // Show initial assistant message
                    $messages.html('');
                    addMessage('assistant', openaiChat.i18n.initialMessage.replace('%s', name));
                    
                    // Enable chat form
                    $form.show();
                    $input.focus();
                }
            });
        }

        // Email validation helper
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Toggle chat window
        function toggleChat(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            $container.toggleClass('minimized');
            updateToggleIcon();
            saveChatState();
            
            if (!$container.hasClass('minimized')) {
                setTimeout(() => $input.focus(), 300);
            }
        }

        // Update toggle icon
        function updateToggleIcon() {
            const $icon = $toggle.find('.dashicons');
            if ($container.hasClass('minimized')) {
                $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            } else {
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            }
        }

        // Add click handlers
        $toggle.on('click', toggleChat);
        $header.on('click', function(e) {
            if (!$(e.target).closest('.chatbot-nora-toggle').length) {
                toggleChat(e);
            }
        });

        // Add click handler for minimized container
        $container.on('click', function(e) {
            if ($container.hasClass('minimized')) {
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
                    message: message,
                    user_info: JSON.stringify(JSON.parse(localStorage.getItem('openaiChatUserInfo')))
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

        // End chat
        function endChat() {
            // First minimize the chat
            $container.addClass('minimized');
            updateToggleIcon();
            
            // Then clear chat state
            localStorage.removeItem('openaiChatState');
            localStorage.removeItem('openaiChatUserInfo');
            
            // Reset chat interface
            $messages.html('');
            $form.hide();
            showWelcomeMessage();
            
            // Save the minimized state
            saveChatState();
        }

        // Add click handler for end button
        let isConfirmingEnd = false;
        $end.html('×').attr('data-tooltip', 'Avslutt chat').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!isConfirmingEnd) {
                // First click - show confirmation state
                isConfirmingEnd = true;
                $end.html('Avslutt chat?').addClass('confirming').attr('data-tooltip', 'Avslutt chat');
                $form.addClass('confirming');
                setTimeout(() => {
                    isConfirmingEnd = false;
                    $end.html('×').removeClass('confirming').attr('data-tooltip', 'Avslutt chat');
                    $form.removeClass('confirming');
                }, 3000); // Reset after 3 seconds
            } else {
                // Second click - actually end the chat
                endChat();
            }
        });

        /**
         * Create a message element
         * @param {string} type - Message type (user or assistant)
         * @param {string} content - Message content
         * @returns {jQuery} The message element
         */
        function createMessageElement(type, content) {
            return $('<div>')
                .addClass('chatbot-nora-message')
                .addClass(`chatbot-nora-message-${type}`)
                .html(content);
        }

        /**
         * Add a message to the chat
         * @param {string} type - Message type (user or assistant)
         * @param {string} content - Message content
         * @param {boolean} isTemporary - Whether the message is temporary
         * @returns {jQuery} The message element
         */
        function addMessage(type, content, isTemporary = false) {
            const $message = createMessageElement(type, content);
            $messages.append($message);
            $messages.scrollTop($messages[0].scrollHeight);
            return isTemporary ? $message : null;
        }

        /**
         * Save chat state to localStorage
         */
        function saveChatState() {
            const messages = [];
            $messages.find('.chatbot-nora-message').each(function() {
                const $message = $(this);
                messages.push({
                    type: $message.hasClass('chatbot-nora-message-user') ? 'user' : 'assistant',
                    content: $message.html()
                });
            });
            
            localStorage.setItem('openaiChatState', JSON.stringify({
                isOpen: !$container.hasClass('minimized'),
                messages: messages
            }));
        }
    });
})(jQuery); 