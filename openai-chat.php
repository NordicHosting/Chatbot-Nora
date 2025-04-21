<?php
/**
 * Plugin Name: OpenAI Chat
 * Plugin URI: https://github.com/NordicHosting/Chatbot-Nora
 * Description: En WordPress plugin som integrerer OpenAI API for chat-funksjonalitet på nettstedet.
 * Version: 1.0.4
 * Author: Jon Bjorseth
 * Author URI: https://jonbjorseth.no
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
define('OPENAI_CHAT_VERSION', '1.0.4');
define('OPENAI_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPENAI_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPENAI_CHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('OPENAI_CHAT_PLUGIN_FILE', __FILE__);

// Include required files
require_once OPENAI_CHAT_PLUGIN_DIR . 'includes/class-openai-chat.php';

// Initialize the plugin
function openai_chat_init() {
    $plugin = new OpenAI_Chat();
    $plugin->init();
}
add_action('plugins_loaded', 'openai_chat_init');

/**
 * Add settings link to plugins page
 */
function openai_chat_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=openai-chat') . '">' . __('Innstillinger', 'openai-chat') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . OPENAI_CHAT_PLUGIN_BASENAME, 'openai_chat_add_settings_link');

/**
 * Check for updates from GitHub
 */
function openai_chat_github_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_data = get_plugin_data(OPENAI_CHAT_PLUGIN_FILE);
    $current_version = $plugin_data['Version'];
    
    // Get latest release from public repo
    $response = wp_remote_get('https://api.github.com/repos/NordicHosting/Chatbot-Nora/releases/latest', array(
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json'
        )
    ));
    
    if (is_wp_error($response)) {
        return $transient;
    }
    
    $release = json_decode(wp_remote_retrieve_body($response));
    
    if (empty($release) || empty($release->tag_name)) {
        return $transient;
    }
    
    $latest_version = ltrim($release->tag_name, 'v');
    
    if (version_compare($latest_version, $current_version, '>')) {
        $transient->response[OPENAI_CHAT_PLUGIN_BASENAME] = (object) array(
            'slug' => 'openai-chat',
            'plugin' => OPENAI_CHAT_PLUGIN_BASENAME,
            'new_version' => $latest_version,
            'url' => $plugin_data['PluginURI'],
            'package' => $release->zipball_url
        );
    }
    
    return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'openai_chat_github_update');

// Add GitHub Updater headers
add_filter('extra_plugin_headers', 'openai_chat_add_headers');
function openai_chat_add_headers($headers) {
    $headers[] = 'GitHub Plugin URI';
    $headers[] = 'GitHub Branch';
    return $headers;
} 