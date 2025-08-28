<?php
/**
 * Plugin Name: EventNinja
 * Description: Event management plugin for WordPress
 * Version: 0.1.0
 * Author: Sheikh Mahdi Mesbah
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main EventNinja Class
 */
class EventNinja {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        $this->register_post_type();
    }


    /**
     * Register the event post type
     */
    public function register_post_type() {
        $args = array(
            'public' => true,
            'show_ui' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title', 'editor'),
        );
        
        register_post_type('en_event', $args);
    }
}

// Initialize the plugin
new EventNinja();