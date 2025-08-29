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
        $labels = array(
            'name' => 'Events',
            'singular_name' => 'Event',
            'menu_name' => 'Events',
            'add_new' => 'Add New Event',
            'edit_item' => 'Edit Event',
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title', 'editor'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'events'),
        );
        
        register_post_type('en_event', $args);
    }
    

    /**
     * Add wordpress hooks
     */
    public function add_hooks() {
        add_action('add_meta_boxes', array($this, 'add_event_meta_boxes'));
    }

    /**
     * add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'en_event_details',
            'Event Details',
            array($this, 'event_details_callback'),
            'en_event'
        );
    }   

    /**
     * Event details meta box
     */
    public function event_details_callback($post) {
        $event_date = get_post_meta($post->ID, '_en_event_date', true);
        ?>
        <p>
            <label for="en_event_date">Event Date:</label>
            <input type="date" id="en_event_date" name="en_event_date" value="<?php echo esc_attr($event_date); ?>" />
        </p>
        <?php
    }
}

// Initialize the plugin
new EventNinja();