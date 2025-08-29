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
        $this->add_hooks();
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
        add_action('save_post', array($this, 'save_event_meta'));
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
        wp_nonce_field('en_save_event_meta', 'en_event_nonce');
        
        $event_date = get_post_meta($post->ID, '_en_event_date', true);
        ?>
        <p>
            <label for="en_event_date">Event Date:</label>
            <input type="date" id="en_event_date" name="en_event_date" value="<?php echo esc_attr($event_date); ?>" />
        </p>
        <?php
    }

    public function save_event_meta($post_id) {
        // Security checks
        if (!isset($_POST['en_event_nonce']) || !wp_verify_nonce($_POST['en_event_nonce'], 'en_save_event_meta')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['en_event_date'])) {
            update_post_meta($post_id, '_en_event_date', sanitize_text_field($_POST['en_event_date']));
        }
    }
    
}

// Initialize the plugin
new EventNinja();