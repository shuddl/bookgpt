<?php

/**
 * The public-facing functionality of the plugin.
 */
class BookGPT_Public {

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style('bookgpt-public', BOOKGPT_PLUGIN_URL . 'public/css/bookgpt-public.css', array(), BOOKGPT_VERSION, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script('bookgpt-public', BOOKGPT_PLUGIN_URL . 'public/js/bookgpt-public.js', array('jquery'), BOOKGPT_VERSION, true);
        
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
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode('bookgpt_widget', array($this, 'widget_shortcode'));
        add_shortcode('bookgpt_inline', array($this, 'inline_widget_shortcode'));
    }

    /**
     * Widget shortcode callback.
     */
    public function widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => '',
            'position' => '',
            'color' => '',
        ), $atts, 'bookgpt_widget');
        
        // Return empty div with data attributes - JS will handle initialization
        return '<div class="bookgpt-shortcode" 
                    data-type="widget"
                    data-title="' . esc_attr($atts['title']) . '" 
                    data-position="' . esc_attr($atts['position']) . '"
                    data-color="' . esc_attr($atts['color']) . '"
                ></div>';
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