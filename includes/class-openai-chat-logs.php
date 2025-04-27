<?php
/**
 * Handles chat logging functionality
 *
 * @package OpenAI_Chat
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chat logging class
 */
class OpenAI_Chat_Logs {
    /**
     * Initialize logging functionality
     */
    public function init(): void {
        // Add menu page
        add_action('admin_menu', array($this, 'add_menu_page'));
        
        // Add AJAX handler for clearing logs
        add_action('wp_ajax_openai_chat_clear_logs', array($this, 'clear_logs'));
    }

    /**
     * Add menu page for logs and stats
     */
    public function add_menu_page(): void {
        add_submenu_page(
            'openai-chat',
            __('Chat Logs & Statistics', 'openai-chat'),
            __('Chat Logs & Statistics', 'openai-chat'),
            'manage_options',
            'openai-chat-logs',
            array($this, 'render_stats_and_logs_page')
        );
    }

    /**
     * Log a chat message
     */
    public function log_message(string $session_id, string $type, string $content, ?string $user_info = null): void {
        global $wpdb;
        
        // Verify table exists
        $table_name = $wpdb->prefix . 'openai_chat_messages';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log('OpenAI Chat: Messages table does not exist');
            return;
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'message_type' => $type,
                'content' => $content,
                'user_info' => $user_info,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            error_log('OpenAI Chat: Failed to log message - ' . $wpdb->last_error);
        }
    }

    /**
     * Get chat logs
     */
    public function get_logs(?string $session_id = null, int $limit = 100): array {
        global $wpdb;
        
        $where = '';
        $params = array($limit);
        
        if ($session_id) {
            $where = 'WHERE session_id = %s';
            $params = array($session_id, $limit);
        }
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}openai_chat_messages 
                 {$where}
                 ORDER BY created_at DESC 
                 LIMIT %d",
                $params
            ),
            ARRAY_A
        );
    }

    /**
     * Get unique session IDs
     */
    private function get_session_ids(): array {
        global $wpdb;
        
        return $wpdb->get_col(
            "SELECT DISTINCT session_id 
             FROM {$wpdb->prefix}openai_chat_messages 
             ORDER BY created_at DESC"
        );
    }

    /**
     * Clear all logs
     */
    public function clear_logs(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}openai_chat_messages");
        
        wp_send_json_success();
    }

    /**
     * Render both statistics and logs on the same page
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
        $this->render_logs_page();
    }

    /**
     * Render logs page
     */
    public function render_logs_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : null;
        $date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : null;
        $user_type = isset($_GET['user_type']) ? sanitize_text_field($_GET['user_type']) : null;
        
        $logs = $this->get_logs($session_id);
        $sessions = $this->get_session_ids();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Chat Logs', 'openai-chat'); ?></h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select id="session-filter">
                        <option value=""><?php esc_html_e('All Sessions', 'openai-chat'); ?></option>
                        <?php foreach ($sessions as $sid): ?>
                            <option value="<?php echo esc_attr($sid); ?>" <?php selected($session_id, $sid); ?>>
                                <?php echo esc_html(substr($sid, 0, 8) . '...'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="date-filter">
                        <option value=""><?php esc_html_e('All Dates', 'openai-chat'); ?></option>
                        <?php
                        $dates = $this->get_unique_dates();
                        foreach ($dates as $date): ?>
                            <option value="<?php echo esc_attr($date); ?>" <?php selected($date_filter, $date); ?>>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($date))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="user-type-filter">
                        <option value=""><?php esc_html_e('All Users', 'openai-chat'); ?></option>
                        <option value="user" <?php selected($user_type, 'user'); ?>><?php esc_html_e('Users', 'openai-chat'); ?></option>
                        <option value="assistant" <?php selected($user_type, 'assistant'); ?>><?php esc_html_e('Nora', 'openai-chat'); ?></option>
                    </select>

                    <button type="button" class="button" id="clear-logs">
                        <?php esc_html_e('Clear All Logs', 'openai-chat'); ?>
                    </button>
                </div>
            </div>

            <?php if ($session_id): ?>
                <p>
                    <a href="?page=openai-chat-logs" class="button">
                        <?php esc_html_e('Back to all logs', 'openai-chat'); ?>
                    </a>
                </p>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Time', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('User', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Session ID', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Content', 'openai-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): 
                        // Filtrer etter dato
                        if ($date_filter && date('Y-m-d', strtotime($log['created_at'])) !== $date_filter) {
                            continue;
                        }
                        // Filtrer etter brukertype
                        if ($user_type && $log['message_type'] !== $user_type) {
                            continue;
                        }
                    ?>
                        <tr class="message-type-<?php echo esc_attr($log['message_type']); ?>">
                            <td><?php echo esc_html($log['created_at']); ?></td>
                            <td>
                                <?php
                                if ($log['message_type'] === 'user') {
                                    if (!empty($log['user_info'])) {
                                        $user_info = json_decode($log['user_info'], true);
                                        echo '<strong>' . esc_html($user_info['name']) . '</strong>';
                                        if (!empty($user_info['email'])) {
                                            echo '<br><small>' . esc_html($user_info['email']) . '</small>';
                                        }
                                    } else {
                                        echo '<strong>' . esc_html__('User', 'openai-chat') . '</strong>';
                                    }
                                } else {
                                    echo '<strong>Nora</strong>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="?page=openai-chat-logs&session_id=<?php echo esc_attr($log['session_id']); ?>">
                                    <?php echo esc_html(substr($log['session_id'], 0, 8) . '...'); ?>
                                </a>
                            </td>
                            <td><?php echo wp_kses_post($log['content']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <style>
                .message-type-user {
                    background-color: #f0f7ff;
                }
                .message-type-assistant {
                    background-color: #f8f8f8;
                }
                .message-type-error {
                    background-color: #fff0f0;
                }
                #session-filter,
                #date-filter,
                #user-type-filter {
                    margin-right: 10px;
                }
                .wp-list-table td small {
                    color: #666;
                    font-size: 12px;
                }
            </style>

            <script>
            jQuery(document).ready(function($) {
                function updateFilters() {
                    var sessionId = $('#session-filter').val();
                    var date = $('#date-filter').val();
                    var userType = $('#user-type-filter').val();
                    
                    var url = '?page=openai-chat-logs';
                    if (sessionId) url += '&session_id=' + sessionId;
                    if (date) url += '&date=' + date;
                    if (userType) url += '&user_type=' + userType;
                    
                    window.location.href = url;
                }

                $('#session-filter, #date-filter, #user-type-filter').on('change', updateFilters);

                $('#clear-logs').on('click', function() {
                    if (confirm('<?php esc_html_e('Are you sure you want to clear all logs?', 'openai-chat'); ?>')) {
                        $.post(ajaxurl, {
                            action: 'openai_chat_clear_logs',
                            nonce: '<?php echo wp_create_nonce('openai_chat_clear_logs'); ?>'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('<?php esc_html_e('Failed to clear logs', 'openai-chat'); ?>');
                            }
                        });
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Get unique dates from logs
     */
    private function get_unique_dates(): array {
        global $wpdb;
        
        return $wpdb->get_col(
            "SELECT DISTINCT DATE(created_at) 
             FROM {$wpdb->prefix}openai_chat_messages 
             ORDER BY created_at DESC"
        );
    }
} 