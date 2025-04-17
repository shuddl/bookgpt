<?php
/**
 * Admin analytics display
 */
// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap bookgpt-admin bookgpt-analytics-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Analytics Date Range Selector -->
    <div class="bookgpt-date-range-selector">
        <form method="get">
            <input type="hidden" name="page" value="bookgpt-analytics">
            
            <div class="bookgpt-date-inputs">
                <label for="start_date">
                    <?php _e('Start Date:', 'bookgpt-wp'); ?>
                    <input type="date" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : date('Y-m-d', strtotime('-30 days')); ?>">
                </label>
                
                <label for="end_date">
                    <?php _e('End Date:', 'bookgpt-wp'); ?>
                    <input type="date" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : date('Y-m-d'); ?>">
                </label>
                
                <button type="submit" class="button"><?php _e('Apply', 'bookgpt-wp'); ?></button>
            </div>
            
            <div class="bookgpt-date-presets">
                <a href="<?php echo add_query_arg(array('page' => 'bookgpt-analytics', 'start_date' => date('Y-m-d', strtotime('-7 days')), 'end_date' => date('Y-m-d'))); ?>"><?php _e('Last 7 days', 'bookgpt-wp'); ?></a>
                <a href="<?php echo add_query_arg(array('page' => 'bookgpt-analytics', 'start_date' => date('Y-m-d', strtotime('-30 days')), 'end_date' => date('Y-m-d'))); ?>"><?php _e('Last 30 days', 'bookgpt-wp'); ?></a>
                <a href="<?php echo add_query_arg(array('page' => 'bookgpt-analytics', 'start_date' => date('Y-m-d', strtotime('-90 days')), 'end_date' => date('Y-m-d'))); ?>"><?php _e('Last 90 days', 'bookgpt-wp'); ?></a>
                <a href="<?php echo add_query_arg(array('page' => 'bookgpt-analytics', 'start_date' => date('Y-m-d', strtotime('first day of january this year')), 'end_date' => date('Y-m-d'))); ?>"><?php _e('Year to date', 'bookgpt-wp'); ?></a>
            </div>
        </form>
    </div>
    
    <!-- Analytics Overview Cards -->
    <?php
    // Get the analytics data from the database
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
    
    global $wpdb;
    $interactions_table = $wpdb->prefix . 'bookgpt_interactions';
    $conversions_table = $wpdb->prefix . 'bookgpt_conversions';
    $api_usage_table = $wpdb->prefix . 'bookgpt_api_usage';
    
    // Total interactions
    $total_interactions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$interactions_table} WHERE DATE(created_at) BETWEEN %s AND %s",
        $start_date,
        $end_date
    ));
    
    // Total unique sessions
    $unique_sessions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$interactions_table} WHERE DATE(created_at) BETWEEN %s AND %s",
        $start_date,
        $end_date
    ));
    
    // Total conversions
    $total_conversions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$conversions_table} WHERE DATE(created_at) BETWEEN %s AND %s",
        $start_date,
        $end_date
    ));
    
    // Total conversion value
    $total_conversion_value = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(value) FROM {$conversions_table} WHERE DATE(created_at) BETWEEN %s AND %s",
        $start_date,
        $end_date
    ));
    
    // Total API calls
    $total_api_calls = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$api_usage_table} WHERE DATE(created_at) BETWEEN %s AND %s",
        $start_date,
        $end_date
    ));
    
    // Total tokens used
    $total_tokens = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_tokens) FROM {$api_usage_table} WHERE DATE(created_at) BETWEEN %s AND %s",
        $start_date,
        $end_date
    ));
    
    // Estimated cost (assuming $0.02 per 1K tokens for GPT-3.5-turbo)
    $estimated_cost = $total_tokens ? ($total_tokens / 1000) * 0.02 : 0;
    
    // Conversion rate
    $conversion_rate = $unique_sessions ? round(($total_conversions / $unique_sessions) * 100, 2) : 0;
    ?>
    
    <div class="bookgpt-analytics-overview">
        <div class="bookgpt-card">
            <div class="bookgpt-card-header">
                <h2><?php _e('User Interactions', 'bookgpt-wp'); ?></h2>
            </div>
            <div class="bookgpt-card-content">
                <div class="bookgpt-analytics-value"><?php echo esc_html(number_format($total_interactions)); ?></div>
                <div class="bookgpt-analytics-label"><?php _e('Total Interactions', 'bookgpt-wp'); ?></div>
            </div>
            <div class="bookgpt-card-footer">
                <div class="bookgpt-analytics-secondary">
                    <span><?php echo esc_html(number_format($unique_sessions)); ?></span>
                    <?php _e('Unique Sessions', 'bookgpt-wp'); ?>
                </div>
            </div>
        </div>
        
        <div class="bookgpt-card">
            <div class="bookgpt-card-header">
                <h2><?php _e('Conversions', 'bookgpt-wp'); ?></h2>
            </div>
            <div class="bookgpt-card-content">
                <div class="bookgpt-analytics-value"><?php echo esc_html(number_format($total_conversions)); ?></div>
                <div class="bookgpt-analytics-label"><?php _e('Total Conversions', 'bookgpt-wp'); ?></div>
            </div>
            <div class="bookgpt-card-footer">
                <div class="bookgpt-analytics-secondary">
                    <span><?php echo esc_html($conversion_rate); ?>%</span>
                    <?php _e('Conversion Rate', 'bookgpt-wp'); ?>
                </div>
            </div>
        </div>
        
        <div class="bookgpt-card">
            <div class="bookgpt-card-header">
                <h2><?php _e('Revenue', 'bookgpt-wp'); ?></h2>
            </div>
            <div class="bookgpt-card-content">
                <div class="bookgpt-analytics-value">$<?php echo esc_html(number_format($total_conversion_value, 2)); ?></div>
                <div class="bookgpt-analytics-label"><?php _e('Estimated Revenue', 'bookgpt-wp'); ?></div>
            </div>
            <div class="bookgpt-card-footer">
                <div class="bookgpt-analytics-secondary">
                    <?php if ($total_conversions > 0): ?>
                        <span>$<?php echo esc_html(number_format($total_conversion_value / $total_conversions, 2)); ?></span>
                        <?php _e('Avg. Order Value', 'bookgpt-wp'); ?>
                    <?php else: ?>
                        <span>$0.00</span>
                        <?php _e('Avg. Order Value', 'bookgpt-wp'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="bookgpt-card">
            <div class="bookgpt-card-header">
                <h2><?php _e('API Usage', 'bookgpt-wp'); ?></h2>
            </div>
            <div class="bookgpt-card-content">
                <div class="bookgpt-analytics-value"><?php echo esc_html(number_format($total_api_calls)); ?></div>
                <div class="bookgpt-analytics-label"><?php _e('API Calls', 'bookgpt-wp'); ?></div>
            </div>
            <div class="bookgpt-card-footer">
                <div class="bookgpt-analytics-secondary">
                    <span>$<?php echo esc_html(number_format($estimated_cost, 2)); ?></span>
                    <?php _e('Est. API Cost', 'bookgpt-wp'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Analytics Charts -->
    <div class="bookgpt-analytics-charts">
        <div class="bookgpt-card bookgpt-chart-card">
            <div class="bookgpt-card-header">
                <h2><?php _e('Daily Interactions & Conversions', 'bookgpt-wp'); ?></h2>
            </div>
            <div class="bookgpt-card-content">
                <canvas id="interactionsChart"></canvas>
            </div>
        </div>
        
        <div class="bookgpt-card bookgpt-chart-card">
            <div class="bookgpt-card-header">
                <h2><?php _e('Top Book Recommendations', 'bookgpt-wp'); ?></h2>
            </div>
            <div class="bookgpt-card-content">
                <?php
                // Get top books recommended
                $top_books = $wpdb->get_results($wpdb->prepare(
                    "SELECT book_title, book_author, COUNT(*) as recommendation_count 
                    FROM {$interactions_table} 
                    WHERE book_title != '' AND DATE(created_at) BETWEEN %s AND %s 
                    GROUP BY book_title, book_author 
                    ORDER BY recommendation_count DESC 
                    LIMIT 10",
                    $start_date,
                    $end_date
                ));
                
                if ($top_books): ?>
                    <table class="widefat bookgpt-analytics-table">
                        <thead>
                            <tr>
                                <th><?php _e('Book Title', 'bookgpt-wp'); ?></th>
                                <th><?php _e('Author', 'bookgpt-wp'); ?></th>
                                <th><?php _e('Recommendations', 'bookgpt-wp'); ?></th>
                                <th><?php _e('Conversions', 'bookgpt-wp'); ?></th>
                                <th><?php _e('Conversion Rate', 'bookgpt-wp'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_books as $book): 
                                // Get conversions for this book
                                $book_conversions = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$conversions_table} 
                                    WHERE book_title = %s AND DATE(created_at) BETWEEN %s AND %s",
                                    $book->book_title,
                                    $start_date,
                                    $end_date
                                ));
                                
                                $book_conv_rate = $book->recommendation_count ? round(($book_conversions / $book->recommendation_count) * 100, 2) : 0;
                            ?>
                                <tr>
                                    <td><?php echo esc_html($book->book_title); ?></td>
                                    <td><?php echo esc_html($book->book_author); ?></td>
                                    <td><?php echo esc_html(number_format($book->recommendation_count)); ?></td>
                                    <td><?php echo esc_html(number_format($book_conversions)); ?></td>
                                    <td><?php echo esc_html($book_conv_rate); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="bookgpt-no-data"><?php _e('No book recommendations data available for this period.', 'bookgpt-wp'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- User Queries Analysis -->
    <div class="bookgpt-card">
        <div class="bookgpt-card-header">
            <h2><?php _e('User Queries Analysis', 'bookgpt-wp'); ?></h2>
        </div>
        <div class="bookgpt-card-content">
            <?php
            // Get top user queries
            $top_queries = $wpdb->get_results($wpdb->prepare(
                "SELECT user_input, COUNT(*) as query_count 
                FROM {$interactions_table} 
                WHERE user_input != '' AND DATE(created_at) BETWEEN %s AND %s 
                GROUP BY user_input 
                ORDER BY query_count DESC 
                LIMIT 10",
                $start_date,
                $end_date
            ));
            
            if ($top_queries): ?>
                <table class="widefat bookgpt-analytics-table">
                    <thead>
                        <tr>
                            <th><?php _e('User Query', 'bookgpt-wp'); ?></th>
                            <th><?php _e('Count', 'bookgpt-wp'); ?></th>
                            <th><?php _e('Successful?', 'bookgpt-wp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_queries as $query): 
                            // Check if query resulted in a recommendation
                            $successful_query = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$interactions_table} 
                                WHERE user_input = %s AND book_title != '' AND DATE(created_at) BETWEEN %s AND %s",
                                $query->user_input,
                                $start_date,
                                $end_date
                            ));
                            
                            $success_rate = $query->query_count ? round(($successful_query / $query->query_count) * 100, 2) : 0;
                        ?>
                            <tr>
                                <td><?php echo esc_html($query->user_input); ?></td>
                                <td><?php echo esc_html(number_format($query->query_count)); ?></td>
                                <td>
                                    <div class="bookgpt-progress-bar">
                                        <div class="bookgpt-progress" style="width: <?php echo esc_attr($success_rate); ?>%;">
                                            <span><?php echo esc_html($success_rate); ?>%</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="bookgpt-no-data"><?php _e('No user query data available for this period.', 'bookgpt-wp'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- API Usage Breakdown -->
    <div class="bookgpt-card">
        <div class="bookgpt-card-header">
            <h2><?php _e('API Usage Breakdown', 'bookgpt-wp'); ?></h2>
        </div>
        <div class="bookgpt-card-content">
            <?php
            // Get API usage by day
            $daily_api_usage = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    DATE(created_at) as usage_date,
                    COUNT(*) as call_count,
                    SUM(prompt_tokens) as prompt_tokens,
                    SUM(completion_tokens) as completion_tokens,
                    SUM(total_tokens) as total_tokens,
                    AVG(response_time) as avg_response_time
                FROM {$api_usage_table}
                WHERE DATE(created_at) BETWEEN %s AND %s
                GROUP BY DATE(created_at)
                ORDER BY usage_date ASC",
                $start_date,
                $end_date
            ));
            
            if ($daily_api_usage): ?>
                <table class="widefat bookgpt-analytics-table">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'bookgpt-wp'); ?></th>
                            <th><?php _e('API Calls', 'bookgpt-wp'); ?></th>
                            <th><?php _e('Prompt Tokens', 'bookgpt-wp'); ?></th>
                            <th><?php _e('Completion Tokens', 'bookgpt-wp'); ?></th>
                            <th><?php _e('Total Tokens', 'bookgpt-wp'); ?></th>
                            <th><?php _e('Avg. Response Time', 'bookgpt-wp'); ?></th>
                            <th><?php _e('Est. Cost', 'bookgpt-wp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_api_usage as $usage): 
                            $date = new DateTime($usage->usage_date);
                            $est_cost = ($usage->total_tokens / 1000) * 0.02;
                        ?>
                            <tr>
                                <td><?php echo esc_html($date->format('M j, Y')); ?></td>
                                <td><?php echo esc_html(number_format($usage->call_count)); ?></td>
                                <td><?php echo esc_html(number_format($usage->prompt_tokens)); ?></td>
                                <td><?php echo esc_html(number_format($usage->completion_tokens)); ?></td>
                                <td><?php echo esc_html(number_format($usage->total_tokens)); ?></td>
                                <td><?php echo esc_html(number_format($usage->avg_response_time, 2)); ?>s</td>
                                <td>$<?php echo esc_html(number_format($est_cost, 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><?php _e('Total', 'bookgpt-wp'); ?></th>
                            <th><?php echo esc_html(number_format(array_sum(array_column($daily_api_usage, 'call_count')))); ?></th>
                            <th><?php echo esc_html(number_format(array_sum(array_column($daily_api_usage, 'prompt_tokens')))); ?></th>
                            <th><?php echo esc_html(number_format(array_sum(array_column($daily_api_usage, 'completion_tokens')))); ?></th>
                            <th><?php echo esc_html(number_format(array_sum(array_column($daily_api_usage, 'total_tokens')))); ?></th>
                            <th>-</th>
                            <th>$<?php echo esc_html(number_format(array_sum(array_column($daily_api_usage, 'total_tokens')) / 1000 * 0.02, 2)); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p class="bookgpt-no-data"><?php _e('No API usage data available for this period.', 'bookgpt-wp'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Export Section -->
    <div class="bookgpt-card">
        <div class="bookgpt-card-header">
            <h2><?php _e('Export Data', 'bookgpt-wp'); ?></h2>
        </div>
        <div class="bookgpt-card-content">
            <p><?php _e('Export analytics data for the selected date range:', 'bookgpt-wp'); ?></p>
            
            <div class="bookgpt-export-buttons">
                <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=bookgpt_export_analytics&type=interactions&start_date=' . $start_date . '&end_date=' . $end_date . '&_wpnonce=' . wp_create_nonce('bookgpt_export_analytics'))); ?>" class="button">
                    <?php _e('Export Interactions', 'bookgpt-wp'); ?>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=bookgpt_export_analytics&type=conversions&start_date=' . $start_date . '&end_date=' . $end_date . '&_wpnonce=' . wp_create_nonce('bookgpt_export_analytics'))); ?>" class="button">
                    <?php _e('Export Conversions', 'bookgpt-wp'); ?>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=bookgpt_export_analytics&type=api_usage&start_date=' . $start_date . '&end_date=' . $end_date . '&_wpnonce=' . wp_create_nonce('bookgpt_export_analytics'))); ?>" class="button">
                    <?php _e('Export API Usage', 'bookgpt-wp'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        // Load Chart.js
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js';
        script.onload = function() {
            initCharts();
        };
        document.head.appendChild(script);
    } else {
        initCharts();
    }
    
    // Initialize charts
    function initCharts() {
        // Get the interactions chart data
        <?php
        // Get daily interactions and conversions
        $daily_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(i.created_at) as stat_date,
                COUNT(DISTINCT i.id) as interaction_count,
                COUNT(DISTINCT c.id) as conversion_count
            FROM 
                {$interactions_table} i
                LEFT JOIN {$conversions_table} c ON DATE(i.created_at) = DATE(c.created_at)
            WHERE 
                DATE(i.created_at) BETWEEN %s AND %s
            GROUP BY 
                DATE(i.created_at)
            ORDER BY 
                stat_date ASC",
            $start_date,
            $end_date
        ));
        
        $dates = array();
        $interactions = array();
        $conversions = array();
        
        foreach ($daily_stats as $stat) {
            $date = new DateTime($stat->stat_date);
            $dates[] = $date->format('M j');
            $interactions[] = $stat->interaction_count;
            $conversions[] = $stat->conversion_count;
        }
        ?>
        
        var ctx = document.getElementById('interactionsChart').getContext('2d');
        var interactionsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: '<?php _e('Interactions', 'bookgpt-wp'); ?>',
                        data: <?php echo json_encode($interactions); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 3
                    },
                    {
                        label: '<?php _e('Conversions', 'bookgpt-wp'); ?>',
                        data: <?php echo json_encode($conversions); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
</script>
