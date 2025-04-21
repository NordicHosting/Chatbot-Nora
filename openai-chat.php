<?php
/**
 * Plugin Name: OpenAI Chat
 * Plugin URI: https://github.com/NordicHosting/Chatbot-Nora
 * Description: En WordPress plugin som integrerer OpenAI API for chat-funksjonalitet på nettstedet.
 * Version: 1.0.0
 * Author: Jon Bjorseth
 * Author URI: https://github.com/jonbjorseth
 * Text Domain: openai-chat
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: NordicHosting/Chatbot-Nora
 * GitHub Branch: main
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

// Add GitHub Updater functionality
add_filter('update_plugins_github.com', 'openai_chat_github_update', 10, 4);
function openai_chat_github_update($update, $plugin_data, $plugin_file, $locales) {
    if (OPENAI_CHAT_PLUGIN_BASENAME !== $plugin_file) {
        return $update;
    }

    $response = wp_remote_get('https://api.github.com/repos/NordicHosting/Chatbot-Nora/releases/latest');
    
    if (is_wp_error($response)) {
        return $update;
    }

    $release_data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!isset($release_data['tag_name'])) {
        return $update;
    }

    $latest_version = ltrim($release_data['tag_name'], 'v');
    $current_version = OPENAI_CHAT_VERSION;

    if (version_compare($latest_version, $current_version, '>')) {
        return array(
            'slug' => 'openai-chat',
            'version' => $latest_version,
            'url' => 'https://github.com/NordicHosting/Chatbot-Nora',
            'package' => $release_data['zipball_url']
        );
    }

    return $update;
}

// Add GitHub Updater headers
add_filter('extra_plugin_headers', 'openai_chat_add_headers');
function openai_chat_add_headers($headers) {
    $headers[] = 'GitHub Plugin URI';
    $headers[] = 'GitHub Branch';
    return $headers;
} 