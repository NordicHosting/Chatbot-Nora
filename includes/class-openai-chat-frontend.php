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
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check if API key is configured
        $api_key = get_option('openai_chat_api_key');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
            return;
        }

        // Get message
        $message = sanitize_text_field($_POST['message']);
        if (empty($message)) {
            wp_send_json_error('Empty message');
            return;
        }

        // Detect language of the question
        $language = $this->detect_language($message);

        // Get FAQ knowledge
        $faq_knowledge = $this->get_faq_knowledge();

        // Prepare system message based on language
        $system_message = $this->get_system_message($language, $faq_knowledge);

        // Send to OpenAI API
        $response = $this->send_to_openai($message, $system_message, $language);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        wp_send_json_success(array('response' => $response));
    }

    /**
     * Detect language of the message
     */
    private function detect_language($message) {
        // Simple language detection based on common words
        $english_words = array('the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i');
        $norwegian_words = array('og', 'er', 'det', 'som', 'på', 'til', 'for', 'med', 'har', 'jeg');

        $message = strtolower($message);
        $english_count = 0;
        $norwegian_count = 0;

        foreach ($english_words as $word) {
            if (strpos($message, $word) !== false) {
                $english_count++;
            }
        }

        foreach ($norwegian_words as $word) {
            if (strpos($message, $word) !== false) {
                $norwegian_count++;
            }
        }

        return $english_count > $norwegian_count ? 'en' : 'no';
    }

    /**
     * Get system message based on language
     */
    private function get_system_message($language, $faq_knowledge) {
        if ($language === 'en') {
            return "You are a helpful assistant. Use the following FAQ knowledge to answer questions. If the answer is in Norwegian, translate it to English before responding:\n\n" . $faq_knowledge;
        } else {
            return "Du er en hjelpsom assistent. Bruk følgende FAQ-kunnskap til å svare på spørsmål:\n\n" . $faq_knowledge;
        }
    }

    /**
     * Send message to OpenAI API with language consideration
     */
    private function send_to_openai($message, $system_message, $language) {
        $api_key = get_option('openai_chat_api_key');
        $model = get_option('openai_chat_model', 'gpt-3.5-turbo');

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'system', 'content' => $system_message),
                    array('role' => 'user', 'content' => $message)
                ),
                'temperature' => 0.7,
                'max_tokens' => 1000
            )),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('openai_error', $body['error']['message']);
        }

        return $body['choices'][0]['message']['content'];
    }
} 