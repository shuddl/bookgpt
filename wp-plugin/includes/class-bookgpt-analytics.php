<?php

/**
 * The analytics functionality of the plugin.
 */
class BookGPT_Analytics {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('wp_ajax_bookgpt_get_analytics', array($this, 'get_analytics'));
        add_action('wp_ajax_bookgpt_export_analytics', array($this, 'export_analytics'));
        add_action('wp_ajax_nopriv_bookgpt_track_event', array($this, 'track_event'));
        add_action('wp_ajax_bookgpt_track_event', array($this, 'track_event'));
        add_action('wp_ajax_bookgpt_track_conversion', array($this, 'track_conversion'));
        add_action('wp_ajax_nopriv_bookgpt_track_conversion', array($this, 'track_conversion'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tracking_script'));
    }

    /**
     * Enqueue tracking script for affiliate link tracking
     */
    public function enqueue_tracking_script() {
        $options = get_option('bookgpt_options');
        if (isset($options['enable_analytics']) && $options['enable_analytics'] === 'yes') {
            wp_enqueue_script('bookgpt-tracking', plugin_dir_url(dirname(__FILE__)) . 'public/js/bookgpt-tracking.js', array('jquery'), BOOKGPT_VERSION, true);
            wp_localize_script('bookgpt-tracking', 'bookgpt_tracking', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bookgpt-tracking-nonce'),
            ));
        }
    }

    /**
     * Get total number of conversations
     * 
     * @return int
     */
    public function get_total_conversations() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_interactions';
        
        // Get count of distinct session_id
        $count = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table_name");
        
