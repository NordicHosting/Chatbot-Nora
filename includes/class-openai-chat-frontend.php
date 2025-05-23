<?php
/**
 * Frontend functionality class
 *
 * @package OpenAI_Chat
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once OPENAI_CHAT_PLUGIN_DIR . 'includes/class-openai-chat.php';
require_once OPENAI_CHAT_PLUGIN_DIR . 'includes/class-openai-chat-logs.php';

/**
 * Handles frontend functionality
 */
class OpenAI_Chat_Frontend {
    /**
     * Initialize frontend functionality
     */
    public function init(): void {
        // Check if chat is enabled
        if (!get_option('openai_chat_enabled', true)) {
            error_log('OpenAI Chat: Chat is disabled');
            return;
        }

        // Check if API key is configured
        $api_key = get_option('openai_chat_api_key');
        if (empty($api_key)) {
            error_log('OpenAI Chat: API key is not configured');
            return;
        }

        error_log('OpenAI Chat: Initializing frontend...');

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Add chat window to footer
        add_action('wp_footer', array($this, 'render_chat'));

        // Add AJAX handlers
        add_action('wp_ajax_openai_chat_send_message', array($this, 'handle_message'));
        add_action('wp_ajax_nopriv_openai_chat_send_message', array($this, 'handle_message'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets(): void {
        // Only load assets if chat is enabled and API key is configured
        if (!get_option('openai_chat_enabled', true) || empty(get_option('openai_chat_api_key'))) {
            return;
        }

        // Enqueue Dashicons
        wp_enqueue_style('dashicons');

        // Enqueue styles
        wp_enqueue_style(
            'openai-chat',
            plugins_url('assets/css/openai-chat.css', dirname(__FILE__)),
            array(),
            OPENAI_CHAT_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'openai-chat',
            plugins_url('assets/js/openai-chat.js', dirname(__FILE__)),
            array('jquery'),
            OPENAI_CHAT_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'openai-chat',
            'openaiChat',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('openai_chat_nonce'),
                'i18n' => array(
                    'error' => get_locale() === 'nb_NO' ? __('Beklager, det oppstod en feil. Vennligst prøv igjen.', 'openai-chat') : __('An error occurred. Please try again.', 'openai-chat'),
                    'emptyMessage' => get_locale() === 'nb_NO' ? __('Vennligst skriv inn en melding.', 'openai-chat') : __('Please enter a message.', 'openai-chat'),
                    'sending' => get_locale() === 'nb_NO' ? __('Sender...', 'openai-chat') : __('Sending...', 'openai-chat'),
                    'thinking' => get_locale() === 'nb_NO' ? __('Nora tenker...', 'openai-chat') : __('Nora is thinking...', 'openai-chat'),
                    'welcomeTitle' => get_locale() === 'nb_NO' ? __('Hei! Jeg er Nora', 'openai-chat') : __('Hi! I am Nora', 'openai-chat'),
                    'welcomeMessage' => get_locale() === 'nb_NO' ? __('Jeg er en chatbot som fortsatt er under opplæring. Jeg gjør mitt beste for å hjelpe deg, men kan noen ganger gjøre feil. Før vi begynner, trenger jeg litt informasjon fra deg.', 'openai-chat') : __('I am a chatbot that is still in training. I do my best to help you, but I might make mistakes sometimes. Before we start, I need some information from you.', 'openai-chat'),
                    'nameLabel' => get_locale() === 'nb_NO' ? __('Ditt navn', 'openai-chat') : __('Your name', 'openai-chat'),
                    'emailLabel' => get_locale() === 'nb_NO' ? __('Din e-postadresse (valgfritt)', 'openai-chat') : __('Your email (optional)', 'openai-chat'),
                    'startChat' => get_locale() === 'nb_NO' ? __('Start chat', 'openai-chat') : __('Start chat', 'openai-chat'),
                    'nameRequired' => get_locale() === 'nb_NO' ? __('Vennligst skriv inn ditt navn', 'openai-chat') : __('Please enter your name', 'openai-chat'),
                    'invalidEmail' => get_locale() === 'nb_NO' ? __('Vennligst skriv inn en gyldig e-postadresse', 'openai-chat') : __('Please enter a valid email address', 'openai-chat'),
                    'initialMessage' => get_locale() === 'nb_NO' ? __('Hei %s! Hvordan kan jeg hjelpe deg i dag?', 'openai-chat') : __('Hi %s! How can I help you today?', 'openai-chat'),
                    'endChatConfirm' => __('Are you sure you want to end this chat? This will clear the chat history.', 'openai-chat')
                )
            )
        );
    }

    /**
     * Render chat interface
     */
    public function render_chat(): void {
        $styling = get_option('openai_chat_styling', array());
        ?>
        <div class="chatbot-nora-container minimized" 
             style="--primary-color: <?php echo esc_attr($styling['primary_color'] ?? '#0073aa'); ?>;
                    --secondary-color: <?php echo esc_attr($styling['secondary_color'] ?? '#23282d'); ?>;
                    --border-radius: <?php echo esc_attr($styling['border_radius'] ?? '4px'); ?>;">
            <div class="chatbot-nora-header">
                <h3 class="chatbot-nora-title"><?php esc_html_e('Chat med Nora', 'chatbot-nora'); ?></h3>
                <button type="button" class="chatbot-nora-toggle" aria-label="<?php esc_attr_e('Toggle chat', 'chatbot-nora'); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
            </div>
            <div class="chatbot-nora-messages"></div>
            <form class="chatbot-nora-form">
                <input type="text" 
                       class="chatbot-nora-input" 
                       placeholder="<?php esc_attr_e('Spør om hva som helst', 'chatbot-nora'); ?>"
                       aria-label="<?php esc_attr_e('Chat message', 'chatbot-nora'); ?>"
                       required>
                <div class="chatbot-nora-buttons">
                    <button type="submit" class="chatbot-nora-submit">
                        <?php esc_html_e('Send', 'chatbot-nora'); ?>
                    </button>
                    <button type="button" class="chatbot-nora-end" aria-label="<?php esc_attr_e('End chat', 'chatbot-nora'); ?>">
                        <?php esc_html_e('Avslutt', 'chatbot-nora'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Get FAQ knowledge for the AI
     */
    private function get_faq_knowledge(): string {
        $faqs = get_posts(array(
            'post_type' => 'faq',
            'post_status' => 'publish',
            'numberposts' => -1
        ));

        if (empty($faqs)) {
            return '';
        }

        $knowledge = "\n\nHere is some knowledge about the website:\n";
        foreach ($faqs as $faq) {
            $keywords = get_post_meta($faq->ID, '_faq_keywords', true);
            $category = get_post_meta($faq->ID, '_faq_category', true);
            
            $knowledge .= sprintf(
                "\nQuestion: %s\nAnswer: %s\nKeywords: %s\nCategory: %s\n",
                $faq->post_title,
                $faq->post_content,
                $keywords,
                $category
            );
        }

        return $knowledge;
    }

    /**
     * Handle message submission
     */
    public function handle_message(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'openai_chat_nonce')) {
            wp_send_json_error(__('Invalid nonce', 'openai-chat'));
            return;
        }

        // Get message
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        if (empty($message)) {
            wp_send_json_error(__('Message is required', 'openai-chat'));
            return;
        }

        // Get API key
        $api_key = get_option('openai_chat_api_key');
        if (empty($api_key)) {
            wp_send_json_error(__('API key is not configured', 'openai-chat'));
            return;
        }

        try {
            $faq_knowledge = $this->get_faq_knowledge();
            $system_message = sprintf(
                'You are Nora, a helpful assistant for Nordic Hosting. Respond in the same language as the user message. If you cannot determine the language, respond in %s. Keep responses concise and helpful. If you do not know the answer, recommend to contact support directly. %s',
                get_locale(),
                $faq_knowledge
            );

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-3.5-turbo',
                    'messages' => array(
                        array(
                            'role' => 'system',
                            'content' => $system_message
                        ),
                        array(
                            'role' => 'user',
                            'content' => $message
                        )
                    ),
                    'temperature' => 0.7,
                    'max_tokens' => 1000
                )),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                error_log('OpenAI Chat API Error: ' . $response->get_error_message());
                wp_send_json_error(__('Failed to connect to OpenAI API', 'openai-chat'));
                return;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $error_message = wp_remote_retrieve_response_message($response);
                $response_body = wp_remote_retrieve_body($response);
                error_log('OpenAI Chat API Error: HTTP ' . $response_code . ' - ' . $error_message . ' - ' . $response_body);
                wp_send_json_error(__('OpenAI API returned an error', 'openai-chat'));
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($body['choices'][0]['message']['content'])) {
                error_log('OpenAI Chat API Error: Invalid response format - ' . wp_remote_retrieve_body($response));
                wp_send_json_error(__('Invalid response from OpenAI API', 'openai-chat'));
                return;
            }

            // Get session ID from cookie or generate new one
            $session_id = isset($_COOKIE['openai_chat_session']) ? sanitize_text_field($_COOKIE['openai_chat_session']) : OpenAI_Chat::generate_uuid4();
            if (!isset($_COOKIE['openai_chat_session'])) {
                setcookie('openai_chat_session', $session_id, time() + (86400 * 30), '/'); // 30 days
            }

            // Get user info from localStorage
            $user_info = isset($_POST['user_info']) ? $_POST['user_info'] : null;
            if ($user_info) {
                $user_info = json_decode(stripslashes($user_info), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $user_info = json_encode($user_info);
                } else {
                    $user_info = null;
                }
            }

            // Log messages
            require_once OPENAI_CHAT_PLUGIN_DIR . 'includes/class-openai-chat-logs.php';
            $logs = new OpenAI_Chat_Logs();
            $logs->log_message($session_id, 'user', $message, $user_info);
            $logs->log_message($session_id, 'assistant', $body['choices'][0]['message']['content']);

            // Update session activity
            global $wpdb;
            $wpdb->replace(
                $wpdb->prefix . 'openai_chat_sessions',
                array(
                    'session_id' => $session_id,
                    'last_activity' => current_time('mysql')
                ),
                array('%s', '%s')
            );

            wp_send_json_success(array(
                'message' => $message,
                'response' => $body['choices'][0]['message']['content']
            ));
        } catch (Exception $e) {
            error_log('OpenAI Chat: Exception - ' . $e->getMessage());
            wp_send_json_error(__('An unexpected error occurred', 'openai-chat'));
        }
    }
} 