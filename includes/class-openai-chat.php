<?php
/**
 * Main plugin class
 *
 * @package OpenAI_Chat
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class OpenAI_Chat {
    /**
     * Initialize the plugin
     */
    public function init(): void {
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));

        // Initialize admin functionality
        if (is_admin()) {
            $this->init_admin();
        }

        // Initialize frontend functionality
        $this->init_frontend();
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'openai-chat',
            false,
            dirname(OPENAI_CHAT_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize admin functionality
     */
    private function init_admin(): void {
        require_once plugin_dir_path(__FILE__) . 'class-openai-chat-admin.php';
        require_once plugin_dir_path(__FILE__) . 'class-openai-chat-faq.php';
        require_once plugin_dir_path(__FILE__) . 'class-openai-chat-stats.php';
        require_once plugin_dir_path(__FILE__) . 'class-openai-chat-logs.php';
        
        $admin = new OpenAI_Chat_Admin();
        $admin->init();
        
        $faq = new OpenAI_Chat_FAQ();
        $faq->init();
        
        $stats = new OpenAI_Chat_Stats();
        $stats->init();
        
        $logs = new OpenAI_Chat_Logs();
        $logs->init();
    }

    /**
     * Initialize frontend functionality
     */
    private function init_frontend(): void {
        // Include frontend class
        require_once OPENAI_CHAT_PLUGIN_DIR . 'includes/class-openai-chat-frontend.php';
        
        // Initialize frontend
        $frontend = new OpenAI_Chat_Frontend();
        $frontend->init();
    }
} 