        return intval($count);
    }
    
    /**
     * Get total number of book recommendations made
     * 
     * @return int
     */
    public function get_total_recommendations() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_interactions';
        
        // Count records where books_recommended is not null/empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE books_recommended IS NOT NULL AND books_recommended != ''");
        
        return intval($count);
    }
    
    /**
     * Get total number of book clicks
     * 
     * @return int
     */
    public function get_total_book_clicks() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_conversions';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        return intval($count);
    }
    
    /**
     * Get clickthrough rate
     * 
     * @return float
     */
    public function get_clickthrough_rate() {
        $total_recommendations = $this->get_total_recommendations();
        $total_clicks = $this->get_total_book_clicks();
        
        if ($total_recommendations === 0) {
            return 0;
        }
        
        return round(($total_clicks / $total_recommendations) * 100, 2);
    }
    
    /**
     * Get monthly API cost
     * 
     * @return float
     */
    public function get_monthly_api_cost() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_api_usage';
        
        $first_day_of_month = date('Y-m-01 00:00:00');
        $last_day_of_month = date('Y-m-t 23:59:59');
        
        $cost = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(cost) FROM $table_name WHERE timestamp BETWEEN %s AND %s",
                $first_day_of_month,
                $last_day_of_month
            )
        );
        
        return floatval($cost) ?? 0;
    }
    
    /**
     * Get total tokens used
     * 
     * @return int
     */
    public function get_total_tokens() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_api_usage';
        
        $tokens = $wpdb->get_var("SELECT SUM(tokens_used) FROM $table_name");
        
        return intval($tokens) ?? 0;
    }
    
    /**
     * Get conversation chart data for a given period
     * 
     * @param string $period Period ('7days', '30days', '90days', 'year')
     * @return array
     */
    public function get_conversation_chart_data($period = '7days') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_interactions';
        
        // Calculate date range based on period
        $end_date = date('Y-m-d');
        $start_date = $this->get_start_date_for_period($period);
        
        // Get daily counts
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(timestamp) as date, COUNT(DISTINCT session_id) as count 
                 FROM $table_name 
                 WHERE DATE(timestamp) BETWEEN %s AND %s 
                 GROUP BY DATE(timestamp) 
                 ORDER BY date ASC",
                $start_date,
                $end_date
            )
        );
        
        // Format data for Chart.js
        $labels = array();
        $data = array();
        
        // Create date range
        $date_range = $this->create_date_range($start_date, $end_date);
        
        // Initialize data with zeros
        foreach ($date_range as $date) {
            $labels[] = $date;
            $data[$date] = 0;
        }
        
        // Fill in actual data
        foreach ($results as $row) {
            $data[$row->date] = intval($row->count);
        }
        
        return array(
            'labels' => $labels,
            'data' => array_values($data)
        );
    }
    
    /**
     * Get book click chart data for a given period
     * 
     * @param string $period Period ('7days', '30days', '90days', 'year')
     * @return array
     */
    public function get_book_click_chart_data($period = '7days') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_conversions';
        
        // Calculate date range based on period
        $end_date = date('Y-m-d');
        $start_date = $this->get_start_date_for_period($period);
        
        // Get daily counts
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(timestamp) as date, COUNT(*) as count 
                 FROM $table_name 
                 WHERE DATE(timestamp) BETWEEN %s AND %s 
                 GROUP BY DATE(timestamp) 
                 ORDER BY date ASC",
                $start_date,
                $end_date
            )
        );
        
        // Format data for Chart.js
        $labels = array();
        $data = array();
        
        // Create date range
        $date_range = $this->create_date_range($start_date, $end_date);
        
        // Initialize data with zeros
        foreach ($date_range as $date) {
            $labels[] = $date;
            $data[$date] = 0;
        }
        
        // Fill in actual data
        foreach ($results as $row) {
            $data[$row->date] = intval($row->count);
        }
        
        return array(
            'labels' => $labels,
            'data' => array_values($data)
        );
    }
    
    /**
     * Get API usage chart data for a given period
     * 
     * @param string $period Period ('7days', '30days', '90days', 'year')
     * @return array
     */
    public function get_api_usage_chart_data($period = '7days') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_api_usage';
        
        // Calculate date range based on period
        $end_date = date('Y-m-d');
        $start_date = $this->get_start_date_for_period($period);
        
        // Get daily token usage
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(timestamp) as date, SUM(tokens_used) as tokens, SUM(cost) as cost
                 FROM $table_name 
                 WHERE DATE(timestamp) BETWEEN %s AND %s 
                 GROUP BY DATE(timestamp) 
                 ORDER BY date ASC",
                $start_date,
                $end_date
            )
        );
        
        // Format data for Chart.js
        $labels = array();
        $tokens_data = array();
        $cost_data = array();
        
        // Create date range
        $date_range = $this->create_date_range($start_date, $end_date);
        
        // Initialize data with zeros
        foreach ($date_range as $date) {
            $labels[] = $date;
            $tokens_data[$date] = 0;
            $cost_data[$date] = 0;
        }
        
        // Fill in actual data
        foreach ($results as $row) {
            $tokens_data[$row->date] = intval($row->tokens);
            $cost_data[$row->date] = floatval($row->cost);
        }
        
        return array(
            'labels' => $labels,
            'tokens' => array_values($tokens_data),
            'cost' => array_values($cost_data)
        );
    }
    
    /**
     * Get popular books with click counts
     * 
     * @param int $limit Number of books to return
     * @return array
     */
    public function get_popular_books($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_conversions';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT book_title, book_author, COUNT(*) as clicks
                 FROM $table_name
                 GROUP BY book_title, book_author
                 ORDER BY clicks DESC
                 LIMIT %d",
                $limit
            )
        );
        
        return $results;
    }
    
    /**
     * Get affiliate link statistics
     * 
     * @return array
     */
    public function get_affiliate_link_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_conversions';
        
        // Get stats by day for the last 30 days
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        $today = date('Y-m-d');
        
        $daily_stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(timestamp) as date, COUNT(*) as clicks
                 FROM $table_name
                 WHERE DATE(timestamp) BETWEEN %s AND %s
                 GROUP BY DATE(timestamp)
                 ORDER BY date DESC",
                $thirty_days_ago,
                $today
            )
        );
        
        // Get total clicks
        $total_clicks = $this->get_total_book_clicks();
        
        // Get monthly clicks
        $first_day_of_month = date('Y-m-01');
        $last_day_of_month = date('Y-m-t');
        
        $monthly_clicks = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM $table_name
                 WHERE DATE(timestamp) BETWEEN %s AND %s",
                $first_day_of_month,
                $last_day_of_month
            )
        );
        
        return array(
            'total_clicks' => $total_clicks,
            'monthly_clicks' => intval($monthly_clicks),
            'daily_stats' => $daily_stats
        );
    }
    
    /**
     * Get top performing books by clicks
     * 
     * @param int $limit Number of books to return
     * @return array
     */
    public function get_top_performing_books($limit = 10) {
        return $this->get_popular_books($limit);
    }
    
    /**
     * Helper method to get start date based on period
     * 
     * @param string $period Period ('7days', '30days', '90days', 'year')
     * @return string Date in Y-m-d format
     */
    private function get_start_date_for_period($period) {
        switch ($period) {
            case '7days':
                return date('Y-m-d', strtotime('-7 days'));
            case '30days':
                return date('Y-m-d', strtotime('-30 days'));
            case '90days':
                return date('Y-m-d', strtotime('-90 days'));
            case 'year':
                return date('Y-m-d', strtotime('-1 year'));
            default:
                return date('Y-m-d', strtotime('-7 days'));
        }
    }
    
    /**
     * Helper method to create a continuous date range
     * 
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @return array Array of dates
     */
    private function create_date_range($start_date, $end_date) {
        $dates = array();
        
        $current_date = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        while ($current_date <= $end) {
            $dates[] = $current_date->format('Y-m-d');
            $current_date->modify('+1 day');
        }
        
        return $dates;
    }

    /**
     * Get analytics data
     */
    public function get_analytics() {
        check_ajax_referer('bookgpt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
            return;
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '7days';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'api_usage';
        
        global $wpdb;
        
        switch ($period) {
            case '24hours':
                $start_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
                $interval = 'HOUR';
                $format = '%Y-%m-%d %H:00:00';
                $labels_format = 'H:00';
                break;
            case '7days':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                $interval = 'DAY';
                $format = '%Y-%m-%d 00:00:00';
                $labels_format = 'M j';
                break;
            case '30days':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                $interval = 'DAY';
                $format = '%Y-%m-%d 00:00:00';
                $labels_format = 'M j';
                break;
            case 'ytd':
                $start_date = date('Y-01-01 00:00:00');
                $interval = 'MONTH';
                $format = '%Y-%m-01 00:00:00';
                $labels_format = 'M';
                break;
            default:
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                $interval = 'DAY';
                $format = '%Y-%m-%d 00:00:00';
                $labels_format = 'M j';
                break;
        }
        
        $end_date = date('Y-m-d H:i:s');
        
        switch ($type) {
            case 'api_usage':
                $table_name = $wpdb->prefix . 'bookgpt_api_usage';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE_FORMAT(created_at, %s) as date_group, 
                            SUM(total_tokens) as value 
                     FROM {$table_name} 
                     WHERE created_at BETWEEN %s AND %s 
                     GROUP BY date_group 
                     ORDER BY date_group ASC",
                    $format,
                    $start_date,
                    $end_date
                ));
                break;
            case 'interactions':
                $table_name = $wpdb->prefix . 'bookgpt_interactions';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE_FORMAT(created_at, %s) as date_group, 
                            COUNT(*) as value 
                     FROM {$table_name} 
                     WHERE created_at BETWEEN %s AND %s 
                     GROUP BY date_group 
                     ORDER BY date_group ASC",
                    $format,
                    $start_date,
                    $end_date
                ));
                break;
            case 'conversions':
                $table_name = $wpdb->prefix . 'bookgpt_conversions';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE_FORMAT(created_at, %s) as date_group, 
                            COUNT(*) as value 
                     FROM {$table_name} 
                     WHERE created_at BETWEEN %s AND %s 
                     GROUP BY date_group 
                     ORDER BY date_group ASC",
                    $format,
                    $start_date,
                    $end_date
                ));
                break;
            case 'conversion_rates':
                $interactions_table = $wpdb->prefix . 'bookgpt_interactions';
                $conversions_table = $wpdb->prefix . 'bookgpt_conversions';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT 
                        DATE_FORMAT(i.date_group, %s) as date_group,
                        IFNULL(c.conversions, 0) as conversions,
                        i.interactions as interactions,
                        IFNULL(c.conversions / i.interactions * 100, 0) as value
                     FROM (
                        SELECT DATE_FORMAT(created_at, %s) as date_group, COUNT(*) as interactions
                        FROM {$interactions_table}
                        WHERE created_at BETWEEN %s AND %s
                        GROUP BY date_group
                     ) i
                     LEFT JOIN (
                        SELECT DATE_FORMAT(created_at, %s) as date_group, COUNT(*) as conversions
                        FROM {$conversions_table}
                        WHERE created_at BETWEEN %s AND %s
                        GROUP BY date_group
                     ) c ON i.date_group = c.date_group
                     ORDER BY i.date_group ASC",
                    '%Y-%m-%d',
                    $format,
                    $start_date,
                    $end_date,
                    $format,
                    $start_date,
                    $end_date
                ));
                break;
            case 'revenues':
                $table_name = $wpdb->prefix . 'bookgpt_conversions';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE_FORMAT(created_at, %s) as date_group, 
                            SUM(value) as value 
                     FROM {$table_name} 
                     WHERE created_at BETWEEN %s AND %s 
                     GROUP BY date_group 
                     ORDER BY date_group ASC",
                    $format,
                    $start_date,
                    $end_date
                ));
                break;
            default:
                wp_send_json_error('Invalid analytics type.');
                return;
        }
        
        // Format data for Chart.js
        $labels = array();
        $values = array();
        
        // Generate all dates in the period for consistent charting
        $period_start = new DateTime($start_date);
        $period_end = new DateTime($end_date);
        $date_interval = new DateInterval('P1' . substr($interval, 0, 1));
        $date_period = new DatePeriod($period_start, $date_interval, $period_end);
        
        $data_map = array();
        foreach ($results as $row) {
            $data_map[$row->date_group] = (float) $row->value;
        }
        
        foreach ($date_period as $date) {
            $date_key = $date->format($format);
            $labels[] = $date->format($labels_format);
            $values[] = isset($data_map[$date_key]) ? (float) $data_map[$date_key] : 0;
        }
        
        wp_send_json_success(array(
            'labels' => $labels,
            'values' => $values
        ));
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics() {
        check_ajax_referer('bookgpt_export_analytics', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }
        
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'interactions';
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        
        global $wpdb;
        
        switch ($type) {
            case 'interactions':
                $table_name = $wpdb->prefix . 'bookgpt_interactions';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE DATE(created_at) BETWEEN %s AND %s ORDER BY created_at DESC",
                    $start_date,
                    $end_date
                ));
                $filename = 'bookgpt-interactions-' . $start_date . '-to-' . $end_date . '.csv';
                $headers = array('ID', 'Session ID', 'User Input', 'Bot Response', 'Book Title', 'Book Author', 'Book ISBN', 'Created At');
                break;
            case 'conversions':
                $table_name = $wpdb->prefix . 'bookgpt_conversions';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE DATE(created_at) BETWEEN %s AND %s ORDER BY created_at DESC",
                    $start_date,
                    $end_date
                ));
                $filename = 'bookgpt-conversions-' . $start_date . '-to-' . $end_date . '.csv';
                $headers = array('ID', 'Session ID', 'Book Title', 'Book Author', 'Book ISBN', 'Value', 'Amazon ID', 'Created At');
                break;
            case 'api_usage':
                $table_name = $wpdb->prefix . 'bookgpt_api_usage';
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE DATE(created_at) BETWEEN %s AND %s ORDER BY created_at DESC",
                    $start_date,
                    $end_date
                ));
                $filename = 'bookgpt-api-usage-' . $start_date . '-to-' . $end_date . '.csv';
                $headers = array('ID', 'API Type', 'Model', 'Prompt Tokens', 'Completion Tokens', 'Total Tokens', 'Response Time', 'Created At');
                break;
            default:
                wp_die('Invalid export type.');
                break;
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        foreach ($results as $row) {
            $data = array();
            foreach ($row as $value) {
                $data[] = $value;
            }
            fputcsv($output, $data);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Track a chat event
     */
    public function track_event() {
        check_ajax_referer('bookgpt-tracking-nonce', 'nonce');
        
        $options = get_option('bookgpt_options');
        if (!isset($options['enable_analytics']) || $options['enable_analytics'] !== 'yes') {
            wp_send_json_error('Analytics disabled.');
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_input = sanitize_text_field($_POST['user_input']);
        $bot_response = sanitize_textarea_field($_POST['bot_response']);
        $book_title = isset($_POST['book_title']) ? sanitize_text_field($_POST['book_title']) : '';
        $book_author = isset($_POST['book_author']) ? sanitize_text_field($_POST['book_author']) : '';
        $book_isbn = isset($_POST['book_isbn']) ? sanitize_text_field($_POST['book_isbn']) : '';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_interactions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_input' => $user_input,
                'bot_response' => $bot_response,
                'book_title' => $book_title,
                'book_author' => $book_author,
                'book_isbn' => $book_isbn,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to track event.');
        } else {
            wp_send_json_success('Event tracked successfully.');
        }
    }
    
    /**
     * Track a conversion event (affiliate link click)
     */
    public function track_conversion() {
        check_ajax_referer('bookgpt-tracking-nonce', 'nonce');
        
        $options = get_option('bookgpt_options');
        if (!isset($options['enable_analytics']) || $options['enable_analytics'] !== 'yes') {
            wp_send_json_error('Analytics disabled.');
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $book_title = sanitize_text_field($_POST['book_title']);
        $book_author = isset($_POST['book_author']) ? sanitize_text_field($_POST['book_author']) : '';
        $book_isbn = isset($_POST['book_isbn']) ? sanitize_text_field($_POST['book_isbn']) : '';
        $value = isset($_POST['value']) ? floatval($_POST['value']) : 0;
        $amazon_id = isset($_POST['amazon_id']) ? sanitize_text_field($_POST['amazon_id']) : '';
        $conversion_type = isset($_POST['conversion_type']) ? sanitize_text_field($_POST['conversion_type']) : 'click';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_conversions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'book_title' => $book_title,
                'book_author' => $book_author,
                'book_isbn' => $book_isbn,
                'value' => $value,
                'amazon_id' => $amazon_id,
                'conversion_type' => $conversion_type,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to track conversion.');
        } else {
            wp_send_json_success('Conversion tracked successfully.');
        }
    }
    
    /**
     * Get analytics summary data for the dashboard
     * 
     * @param string $period Period to get data for (today, yesterday, week, month)
     * @return array Analytics summary data
     */
    public function get_analytics_summary($period = 'week') {
        global $wpdb;
        
        $interactions_table = $wpdb->prefix . 'bookgpt_interactions';
        $conversions_table = $wpdb->prefix . 'bookgpt_conversions';
        $api_usage_table = $wpdb->prefix . 'bookgpt_api_usage';
        
        switch ($period) {
            case 'today':
                $start_date = date('Y-m-d 00:00:00');
                $end_date = current_time('mysql');
                break;
            case 'yesterday':
                $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $end_date = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case 'week':
                $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
                $end_date = current_time('mysql');
                break;
            case 'month':
                $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
                $end_date = current_time('mysql');
                break;
            default:
                $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
                $end_date = current_time('mysql');
                break;
        }
        
        // Get total conversations
        $total_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$interactions_table} WHERE created_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        // Get total recommendations
        $total_recommendations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$interactions_table} WHERE book_title != '' AND created_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        // Get total clicks (conversions)
        $total_clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$conversions_table} WHERE conversion_type = 'click' AND created_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        // Get click-through rate
        $click_through_rate = 0;
        if ($total_recommendations > 0) {
            $click_through_rate = round(($total_clicks / $total_recommendations) * 100, 2);
        }
        
        // Get verified purchases
        $verified_purchases = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$conversions_table} WHERE conversion_type = 'purchase' AND created_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        // Get purchase conversion rate
        $purchase_rate = 0;
        if ($total_clicks > 0) {
            $purchase_rate = round(($verified_purchases / $total_clicks) * 100, 2);
        }
        
        // Get total revenue
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(value) FROM {$conversions_table} WHERE created_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        // Get tokens used
        $total_tokens = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(total_tokens) FROM {$api_usage_table} WHERE created_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        // Estimate API cost (assuming $0.02 per 1K tokens)
        $api_cost = $total_tokens ? ($total_tokens / 1000) * 0.02 : 0;
        
        // Get ROI
        $roi = 0;
        if ($api_cost > 0) {
            $roi = round((($total_revenue - $api_cost) / $api_cost) * 100, 2);
        }
        
        return array(
            'total_conversations' => $total_conversations ?: 0,
            'total_recommendations' => $total_recommendations ?: 0,
            'total_clicks' => $total_clicks ?: 0,
            'click_through_rate' => $click_through_rate,
            'verified_purchases' => $verified_purchases ?: 0,
            'purchase_rate' => $purchase_rate,
            'total_revenue' => $total_revenue ?: 0,
            'total_tokens' => $total_tokens ?: 0,
            'api_cost' => $api_cost,
            'roi' => $roi,
        );
    }
    
    /**
     * Create analytics tables on plugin activation
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $interactions_table = $wpdb->prefix . 'bookgpt_interactions';
        $conversions_table = $wpdb->prefix . 'bookgpt_conversions';
        $api_usage_table = $wpdb->prefix . 'bookgpt_api_usage';
        
        // Create interactions table
        $sql1 = "CREATE TABLE {$interactions_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL,
            user_input TEXT NULL,
            bot_response TEXT NULL,
            book_title VARCHAR(255) NULL,
            book_author VARCHAR(255) NULL,
            book_isbn VARCHAR(20) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Create conversions table
        $sql2 = "CREATE TABLE {$conversions_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL,
            book_title VARCHAR(255) NOT NULL,
            book_author VARCHAR(255) NULL,
            book_isbn VARCHAR(20) NULL,
            value DECIMAL(10,2) DEFAULT 0,
            amazon_id VARCHAR(255) NULL,
            conversion_type VARCHAR(20) DEFAULT 'click',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Create API usage table
        $sql3 = "CREATE TABLE {$api_usage_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            api_type VARCHAR(50) NOT NULL,
            model VARCHAR(50) NULL,
            prompt_tokens INT UNSIGNED DEFAULT 0,
            completion_tokens INT UNSIGNED DEFAULT 0,
            total_tokens INT UNSIGNED DEFAULT 0,
            response_time DECIMAL(10,2) DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY api_type (api_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
    }
}