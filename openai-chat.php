<?php
/**
 * Plugin Name: OpenAI Chat
 * Plugin URI: https://github.com/NordicHosting/Chatbot-Nora
 * Description: En WordPress plugin som integrerer OpenAI API for chat-funksjonalitet på nettstedet.
 * Version: 1.0.1
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
define('OPENAI_CHAT_VERSION', '1.0.1');
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

/**
 * Check for updates from GitHub
 */
function openai_chat_github_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_data = get_plugin_data(OPENAI_CHAT_PLUGIN_FILE);
    $current_version = $plugin_data['Version'];
    
    // Get GitHub token
    $github_token = get_option('openai_chat_github_token');
    
    // Prepare API request
    $args = array(
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json',
        )
    );
    
    // Add token if available
    if (!empty($github_token)) {
        $args['headers']['Authorization'] = 'token ' . $github_token;
    }
    
    // Get latest release
    $response = wp_remote_get('https://api.github.com/repos/NordicHosting/Chatbot-Nora/releases/latest', $args);
    
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

/**
 * Register settings
 */
public function register_settings(): void {
    register_setting('openai_chat_settings', 'openai_chat_api_key');
    register_setting('openai_chat_settings', 'openai_chat_styling', array(
        'type' => 'array',
        'default' => array(
            'primary_color' => '#0073aa',
            'secondary_color' => '#23282d',
            'border_radius' => '4px'
        )
    ));
    register_setting('openai_chat_settings', 'openai_chat_enabled', array(
        'type' => 'boolean',
        'default' => true
    ));
    register_setting('openai_chat_settings', 'openai_chat_github_token', array(
        'type' => 'string',
        'default' => ''
    ));
}

/**
 * Render settings page
 */
public function render_settings_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('openai_chat_settings');
            do_settings_sections('openai_chat_settings');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="openai_chat_github_token">
                            <?php esc_html_e('GitHub Token', 'openai-chat'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="password" 
                            id="openai_chat_github_token" 
                            name="openai_chat_github_token" 
                            value="<?php echo esc_attr(get_option('openai_chat_github_token')); ?>" 
                            class="regular-text"
                        />
                        <p class="description">
                            <?php esc_html_e('Enter your GitHub personal access token for private repository access', 'openai-chat'); ?>
                            <br>
                            <a href="https://github.com/settings/tokens" target="_blank">
                                <?php esc_html_e('Generate a new token', 'openai-chat'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
                <!-- ... existing settings ... -->
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
} 