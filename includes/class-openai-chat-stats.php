<?php
/**
 * Chat Statistics and Dashboard Widget
 *
 * @package OpenAI_Chat
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles chat statistics and dashboard widget
 */
class OpenAI_Chat_Stats {
    /**
     * Initialize statistics functionality
     */
    public function init(): void {
        // Add dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Add AJAX handler for stats
        add_action('wp_ajax_openai_chat_get_stats', array($this, 'get_stats'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue required scripts
     */
    public function enqueue_scripts(): void {
        // Only load on dashboard
        if (!is_admin() || get_current_screen()->id !== 'dashboard') {
            return;
        }

        // Enqueue Chart.js
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
            array(),
            '3.7.0',
            true
        );

        // Add inline script for chart
        wp_add_inline_script('chart-js', '
            Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif";
            Chart.defaults.color = "#666";
        ');
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget(): void {
        wp_add_dashboard_widget(
            'openai_chat_stats',
            __('OpenAI Chat Statistics', 'openai-chat'),
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'openai-chat',
            __('Chat Statistics', 'openai-chat'),
            __('Statistics', 'openai-chat'),
            'manage_options',
            'openai-chat-stats',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'openai-chat',
            __('Chat Logs', 'openai-chat'),
            __('Chat Logs', 'openai-chat'),
            'manage_options',
            'openai-chat-logs',
            array($this, 'render_logs_page')
        );

        add_submenu_page(
            'openai-chat',
            __('Debug Logs', 'openai-chat'),
            __('Debug Logs', 'openai-chat'),
            'manage_options',
            'openai-chat-debug',
            array($this, 'render_debug_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page(): void {
        // Get current stats
        $stats = $this->get_current_stats();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('OpenAI Chat Statistics', 'openai-chat'); ?></h1>
            
            <div class="openai-chat-stats-container">
                <div class="openai-chat-stats-row">
                    <div class="openai-chat-stats-col">
                        <h3><?php esc_html_e('Active Chats', 'openai-chat'); ?></h3>
                        <p class="openai-chat-stats-number"><?php echo esc_html($stats['active_chats']); ?></p>
                    </div>
                    <div class="openai-chat-stats-col">
                        <h3><?php esc_html_e('Total Messages', 'openai-chat'); ?></h3>
                        <p class="openai-chat-stats-number"><?php echo esc_html($stats['total_messages']); ?></p>
                    </div>
                    <div class="openai-chat-stats-col">
                        <h3><?php esc_html_e('Today\'s Messages', 'openai-chat'); ?></h3>
                        <p class="openai-chat-stats-number"><?php echo esc_html($stats['today_messages']); ?></p>
                    </div>
                    <div class="openai-chat-stats-col">
                        <h3><?php esc_html_e('Average Messages/Chat', 'openai-chat'); ?></h3>
                        <p class="openai-chat-stats-number"><?php echo esc_html($stats['avg_messages']); ?></p>
                    </div>
                </div>

                <div class="openai-chat-stats-charts">
                    <div class="openai-chat-stats-chart-container">
                        <h3><?php esc_html_e('Messages per Day', 'openai-chat'); ?></h3>
                        <canvas id="openai-chat-stats-chart"></canvas>
                    </div>
                    <div class="openai-chat-stats-chart-container">
                        <h3><?php esc_html_e('Messages by Type', 'openai-chat'); ?></h3>
                        <canvas id="openai-chat-stats-pie-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .openai-chat-stats-container {
                margin-top: 20px;
            }
            .openai-chat-stats-row {
                display: flex;
                margin-bottom: 20px;
                gap: 20px;
            }
            .openai-chat-stats-col {
                flex: 1;
                text-align: center;
                padding: 20px;
                background: #fff;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .openai-chat-stats h3 {
                margin: 0 0 5px 0;
                font-size: 14px;
                color: #666;
            }
            .openai-chat-stats-number {
                margin: 0;
                font-size: 24px;
                font-weight: bold;
                color: #0073aa;
            }
            .openai-chat-stats-charts {
                display: flex;
                gap: 20px;
                margin-top: 20px;
            }
            .openai-chat-stats-chart-container {
                flex: 1;
                background: #fff;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .openai-chat-stats-chart-container h3 {
                margin: 0 0 20px 0;
                font-size: 16px;
                color: #23282d;
            }
            .openai-chat-stats-chart-container canvas {
                height: 300px;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                // Messages per day chart
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'openai_chat_get_stats'
                    },
                    success: function(response) {
                        if (response.success) {
                            const ctx = document.getElementById('openai-chat-stats-chart').getContext('2d');
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: response.data.labels,
                                    datasets: [{
                                        label: '<?php esc_html_e('Messages per Day', 'openai-chat'); ?>',
                                        data: response.data.data,
                                        borderColor: '#0073aa',
                                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                        fill: true,
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: {
                                                color: 'rgba(0, 0, 0, 0.05)'
                                            }
                                        },
                                        x: {
                                            grid: {
                                                display: false
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }
                });

                // Messages by type chart
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'openai_chat_get_stats',
                        type: 'message_types'
                    },
                    success: function(response) {
                        if (response.success) {
                            const ctx = document.getElementById('openai-chat-stats-pie-chart').getContext('2d');
                            new Chart(ctx, {
                                type: 'pie',
                                data: {
                                    labels: response.data.labels,
                                    datasets: [{
                                        data: response.data.data,
                                        backgroundColor: [
                                            '#0073aa',
                                            '#23282d'
                                        ]
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        }
                                    }
                                }
                            });
                        }
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Get current statistics
     */
    private function get_current_stats(): array {
        global $wpdb;
        
        // Get active chats (sessions with activity in last 30 minutes)
        $active_chats = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) 
                FROM {$wpdb->prefix}openai_chat_sessions 
                WHERE last_activity > %s",
                date('Y-m-d H:i:s', strtotime('-30 minutes'))
            )
        );

        // Get total messages
        $total_messages = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}openai_chat_messages"
        );

        // Get today's messages
        $today_messages = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                FROM {$wpdb->prefix}openai_chat_messages 
                WHERE DATE(created_at) = %s",
                date('Y-m-d')
            )
        );

        // Calculate average messages per chat
        $avg_messages = $total_messages > 0 ? 
            round($total_messages / $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}openai_chat_sessions"), 1) : 
            0;

        return array(
            'active_chats' => $active_chats ?: 0,
            'total_messages' => $total_messages ?: 0,
            'today_messages' => $today_messages ?: 0,
            'avg_messages' => $avg_messages
        );
    }

    /**
     * Get statistics for chart
     */
    public function get_stats(): void {
        global $wpdb;
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'daily';
        
        if ($type === 'message_types') {
            // Get message types statistics
            $user_messages = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}openai_chat_messages WHERE message_type = 'user'"
            );
            $assistant_messages = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}openai_chat_messages WHERE message_type = 'assistant'"
            );
            
            wp_send_json_success(array(
                'labels' => array(
                    __('User Messages', 'openai-chat'),
                    __('Assistant Messages', 'openai-chat')
                ),
                'data' => array(
                    $user_messages ?: 0,
                    $assistant_messages ?: 0
                )
            ));
        } else {
            // Get last 7 days of data
            $data = array();
            $labels = array();
            
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) 
                        FROM {$wpdb->prefix}openai_chat_messages 
                        WHERE DATE(created_at) = %s",
                        $date
                    )
                );
                
                $data[] = $count ?: 0;
                $labels[] = date('M j', strtotime($date));
            }

            wp_send_json_success(array(
                'data' => $data,
                'labels' => $labels
            ));
        }
    }

    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget(): void {
        // Get current stats
        $stats = $this->get_current_stats();
        ?>
        <div class="openai-chat-stats">
            <div class="openai-chat-stats-row">
                <div class="openai-chat-stats-col">
                    <h3><?php esc_html_e('Active Chats', 'openai-chat'); ?></h3>
                    <p class="openai-chat-stats-number"><?php echo esc_html($stats['active_chats']); ?></p>
                </div>
                <div class="openai-chat-stats-col">
                    <h3><?php esc_html_e('Total Messages', 'openai-chat'); ?></h3>
                    <p class="openai-chat-stats-number"><?php echo esc_html($stats['total_messages']); ?></p>
                </div>
            </div>
            <div class="openai-chat-stats-row">
                <div class="openai-chat-stats-col">
                    <h3><?php esc_html_e('Today\'s Messages', 'openai-chat'); ?></h3>
                    <p class="openai-chat-stats-number"><?php echo esc_html($stats['today_messages']); ?></p>
                </div>
                <div class="openai-chat-stats-col">
                    <h3><?php esc_html_e('Average Messages/Chat', 'openai-chat'); ?></h3>
                    <p class="openai-chat-stats-number"><?php echo esc_html($stats['avg_messages']); ?></p>
                </div>
            </div>
            <div class="openai-chat-stats-chart">
                <canvas id="openai-chat-stats-chart"></canvas>
            </div>
            <div class="openai-chat-stats-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=openai-chat-stats')); ?>" class="button button-primary">
                    <?php esc_html_e('View Detailed Statistics', 'openai-chat'); ?>
                </a>
            </div>
        </div>
        <style>
            .openai-chat-stats {
                padding: 10px;
            }
            .openai-chat-stats-row {
                display: flex;
                margin-bottom: 20px;
            }
            .openai-chat-stats-col {
                flex: 1;
                text-align: center;
                padding: 10px;
            }
            .openai-chat-stats h3 {
                margin: 0 0 5px 0;
                font-size: 14px;
                color: #666;
            }
            .openai-chat-stats-number {
                margin: 0;
                font-size: 24px;
                font-weight: bold;
                color: #0073aa;
            }
            .openai-chat-stats-chart {
                margin-top: 20px;
                height: 200px;
            }
            .openai-chat-stats-footer {
                margin-top: 20px;
                text-align: center;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'openai_chat_get_stats'
                    },
                    success: function(response) {
                        if (response.success) {
                            const ctx = document.getElementById('openai-chat-stats-chart').getContext('2d');
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: response.data.labels,
                                    datasets: [{
                                        label: '<?php esc_html_e('Messages per Day', 'openai-chat'); ?>',
                                        data: response.data.data,
                                        borderColor: '#0073aa',
                                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                        fill: true,
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: {
                                                color: 'rgba(0, 0, 0, 0.05)'
                                            }
                                        },
                                        x: {
                                            grid: {
                                                display: false
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Render logs page
     */
    public function render_logs_page(): void {
        global $wpdb;

        // Get pagination parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;

        // Get total number of sessions
        $total_sessions = $wpdb->get_var(
            "SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}openai_chat_sessions"
        );

        // Get sessions with pagination
        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.session_id, s.last_activity, 
                COUNT(m.id) as message_count,
                MAX(m.created_at) as last_message
                FROM {$wpdb->prefix}openai_chat_sessions s
                LEFT JOIN {$wpdb->prefix}openai_chat_messages m ON s.session_id = m.session_id
                GROUP BY s.session_id
                ORDER BY s.last_activity DESC
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        // Calculate total pages
        $total_pages = ceil($total_sessions / $per_page);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Chat Logs', 'openai-chat'); ?></h1>

            <div class="tablenav top">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf(esc_html__('%s sessions', 'openai-chat'), number_format_i18n($total_sessions)); ?></span>
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Session ID', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Last Activity', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Messages', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Last Message', 'openai-chat'); ?></th>
                        <th><?php esc_html_e('Actions', 'openai-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)) : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No chat sessions found.', 'openai-chat'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($sessions as $session) : ?>
                            <tr>
                                <td><?php echo esc_html(substr($session->session_id, 0, 8) . '...'); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session->last_activity))); ?></td>
                                <td><?php echo esc_html($session->message_count); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session->last_message))); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'openai-chat-logs', 'session' => $session->session_id))); ?>" class="button">
                                        <?php esc_html_e('View Chat', 'openai-chat'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            // Show chat details if session is selected
            if (isset($_GET['session'])) {
                $session_id = sanitize_text_field($_GET['session']);
                $messages = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}openai_chat_messages 
                        WHERE session_id = %s 
                        ORDER BY created_at ASC",
                        $session_id
                    )
                );
                ?>
                <div class="openai-chat-log-details">
                    <h2><?php esc_html_e('Chat Details', 'openai-chat'); ?></h2>
                    <div class="openai-chat-log-messages">
                        <?php foreach ($messages as $message) : ?>
                            <div class="openai-chat-log-message <?php echo esc_attr('openai-chat-log-message-' . $message->message_type); ?>">
                                <div class="openai-chat-log-message-header">
                                    <span class="openai-chat-log-message-type">
                                        <?php echo esc_html($message->message_type === 'user' ? __('User', 'openai-chat') : __('Assistant', 'openai-chat')); ?>
                                    </span>
                                    <span class="openai-chat-log-message-time">
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($message->created_at))); ?>
                                    </span>
                                </div>
                                <div class="openai-chat-log-message-content">
                                    <?php echo wp_kses_post($message->content); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <style>
                    .openai-chat-log-details {
                        margin-top: 20px;
                        padding: 20px;
                        background: #fff;
                        border-radius: 4px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }
                    .openai-chat-log-messages {
                        margin-top: 20px;
                    }
                    .openai-chat-log-message {
                        margin-bottom: 20px;
                        padding: 15px;
                        border-radius: 4px;
                    }
                    .openai-chat-log-message-user {
                        background: #f0f6fc;
                        margin-left: 20%;
                    }
                    .openai-chat-log-message-assistant {
                        background: #fff;
                        margin-right: 20%;
                        border: 1px solid #e2e4e7;
                    }
                    .openai-chat-log-message-header {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 10px;
                        font-size: 12px;
                        color: #666;
                    }
                    .openai-chat-log-message-content {
                        line-height: 1.5;
                    }
                </style>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render debug logs page
     */
    public function render_debug_page(): void {
        $debug_file = WP_CONTENT_DIR . '/debug.log';
        $logs = array();
        $error_count = 0;
        $warning_count = 0;
        $info_count = 0;

        if (file_exists($debug_file)) {
            $handle = fopen($debug_file, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    if (strpos($line, 'OpenAI Chat:') !== false) {
                        $logs[] = $line;
                        
                        // Count log types
                        if (strpos($line, 'Failed') !== false || strpos($line, 'Exception') !== false) {
                            $error_count++;
                        } elseif (strpos($line, 'Warning') !== false) {
                            $warning_count++;
                        } else {
                            $info_count++;
                        }
                    }
                }
                fclose($handle);
            }
        }

        // Reverse array to show newest logs first
        $logs = array_reverse($logs);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('OpenAI Chat Debug Logs', 'openai-chat'); ?></h1>

            <div class="openai-chat-debug-stats">
                <div class="openai-chat-debug-stat">
                    <span class="openai-chat-debug-stat-label"><?php esc_html_e('Total Logs', 'openai-chat'); ?></span>
                    <span class="openai-chat-debug-stat-value"><?php echo count($logs); ?></span>
                </div>
                <div class="openai-chat-debug-stat">
                    <span class="openai-chat-debug-stat-label"><?php esc_html_e('Errors', 'openai-chat'); ?></span>
                    <span class="openai-chat-debug-stat-value openai-chat-debug-error"><?php echo $error_count; ?></span>
                </div>
                <div class="openai-chat-debug-stat">
                    <span class="openai-chat-debug-stat-label"><?php esc_html_e('Warnings', 'openai-chat'); ?></span>
                    <span class="openai-chat-debug-stat-value openai-chat-debug-warning"><?php echo $warning_count; ?></span>
                </div>
                <div class="openai-chat-debug-stat">
                    <span class="openai-chat-debug-stat-label"><?php esc_html_e('Info', 'openai-chat'); ?></span>
                    <span class="openai-chat-debug-stat-value openai-chat-debug-info"><?php echo $info_count; ?></span>
                </div>
            </div>

            <div class="openai-chat-debug-actions">
                <form method="post" action="">
                    <?php wp_nonce_field('openai_chat_clear_logs', 'openai_chat_clear_logs_nonce'); ?>
                    <button type="submit" name="clear_logs" class="button button-secondary">
                        <?php esc_html_e('Clear Logs', 'openai-chat'); ?>
                    </button>
                </form>
            </div>

            <div class="openai-chat-debug-logs">
                <?php if (empty($logs)) : ?>
                    <p><?php esc_html_e('No debug logs found.', 'openai-chat'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Time', 'openai-chat'); ?></th>
                                <th><?php esc_html_e('Type', 'openai-chat'); ?></th>
                                <th><?php esc_html_e('Message', 'openai-chat'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log) : 
                                $parts = explode(']', $log, 2);
                                $time = trim(str_replace('[', '', $parts[0]));
                                $message = trim($parts[1]);
                                $type = 'info';
                                if (strpos($message, 'Failed') !== false || strpos($message, 'Exception') !== false) {
                                    $type = 'error';
                                } elseif (strpos($message, 'Warning') !== false) {
                                    $type = 'warning';
                                }
                            ?>
                                <tr class="openai-chat-debug-log-<?php echo esc_attr($type); ?>">
                                    <td><?php echo esc_html($time); ?></td>
                                    <td>
                                        <span class="openai-chat-debug-type openai-chat-debug-type-<?php echo esc_attr($type); ?>">
                                            <?php echo esc_html(ucfirst($type)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($message); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .openai-chat-debug-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }
            .openai-chat-debug-stat {
                flex: 1;
                text-align: center;
                padding: 15px;
                background: #fff;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .openai-chat-debug-stat-label {
                display: block;
                font-size: 14px;
                color: #666;
                margin-bottom: 5px;
            }
            .openai-chat-debug-stat-value {
                display: block;
                font-size: 24px;
                font-weight: bold;
                color: #0073aa;
            }
            .openai-chat-debug-error {
                color: #dc3232;
            }
            .openai-chat-debug-warning {
                color: #ffb900;
            }
            .openai-chat-debug-info {
                color: #0073aa;
            }
            .openai-chat-debug-actions {
                margin: 20px 0;
            }
            .openai-chat-debug-logs {
                margin-top: 20px;
            }
            .openai-chat-debug-type {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
            }
            .openai-chat-debug-type-error {
                background: #dc3232;
                color: #fff;
            }
            .openai-chat-debug-type-warning {
                background: #ffb900;
                color: #fff;
            }
            .openai-chat-debug-type-info {
                background: #0073aa;
                color: #fff;
            }
            .openai-chat-debug-log-error {
                background: #fcf0f1;
            }
            .openai-chat-debug-log-warning {
                background: #fff8e5;
            }
        </style>
        <?php

        // Handle clear logs action
        if (isset($_POST['clear_logs']) && check_admin_referer('openai_chat_clear_logs', 'openai_chat_clear_logs_nonce')) {
            if (file_exists($debug_file)) {
                $handle = fopen($debug_file, 'w');
                if ($handle) {
                    fclose($handle);
                    echo '<div class="notice notice-success"><p>' . esc_html__('Debug logs cleared successfully.', 'openai-chat') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Failed to clear debug logs.', 'openai-chat') . '</p></div>';
                }
            }
        }
    }
} 