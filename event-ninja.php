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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate')); 
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
        $event_time = get_post_meta($post->ID, '_en_event_time', true);
        $event_location = get_post_meta($post->ID, '_en_event_location', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="en_event_date">Event Date</label></th>
                <td><input type="date" id="en_event_date" name="en_event_date" value="<?php echo esc_attr($event_date); ?>" /></td>
            </tr>
            <tr>
                <th><label for="en_event_time">Event Time</label></th>
                <td><input type="time" id="en_event_time" name="en_event_time" value="<?php echo esc_attr($event_time); ?>" /></td>
            </tr>
            <tr>
                <th><label for="en_event_location">Location</label></th>
                <td><input type="text" id="en_event_location" name="en_event_location" value="<?php echo esc_attr($event_location); ?>" /></td>
            </tr>
        </table>
        <?php
    }


    public function save_event_meta($post_id) {
        if (!isset($_POST['en_event_nonce']) || !wp_verify_nonce($_POST['en_event_nonce'], 'en_save_event_meta')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['en_event_date'])) {
            update_post_meta($post_id, '_en_event_date', sanitize_text_field($_POST['en_event_date']));
        }
        if (isset($_POST['en_event_time'])) {
            update_post_meta($post_id, '_en_event_time', sanitize_text_field($_POST['en_event_time']));
        }
        if (isset($_POST['en_event_location'])) {
            update_post_meta($post_id, '_en_event_location', sanitize_text_field($_POST['en_event_location']));
        }
    }



    /**
     * Plugin activation
     */
    public function activate() {
        $this->register_post_type();
        $this->create_table();
        flush_rewrite_rules();
    }


    /**
 * Create registrations table
 */
    private function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_registrations';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            user_name varchar(100) NOT NULL,
            user_email varchar(100) NOT NULL,
            registration_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
}

// Initialize the plugin
new EventNinja();