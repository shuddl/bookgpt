<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/yourusername/bookgptwp
 * @since      1.0.0
 *
 * @package    BookGPT
 * @subpackage BookGPT/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for loading
 * the public-facing stylesheet and JavaScript.
 *
 * @package    BookGPT
 * @subpackage BookGPT/public
 * @author     Your Name
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
     * @param    string    $plugin_name    The name of the plugin.
     * @param    string    $version        The version of this plugin.
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
        // No separate CSS needed as styles are embedded in the script.js
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Get plugin options
        $options = get_option('bookgpt_options', array());
        
        // Only load scripts if widget is enabled
        if (isset($options['enable_widget']) && $options['enable_widget']) {
            // Check if we have a configured API URL
            if (!empty($options['api_url'])) {
                // Register worker script first
                wp_register_script(
                    'bookgpt-worker', 
                    plugin_dir_url(__FILE__) . 'js/bookgpt-worker.js', 
                    array(), 
                    $this->version, 
                    false
                );
                
                // Register and enqueue main script (will fetch worker.js)
                wp_register_script(
                    'bookgpt', 
                    plugin_dir_url(__FILE__) . 'js/bookgpt-public.js', 
                    array('jquery'), 
                    $this->version, 
                    true
                );
                
                // Pass the API URL and other configuration to the script
                wp_localize_script(
                    'bookgpt', 
                    'bookGptConfig', 
                    array(
                        'apiUrl' => $options['api_url'],
                        'amazonTag' => isset($options['amazon_associate_tag']) ? $options['amazon_associate_tag'] : 'bookgpt-20',
                        'widgetTitle' => isset($options['widget_title']) ? $options['widget_title'] : 'Book Buddy',
                        'analyticsUrl' => admin_url('admin-ajax.php') . '?action=bookgpt_track_analytics',
                        'nonce' => wp_create_nonce('bookgpt_tracking_nonce'),
                        'workerUrl' => plugin_dir_url(__FILE__) . 'js/bookgpt-worker.js',
                        'enableAnalytics' => isset($options['enable_analytics']) && $options['enable_analytics']
                    )
                );
                
                // Enqueue the script
                wp_enqueue_script('bookgpt');
                
                // Enqueue tracking script if analytics are enabled
                if (isset($options['enable_analytics']) && $options['enable_analytics']) {
                    wp_enqueue_script(
                        'bookgpt-tracking', 
                        plugin_dir_url(__FILE__) . 'js/bookgpt-tracking.js', 
                        array('jquery', 'bookgpt'), 
                        $this->version, 
                        true
                    );
                }
            }
        }
    }
    
    /**
     * Register the shortcode for embedding the chat widget
     * 
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('bookgpt', array($this, 'render_bookgpt_shortcode'));
    }
    
    /**
     * Render the BookGPT shortcode
     * 
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   Rendered shortcode HTML
     */
    public function render_bookgpt_shortcode($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(
            array(
                'position' => 'default', // default, inline, fixed
                'theme' => 'light',      // light, dark
                'width' => '100%',       // CSS width value
                'height' => '500px',     // CSS height value
            ),
            $atts,
            'bookgpt'
        );
        
        // Get plugin options
        $options = get_option('bookgpt_options', array());
        
        // Only render if API URL is configured
        if (empty($options['api_url'])) {
            return '<div class="bookgpt-error">BookGPT API not configured. Please set up the API URL in the plugin settings.</div>';
        }
        
        // Determine container class based on position
        $container_class = 'bookgpt-container';
        if ($atts['position'] === 'inline') {
            $container_class .= ' bookgpt-inline';
        } else if ($atts['position'] === 'fixed') {
            $container_class .= ' bookgpt-fixed';
        }
        
        // Add theme class
        $container_class .= ' bookgpt-theme-' . $atts['theme'];
        
        // Build inline styles for container
        $container_style = '';
        if ($atts['position'] === 'inline') {
            $container_style = 'width:' . $atts['width'] . '; height:' . $atts['height'] . ';';
        }
        
        // Build shortcode HTML
        $html = '<div class="' . esc_attr($container_class) . '"';
        if (!empty($container_style)) {
            $html .= ' style="' . esc_attr($container_style) . '"';
        }
        $html .= ' data-position="' . esc_attr($atts['position']) . '"';
        $html .= ' data-theme="' . esc_attr($atts['theme']) . '"';
        $html .= '></div>';
        
        return $html;
    }
    
    /**
     * Handle analytics tracking AJAX endpoint
     * 
     * @since    1.0.0
     */
    public function handle_tracking_request() {
        // Verify nonce
        if (!check_ajax_referer('bookgpt_tracking_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Get tracking data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            wp_send_json_error('Invalid tracking data');
            return;
        }
        
        // Process tracking data
        $analytics = new BookGPT_Analytics();
        $result = $analytics->track_event($data);
        
        if ($result) {
            wp_send_json_success('Event tracked');
        } else {
            wp_send_json_error('Failed to track event');
        }
    }

    /**
     * Send a message to the chat bot
     * 
     * @since    1.0.0
     * @param    string    $message    The message to send
     * @return   array     The response from the chat bot
     */
    public function sendMessageToServer($message) {
        // Get plugin options
        $options = get_option('bookgpt_options', array());
        
        // Check if API URL is configured
        if (empty($options['api_url'])) {
            return array('error' => 'API URL not configured.');
        }
        
        // Prepare request data
        $request_data = array(
            'user_id' => 'wp_user_' . get_current_user_id(),
            'message' => $message
        );
        
        // Send request to chat bot API
        $response = wp_remote_post($options['api_url'], array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data)
        ));
        
        // Check for errors
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        // Parse response
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        // Check for valid response
        if (empty($response_data) || !isset($response_data['bot_message'])) {
            return array('error' => 'Invalid response from chat bot.');
        }
        
        return $response_data;
    }
}
