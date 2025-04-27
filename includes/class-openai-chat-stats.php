<?php
/**
 * Chat Statistics
 *
 * @package OpenAI_Chat
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles chat statistics
 */
class OpenAI_Chat_Stats {
    /**
     * Initialize statistics functionality
     */
    public function init(): void {
        // Add AJAX handler for stats
        add_action('wp_ajax_openai_chat_get_stats', array($this, 'get_stats'));

        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
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

                <div class="openai-chat-stats-chart-container">
                    <h3><?php esc_html_e('Chat Activity', 'openai-chat'); ?></h3>
                    <div class="openai-chat-stats-chart-wrapper">
                        <canvas id="openai-chat-stats-chart" width="800" height="300"></canvas>
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
            .openai-chat-stats-chart-container {
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
            .openai-chat-stats-chart-wrapper {
                width: 800px;
                height: 300px;
                margin: 0 auto;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                // Function to create chart
                function createChart(elementId, messagesData, sessionsData, labels) {
                    const ctx = document.getElementById(elementId).getContext('2d');
                    return new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: '<?php esc_html_e('Messages', 'openai-chat'); ?>',
                                    data: messagesData,
                                    borderColor: '#0073aa',
                                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                },
                                {
                                    label: '<?php esc_html_e('Sessions', 'openai-chat'); ?>',
                                    data: sessionsData,
                                    borderColor: '#46b450',
                                    backgroundColor: 'rgba(70, 180, 80, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: false,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
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

                // Load both messages and sessions data
                let messagesData = null;
                let sessionsData = null;
                let labels = null;

                function checkAndCreateChart() {
                    if (messagesData && sessionsData && labels) {
                        createChart('openai-chat-stats-chart', messagesData, sessionsData, labels);
                    }
                }

                // Load messages data
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'openai_chat_get_stats',
                        type: 'messages'
                    },
                    success: function(response) {
                        if (response.success) {
                            messagesData = response.data.data;
                            labels = response.data.labels;
                            checkAndCreateChart();
                        }
                    }
                });

                // Load sessions data
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'openai_chat_get_stats',
                        type: 'sessions'
                    },
                    success: function(response) {
                        if (response.success) {
                            sessionsData = response.data.data;
                            checkAndCreateChart();
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
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'messages';
        
        // Get last 7 days of data
        $data = array();
        $labels = array();
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            
            if ($type === 'sessions') {
                $count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(DISTINCT session_id) 
                        FROM {$wpdb->prefix}openai_chat_messages 
                        WHERE DATE(created_at) = %s",
                        $date
                    )
                );
            } else {
                $count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) 
                        FROM {$wpdb->prefix}openai_chat_messages 
                        WHERE DATE(created_at) = %s",
                        $date
                    )
                );
            }
            
            $data[] = $count ?: 0;
            $labels[] = date('M j', strtotime($date));
        }

        wp_send_json_success(array(
            'data' => $data,
            'labels' => $labels
        ));
    }

    /**
     * Enqueue required scripts
     */
    public function enqueue_scripts(): void {
        // Only load on our page
        if (!isset($_GET['page']) || $_GET['page'] !== 'openai-chat-logs') {
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

        // Add inline script for chart defaults
        wp_add_inline_script('chart-js', '
            Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif";
            Chart.defaults.color = "#666";
        ');
    }
} 