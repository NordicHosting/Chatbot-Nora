<?php
/**
 * Plugin Name: OpenAI Chat
 * Plugin URI: https://github.com/yourusername/openai-chat
 * Description: En WordPress plugin som integrerer OpenAI API for chat-funksjonalitet på nettstedet.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: openai-chat
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OPENAI_CHAT_VERSION', '1.0.0');
define('OPENAI_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPENAI_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPENAI_CHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once OPENAI_CHAT_PLUGIN_DIR . 'includes/class-openai-chat.php';

// Initialize the plugin
function openai_chat_init() {
    $plugin = new OpenAI_Chat();
    $plugin->init();
}
add_action('plugins_loaded', 'openai_chat_init'); 