<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    BookGPT
 * @subpackage BookGPT/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the public-facing side.
 *
 * @package    BookGPT
 * @subpackage BookGPT/public
 */
class BookGPT_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('bookgpt-public', BOOKGPT_PLUGIN_URL . 'public/css/bookgpt-public.css', array(), BOOKGPT_VERSION, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $options = get_option('bookgpt_options');
        $api_url = isset($options['api_url']) ? $options['api_url'] : '';
        $amazon_tag = isset($options['amazon_associate_tag']) ? $options['amazon_associate_tag'] : '';
        $enable_tracking = isset($options['enable_analytics']) && $options['enable_analytics'] == 1;
        
        // Only enqueue scripts if tracking is enabled
        if ($enable_tracking) {
            // Register the tracking worker script - important to register this first
            wp_register_script(
                $this->plugin_name . '-worker', 
                plugin_dir_url(__FILE__) . 'js/bookgpt-worker.js', 
                array(), 
                $this->version, 
                false
            );
            
            // Enqueue jQuery
            wp_enqueue_script('jquery');
            
            // Enqueue the tracking script
            wp_enqueue_script(
                $this->plugin_name . '-tracking',
                plugin_dir_url(__FILE__) . 'js/bookgpt-tracking.js',
                array('jquery'),
                $this->version,
                true
            );
            
            // Localize the tracking script with necessary data
            wp_localize_script(
                $this->plugin_name . '-tracking',
                'bookgpt_tracking',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('bookgpt_tracking_nonce'),
                    'amazon_tag' => $amazon_tag,
                    'worker_url' => plugin_dir_url(__FILE__) . 'js/bookgpt-worker.js'
                )
            );
        }

        // Get plugin options
        $options = get_option('bookgpt_options', array());
        
        // Set default values if empty
        $api_url = !empty($options['api_url']) ? $options['api_url'] : 'https://your-bookgpt-api.vercel.app/api/chat';
        $amazon_tag = !empty($options['amazon_associate_tag']) ? $options['amazon_associate_tag'] : 'bookgpt-20';
        $enable_analytics = isset($options['enable_analytics']) ? $options['enable_analytics'] === 'yes' : true;
        $widget_title = !empty($options['chat_widget_title']) ? $options['chat_widget_title'] : 'Book Recommendations';
        $widget_position = !empty($options['chat_widget_position']) ? $options['chat_widget_position'] : 'bottom-right';
        $widget_color = !empty($options['chat_widget_color']) ? $options['chat_widget_color'] : '#3b82f6';
        
        // Pass variables to JavaScript
        wp_localize_script('bookgpt-public', 'bookgpt_vars', array(
            'api_url' => $api_url,
            'amazon_tag' => $amazon_tag,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bookgpt-public-nonce'),
            'enable_analytics' => $enable_analytics,
            'widget_title' => $widget_title,
            'widget_position' => $widget_position,
            'widget_color' => $widget_color,
            'user_id' => get_current_user_id(),
            'page_url' => get_permalink(),
            'is_logged_in' => is_user_logged_in()
        ));
    }

    /**
     * Register the shortcode for displaying the chat widget.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('bookgpt_widget', array($this, 'render_widget_shortcode'));
        add_shortcode('bookgpt_inline', array($this, 'inline_widget_shortcode'));
    }

    /**
     * Render the widget shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    The shortcode attributes.
     * @return   string    The rendered shortcode content.
     */
    public function render_widget_shortcode($atts) {
        $options = get_option('bookgpt_options');
        $api_url = isset($options['api_url']) ? $options['api_url'] : '';
        
        if (empty($api_url)) {
            return '<p>BookGPT API URL is not configured. Please go to BookGPT Settings to set it up.</p>';
        }
        
        // Extract and sanitize attributes
        $atts = shortcode_atts(
            array(
                'theme' => 'light',
                'position' => 'bottom-right',
                'width' => '380px',
                'height' => '550px',
            ),
            $atts,
            'bookgpt_widget'
        );
        
        // Include the deployment config
        $config_file = plugin_dir_path(dirname(__FILE__)) . 'deployment_config.json';
        $config = array();
        if (file_exists($config_file)) {
            $config_json = file_get_contents($config_file);
            $config = json_decode($config_json, true);
        }
        
        // Get the correctly configured API URL
        $api_url = isset($config['backend_url']) ? $config['backend_url'] . '/api/chat' : $api_url;
        
        // Generate a placeholder for the widget
        $widget_html = '<div id="bookgpt-widget-placeholder" data-api-url="' . esc_attr($api_url) . '"';
        
        // Add additional attributes
        foreach ($atts as $key => $value) {
            $widget_html .= ' data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        $widget_html .= '></div>';
        
        // Add script tag to load the widget
        $widget_html .= '<script src="' . esc_url($api_url) . '"></script>';
        
        return $widget_html;
    }

    /**
     * Inline widget shortcode callback.
     */
    public function inline_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => '',
            'color' => '',
            'height' => '500px',
        ), $atts, 'bookgpt_inline');
        
        // Return inline container - JS will handle initialization
        return '<div class="bookgpt-shortcode bookgpt-inline-container" 
                    data-type="inline"
                    data-title="' . esc_attr($atts['title']) . '"
                    data-color="' . esc_attr($atts['color']) . '"
                    style="height:' . esc_attr($atts['height']) . '"
                ></div>';
    }

    /**
     * Render the chat widget in footer.
     */
    public function render_chat_widget() {
        $options = get_option('bookgpt_options', array());
        
        // Don't render if disabled
        if (isset($options['disable_widget']) && $options['disable_widget'] === 'yes') {
            return;
        }
        
        // Widget container will be populated via JavaScript
        echo '<div id="bookgpt-widget-container"></div>';
    }

    /**
     * Handle AJAX request to track chat interactions.
     *
     * @since    1.0.0
     */
    public function track_event() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bookgpt_tracking_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get data from request
        $user_message = isset($_POST['user_input']) ? sanitize_text_field($_POST['user_input']) : '';
        $bot_response = isset($_POST['bot_response']) ? sanitize_textarea_field($_POST['bot_response']) : '';
        $books_recommended = isset($_POST['books']) ? sanitize_textarea_field($_POST['books']) : '';
        $user_id = get_current_user_id(); // 0 if not logged in
        
        // Get user IP address
        $ip_address = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        // Get page URL
        $page_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '';
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_interactions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'session_id' => isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '',
                'user_id' => $user_id,
                'ip_address' => $ip_address,
                'page_url' => $page_url,
                'user_message' => $user_message,
                'bot_response' => $bot_response,
                'book_title' => isset($_POST['book_title']) ? sanitize_text_field($_POST['book_title']) : '',
                'book_author' => isset($_POST['book_author']) ? sanitize_text_field($_POST['book_author']) : '',
                'book_isbn' => isset($_POST['book_isbn']) ? sanitize_text_field($_POST['book_isbn']) : '',
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result) {
            wp_send_json_success('Interaction tracked successfully');
        } else {
            wp_send_json_error('Failed to track interaction');
        }
    }
    
    /**
     * Handle AJAX request to track conversions.
     *
     * @since    1.0.0
     */
    public function track_conversion() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bookgpt_tracking_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get data from request
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $book_title = isset($_POST['book_title']) ? sanitize_text_field($_POST['book_title']) : '';
        $amazon_id = isset($_POST['amazon_id']) ? sanitize_text_field($_POST['amazon_id']) : '';
        $conversion_type = isset($_POST['conversion_type']) ? sanitize_text_field($_POST['conversion_type']) : 'click';
        $value = isset($_POST['value']) ? floatval($_POST['value']) : 0;
        $user_id = get_current_user_id(); // 0 if not logged in
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_conversions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'book_title' => $book_title,
                'amazon_id' => $amazon_id,
                'conversion_type' => $conversion_type,
                'value' => $value,
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result) {
            wp_send_json_success('Conversion tracked successfully');
        } else {
            wp_send_json_error('Failed to track conversion');
        }
    }

    /**
     * Ajax handler for tracking chat interactions
     */
    public function ajax_track_interaction() {
        // Verify nonce
        check_ajax_referer('bookgpt-public-nonce', 'nonce');
        
        $options = get_option('bookgpt_options', array());
        
        // Only track if analytics is enabled
        if (isset($options['enable_analytics']) && $options['enable_analytics'] !== 'yes') {
            wp_send_json_success('Analytics disabled');
            return;
        }
        
        // Get analytics data from request
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $user_message = isset($_POST['user_message']) ? sanitize_text_field($_POST['user_message']) : '';
        $bot_response = isset($_POST['bot_response']) ? sanitize_textarea_field($_POST['bot_response']) : '';
        $books_recommended = isset($_POST['books']) ? sanitize_textarea_field($_POST['books']) : '';
        $user_id = get_current_user_id(); // 0 if not logged in
        
        // Get user IP address
        $ip_address = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        // Get page URL
        $page_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '';
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_interactions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_id' => $user_id > 0 ? $user_id : null,
                'user_message' => $user_message,
                'bot_response' => $bot_response,
                'books_recommended' => $books_recommended,
                'ip_address' => $ip_address,
                'page_url' => $page_url,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success('Interaction tracked successfully');
        } else {
            wp_send_json_error('Failed to track interaction');
        }
    }

    /**
     * Ajax handler for tracking book clicks
     */
    public function ajax_track_book_click() {
        // Verify nonce
        check_ajax_referer('bookgpt-public-nonce', 'nonce');
        
        $options = get_option('bookgpt_options', array());
        
        // Only track if analytics is enabled
        if (isset($options['enable_analytics']) && $options['enable_analytics'] !== 'yes') {
            wp_send_json_success('Analytics disabled');
            return;
        }
        
        // Get book click data from request
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $book_title = isset($_POST['book_title']) ? sanitize_text_field($_POST['book_title']) : '';
        $book_author = isset($_POST['book_author']) ? sanitize_text_field($_POST['book_author']) : '';
        $amazon_link = isset($_POST['amazon_link']) ? esc_url_raw($_POST['amazon_link']) : '';
        $user_id = get_current_user_id(); // 0 if not logged in
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_conversions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_id' => $user_id > 0 ? $user_id : null,
                'book_title' => $book_title,
                'book_author' => $book_author,
                'amazon_link' => $amazon_link,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success('Book click tracked successfully');
        } else {
            wp_send_json_error('Failed to track book click');
        }
    }
}