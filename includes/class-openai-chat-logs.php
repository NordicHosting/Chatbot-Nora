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
        
        // Add AJAX handlers
        add_action('wp_ajax_openai_chat_clear_logs', array($this, 'clear_logs'));
        add_action('wp_ajax_openai_chat_clear_old_logs', array($this, 'clear_old_logs'));
    }

    /**
     * Add menu page for logs
     */
    public function add_menu_page(): void {
        add_submenu_page(
            'openai-chat',
            __('Chat Logs', 'openai-chat'),
            __('Chat Logs', 'openai-chat'),
            'manage_options',
            'openai-chat-logs',
            array($this, 'render_logs_page')
        );
    }

    /**
     * Log a chat message
     */
    public function log_message(string $session_id, string $type, string $content): void {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'openai_chat_messages',
            array(
                'session_id' => $session_id,
                'message_type' => $type,
                'content' => $content,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get unique dates from messages
     */
    private function get_dates(): array {
        global $wpdb;
        
        return $wpdb->get_col(
            "SELECT DISTINCT DATE(created_at) as date 
             FROM {$wpdb->prefix}openai_chat_messages 
             ORDER BY date DESC"
        );
    }

    /**
     * Get chat logs
     */
    public function get_logs(int $limit = 50, ?string $session_id = null, ?string $date = null): array {
        global $wpdb;
        
        $where = array();
        $params = array($limit);
        
        if ($session_id) {
            $where[] = 'session_id = %s';
            $params = array($session_id, $limit);
        }
        
        if ($date) {
            $where[] = 'DATE(created_at) = %s';
            array_splice($params, -1, 0, array($date));
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}openai_chat_messages 
                 {$where_clause}
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
     * Clear logs older than specified days
     */
    public function clear_old_logs(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $days = isset($_POST['days']) ? intval($_POST['days']) : 0;
        if ($days <= 0) {
            wp_send_json_error('Invalid number of days');
            return;
        }

        global $wpdb;
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}openai_chat_messages 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );

        if ($result === false) {
            wp_send_json_error('Failed to clear old logs');
            return;
        }

        wp_send_json_success(array(
            'deleted' => $result
        ));
    }

    /**
     * Render logs page
     */
    public function render_logs_page(): void {
        $session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : null;
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : null;
        $logs = $this->get_logs(50, $session_id, $date);
        $sessions = $this->get_session_ids();
        $dates = $this->get_dates();
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
                        <?php foreach ($dates as $d): ?>
                            <option value="<?php echo esc_attr($d); ?>" <?php selected($date, $d); ?>>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($d))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button" id="clear-logs">
                        <?php esc_html_e('Clear All Logs', 'openai-chat'); ?>
                    </button>
                    <button type="button" class="button" id="clear-old-logs-7">
                        <?php esc_html_e('Clear Logs Older Than 7 Days', 'openai-chat'); ?>
                    </button>
                    <button type="button" class="button" id="clear-old-logs-30">
                        <?php esc_html_e('Clear Logs Older Than 30 Days', 'openai-chat'); ?>
                    </button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Time', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Session ID', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Type', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Content', 'openai-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr class="message-type-<?php echo esc_attr($log['message_type']); ?>">
                            <td><?php echo esc_html($log['created_at']); ?></td>
                            <td>
                                <a href="?page=openai-chat-logs&session_id=<?php echo esc_attr($log['session_id']); ?>">
                                    <?php echo esc_html(substr($log['session_id'], 0, 8) . '...'); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($log['message_type']); ?></td>
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
                #session-filter {
                    margin-right: 10px;
                }
                #date-filter {
                    margin-right: 10px;
                }
            </style>

            <script>
            jQuery(document).ready(function($) {
                function updateFilters() {
                    var sessionId = $('#session-filter').val();
                    var date = $('#date-filter').val();
                    var url = '?page=openai-chat-logs';
                    
                    if (sessionId) {
                        url += '&session_id=' + sessionId;
                    }
                    if (date) {
                        url += '&date=' + date;
                    }
                    
                    window.location.href = url;
                }
                
                $('#session-filter, #date-filter').on('change', updateFilters);

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

                function clearOldLogs(days) {
                    if (confirm('<?php esc_html_e('Are you sure you want to clear logs older than %d days?', 'openai-chat'); ?>'.replace('%d', days))) {
                        $.post(ajaxurl, {
                            action: 'openai_chat_clear_old_logs',
                            days: days,
                            nonce: '<?php echo wp_create_nonce('openai_chat_clear_old_logs'); ?>'
                        }, function(response) {
                            if (response.success) {
                                alert('<?php esc_html_e('Successfully cleared %d old logs', 'openai-chat'); ?>'.replace('%d', response.data.deleted));
                                location.reload();
                            } else {
                                alert('<?php esc_html_e('Failed to clear old logs', 'openai-chat'); ?>');
                            }
                        });
                    }
                }

                $('#clear-old-logs-7').on('click', function() {
                    clearOldLogs(7);
                });

                $('#clear-old-logs-30').on('click', function() {
                    clearOldLogs(30);
                });
            });
            </script>
        </div>
        <?php
    }
} 