<?php
/**
 * Plugin Name: Chatbot Nora
 * Plugin URI: https://github.com/NordicHosting/Chatbot-Nora
 * Description: En intelligent chatbot basert på OpenAI's GPT-teknologi som kan hjelpe besøkende på din WordPress-nettside.
 * Version: 1.1.3
 * Author: Nordic Hosting
 * Author URI: https://nordic.hosting
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: chatbot-nora
 * Domain Path: /languages
 * GitHub Plugin URI: NordicHosting/Chatbot-Nora
 * GitHub Branch: main
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 6.8
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OPENAI_CHAT_VERSION', '1.1.2');
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
 * Create database tables on plugin activation
 */
function openai_chat_activate() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('OpenAI Chat requires PHP 7.4 or higher. Please update your PHP version.', 'openai-chat'),
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Create messages table
    $messages_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}openai_chat_messages (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(36) NOT NULL,
        message_type varchar(20) NOT NULL,
        content text NOT NULL,
        user_info text DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY session_id (session_id)
    ) $charset_collate;";

    // Create sessions table
    $sessions_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}openai_chat_sessions (
        session_id varchar(36) NOT NULL,
        last_activity datetime NOT NULL,
        PRIMARY KEY  (session_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create tables separately for better error handling
    $messages_result = dbDelta($messages_table);
    if (empty($messages_result)) {
        error_log('OpenAI Chat: Failed to create messages table - ' . $wpdb->last_error);
    }

    $sessions_result = dbDelta($sessions_table);
    if (empty($sessions_result)) {
        error_log('OpenAI Chat: Failed to create sessions table - ' . $wpdb->last_error);
    }

    // Check if user_info column exists
    $table_name = $wpdb->prefix . 'openai_chat_messages';
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'user_info'");
    
    if (!$column_exists) {
        // Add user_info column
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN user_info text DEFAULT NULL");
        error_log('OpenAI Chat: Added user_info column to messages table');
    }

    // Verify tables exist
    $messages_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}openai_chat_messages'") === "{$wpdb->prefix}openai_chat_messages";
    $sessions_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}openai_chat_sessions'") === "{$wpdb->prefix}openai_chat_sessions";

    if (!$messages_exists || !$sessions_exists) {
        error_log('OpenAI Chat: Tables verification failed - Messages: ' . ($messages_exists ? 'Yes' : 'No') . ', Sessions: ' . ($sessions_exists ? 'Yes' : 'No'));
    }
}
register_activation_hook(__FILE__, 'openai_chat_activate');

/**
 * Add settings link to plugins page
 */
function openai_chat_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=chatbot-nora') . '">' . __('Innstillinger', 'chatbot-nora') . '</a>';
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

