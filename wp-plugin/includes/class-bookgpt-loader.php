<?php

/**
 * The core loader class for the plugin.
 */
class BookGPT_Loader {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     * @var      array    $filters    The filters registered with WordPress.
     */
    protected $actions;
    protected $filters;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();

        $this->load_dependencies();
    }

    /**
     * Define the hooks related to the admin area functionality
     */
    private function define_admin_hooks() {
        $plugin_admin = new BookGPT_Admin();
        
        // Admin menu and settings
        $this->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // Admin scripts and styles
        $this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Ajax handlers for admin dashboard
        $this->add_action('wp_ajax_bookgpt_get_analytics', $plugin_admin, 'ajax_get_analytics');
        $this->add_action('wp_ajax_bookgpt_update_backend_logic', $plugin_admin, 'ajax_update_backend_logic');
        $this->add_action('wp_ajax_bookgpt_test_api', $plugin_admin, 'ajax_test_api_connection');
    }

    /**
     * Define the hooks related to the public-facing functionality
     */
    private function define_public_hooks() {
        $plugin_public = new BookGPT_Public();
        
        // Public scripts and styles
        $this->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Add chat widget to footer
        $this->add_action('wp_footer', $plugin_public, 'render_chat_widget');
        
        // Register shortcodes
        $this->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Ajax handlers for frontend tracking
        $this->add_action('wp_ajax_bookgpt_track_interaction', $plugin_public, 'ajax_track_interaction');
        $this->add_action('wp_ajax_nopriv_bookgpt_track_interaction', $plugin_public, 'ajax_track_interaction');
        
        $this->add_action('wp_ajax_bookgpt_track_book_click', $plugin_public, 'ajax_track_book_click');
        $this->add_action('wp_ajax_nopriv_bookgpt_track_book_click', $plugin_public, 'ajax_track_book_click');
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Load dependencies if needed
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }

    /**
     * Run the loader to execute all the hooks with WordPress.
     */
    public function run() {
        // Initialize admin and public hooks
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
        // Register all hooks with WordPress
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}