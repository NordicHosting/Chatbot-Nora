<?php
/**
 * Admin functionality class
 *
 * @package OpenAI_Chat
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin functionality
 */
class OpenAI_Chat_Admin {
    /**
     * Initialize admin functionality
     */
    public function init(): void {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Add admin notices
        add_action('admin_notices', array($this, 'check_api_key'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        // Add main menu
        add_menu_page(
            __('Chatbot Nora Settings', 'chatbot-nora'),
            __('Chatbot Nora', 'chatbot-nora'),
            'manage_options',
            'chatbot-nora',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            2  // Position i menyen (2 = rett under Dashboard)
        );

        // Add settings submenu
        add_submenu_page(
            'chatbot-nora',
            __('Chatbot Nora Settings', 'chatbot-nora'),
            __('Settings', 'chatbot-nora'),
            'manage_options',
            'chatbot-nora',
            array($this, 'render_settings_page')
        );

        // Add logs and statistics submenu
        add_submenu_page(
            'chatbot-nora',
            __('Chat Logs & Statistics', 'chatbot-nora'),
            __('Chat Logs & Statistics', 'chatbot-nora'),
            'manage_options',
            'chatbot-nora-logs',
            array($this, 'render_stats_and_logs_page')
        );
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
    }

    /**
     * Check API key and show notice if missing or invalid
     */
    public function check_api_key(): void {
        $api_key = get_option('openai_chat_api_key');
        
        if (empty($api_key)) {
            $this->show_notice(
                __('OpenAI API key is missing. Please add your API key in the settings.', 'openai-chat'),
                'error'
            );
            return;
        }

        // Verify API key if not already verified
        if (!$this->verify_api_key($api_key)) {
            $this->show_notice(
                __('OpenAI API key is invalid. Please check your API key in the settings.', 'openai-chat'),
                'error'
            );
        }
    }

    /**
     * Verify API key by making a test request
     */
    private function verify_api_key(string $api_key): bool {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Test'
                    )
                ),
                'max_tokens' => 5
            )),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            error_log('OpenAI Chat: API verification failed - ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }

    /**
     * Show admin notice
     */
    private function show_notice(string $message, string $type = 'info'): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
            <?php if ($type === 'error'): ?>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=openai-chat')); ?>" class="button button-primary">
                        <?php esc_html_e('Go to Settings', 'openai-chat'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
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
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
                <span class="version" style="font-size: 0.8em; color: #666; font-weight: normal;">
                    <?php echo esc_html(sprintf(__('Version %s', 'openai-chat'), OPENAI_CHAT_VERSION)); ?>
                </span>
            </h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('openai_chat_settings');
                do_settings_sections('openai_chat_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="openai_chat_enabled">
                                <?php esc_html_e('Enable Chat', 'openai-chat'); ?>
                            </label>
                        </th>
                        <td>
                            <input 
                                type="checkbox" 
                                id="openai_chat_enabled" 
                                name="openai_chat_enabled" 
                                value="1" 
                                <?php checked(get_option('openai_chat_enabled', true)); ?>
                            />
                            <p class="description">
                                <?php esc_html_e('Enable or disable the chat interface on the frontend', 'openai-chat'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="openai_chat_api_key">
                                <?php esc_html_e('API Key', 'openai-chat'); ?>
                            </label>
                        </th>
                        <td>
                            <input 
                                type="password" 
                                id="openai_chat_api_key" 
                                name="openai_chat_api_key" 
                                value="<?php echo esc_attr(get_option('openai_chat_api_key')); ?>" 
                                class="regular-text"
                            />
                            <p class="description">
                                <?php esc_html_e('Your OpenAI API key', 'openai-chat'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="openai_chat_primary_color">
                                <?php esc_html_e('Primary Color', 'openai-chat'); ?>
                            </label>
                        </th>
                        <td>
                            <input 
                                type="color" 
                                id="openai_chat_primary_color" 
                                name="openai_chat_styling[primary_color]" 
                                value="<?php echo esc_attr(get_option('openai_chat_styling')['primary_color'] ?? '#0073aa'); ?>" 
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="openai_chat_secondary_color">
                                <?php esc_html_e('Secondary Color', 'openai-chat'); ?>
                            </label>
                        </th>
                        <td>
                            <input 
                                type="color" 
                                id="openai_chat_secondary_color" 
                                name="openai_chat_styling[secondary_color]" 
                                value="<?php echo esc_attr(get_option('openai_chat_styling')['secondary_color'] ?? '#23282d'); ?>" 
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="openai_chat_border_radius">
                                <?php esc_html_e('Border Radius', 'openai-chat'); ?>
                            </label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="openai_chat_border_radius" 
                                name="openai_chat_styling[border_radius]" 
                                value="<?php echo esc_attr(get_option('openai_chat_styling')['border_radius'] ?? '4px'); ?>" 
                                class="regular-text"
                            />
                            <p class="description">
                                <?php esc_html_e('Enter border radius in pixels (e.g. 4px)', 'openai-chat'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render logs and statistics page
     */
    public function render_stats_and_logs_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Vis statistikk øverst
        require_once plugin_dir_path(__FILE__) . 'class-openai-chat-stats.php';
        $stats = new OpenAI_Chat_Stats();
        $stats->render_admin_page();

        // Vis chat-logger under
        $logs = new OpenAI_Chat_Logs();
        $logs->render_logs_page();
    }

    /**
     * Render FAQ page
     */
    public function render_faq_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('FAQ', 'chatbot-nora'); ?></h1>
            
            <div class="card">
                <h2><?php esc_html_e('Vanlige spørsmål', 'chatbot-nora'); ?></h2>
                
                <h3><?php esc_html_e('Hvordan fungerer Chatbot Nora?', 'chatbot-nora'); ?></h3>
                <p><?php esc_html_e('Chatbot Nora er en intelligent chatbot basert på OpenAI\'s GPT-teknologi. Den kan svare på spørsmål, hjelpe med problemer og gi generell informasjon.', 'chatbot-nora'); ?></p>
                
                <h3><?php esc_html_e('Hvordan konfigurerer jeg API-nøkkelen?', 'chatbot-nora'); ?></h3>
                <p><?php esc_html_e('Gå til innstillinger-siden og lim inn din OpenAI API-nøkkel i feltet. Du kan få en API-nøkkel fra OpenAI sin nettside.', 'chatbot-nora'); ?></p>
                
                <h3><?php esc_html_e('Kan jeg endre utseendet på chatten?', 'chatbot-nora'); ?></h3>
                <p><?php esc_html_e('Ja, du kan tilpasse farger og utseende i innstillingene. Se "Innstillinger" i admin-menyen.', 'chatbot-nora'); ?></p>
                
                <h3><?php esc_html_e('Hvordan ser jeg bruksstatistikk?', 'chatbot-nora'); ?></h3>
                <p><?php esc_html_e('Gå til "Bruksstatistikk" i admin-menyen for å se oversikt over chat-aktivitet.', 'chatbot-nora'); ?></p>
                
                <h3><?php esc_html_e('Hvordan ser jeg chatloggene?', 'chatbot-nora'); ?></h3>
                <p><?php esc_html_e('Gå til "Chatlogger" i admin-menyen for å se historikk over alle samtaler.', 'chatbot-nora'); ?></p>
            </div>
        </div>
        <?php
    }
} 