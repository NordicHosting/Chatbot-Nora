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
            return;
        }

        // Check if API key is configured
        $api_key = get_option('openai_chat_api_key');
        if (empty($api_key)) {
            return;
        }

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
                    'error' => __('An error occurred. Please try again.', 'openai-chat'),
                    'emptyMessage' => __('Please enter a message.', 'openai-chat'),
                    'sending' => __('Sending...', 'openai-chat'),
                    'thinking' => __('AI is thinking...', 'openai-chat'),
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
        <div class="openai-chat-container minimized" 
             style="--primary-color: <?php echo esc_attr($styling['primary_color'] ?? '#0073aa'); ?>;
                    --secondary-color: <?php echo esc_attr($styling['secondary_color'] ?? '#23282d'); ?>;
                    --border-radius: <?php echo esc_attr($styling['border_radius'] ?? '4px'); ?>;">
            <div class="openai-chat-header">
                <h3 class="openai-chat-title"><?php esc_html_e('Chat with Nora', 'openai-chat'); ?></h3>
                <button type="button" class="openai-chat-toggle" aria-label="<?php esc_attr_e('Toggle chat', 'openai-chat'); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
            </div>
            <div class="openai-chat-messages"></div>
            <form class="openai-chat-form">
                <input type="text" 
                       class="openai-chat-input" 
                       placeholder="<?php esc_attr_e('Spør om hva som helst', 'openai-chat'); ?>"
                       aria-label="<?php esc_attr_e('Chat message', 'openai-chat'); ?>"
                       required>
                <button type="submit" class="openai-chat-submit">
                    <?php esc_html_e('Send', 'openai-chat'); ?>
                </button>
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
                'You are Nora, a helpful assistant. Respond in the same language as the user message. If you cannot determine the language, respond in %s. Keep responses concise and helpful.%s',
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
                error_log('OpenAI Chat: API request failed - ' . $response->get_error_message());
                wp_send_json_error(__('Failed to connect to OpenAI API', 'openai-chat'));
                return;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $error_message = wp_remote_retrieve_response_message($response);
                error_log('OpenAI Chat: API request failed with status ' . $response_code . ' - ' . $error_message);
                wp_send_json_error(__('OpenAI API request failed', 'openai-chat'));
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($body['choices'][0]['message']['content'])) {
                error_log('OpenAI Chat: Invalid API response format');
                wp_send_json_error(__('Invalid response from OpenAI API', 'openai-chat'));
                return;
            }

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