<?php
/**
 * Plugin Name: EventNinja
 * Description: Event management plugin for WordPress
 * Version: 1.0.0
 * Author: Sheikh Mahdi Mesbah
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

//constants
define('EN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EN_VERSION', '1.0.0');


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
            'view_item' => 'View Event',
            'all_items' => 'All Events',
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title', 'editor', 'thumbnail'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'events'),
        );
        
        register_post_type('en_event', $args);
    }
    

    /**
     * Add wordpress hooks
     */
    public function add_hooks() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_event_meta'));

        //front end hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'add_event_details_and_form'));
        add_action('template_redirect', array($this, 'handle_registration'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'en_event_details',
            'Event Details',
            array($this, 'event_details_callback'),
            'en_event',
            'normal',
            'high'
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
        $event_capacity = get_post_meta($post->ID, '_en_event_capacity', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="en_event_date">Event Date *</label></th>
                <td><input type="date" id="en_event_date" name="en_event_date" value="<?php echo esc_attr($event_date); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="en_event_time">Event Time</label></th>
                <td><input type="time" id="en_event_time" name="en_event_time" value="<?php echo esc_attr($event_time); ?>" /></td>
            </tr>
            <tr>
                <th><label for="en_event_location">Location</label></th>
                <td><input type="text" id="en_event_location" name="en_event_location" value="<?php echo esc_attr($event_location); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="en_event_capacity">Event Capacity</label></th>
                <td>
                    <input type="number" id="en_event_capacity" name="en_event_capacity" value="<?php echo esc_attr($event_capacity); ?>" min="1" class="small-text" />
                    <p class="description">Leave empty for unlimited capacity</p>
                </td>
            </tr>
        </table>
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
        
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        // Save meta fields
        if (isset($_POST['en_event_date'])) {
            update_post_meta($post_id, '_en_event_date', sanitize_text_field($_POST['en_event_date']));
        }
        if (isset($_POST['en_event_time'])) {
            update_post_meta($post_id, '_en_event_time', sanitize_text_field($_POST['en_event_time']));
        }
        if (isset($_POST['en_event_location'])) {
            update_post_meta($post_id, '_en_event_location', sanitize_text_field($_POST['en_event_location']));
        }
        if (isset($_POST['en_event_capacity'])) {
            $capacity = intval($_POST['en_event_capacity']);
            update_post_meta($post_id, '_en_event_capacity', $capacity > 0 ? $capacity : '');
        }
    }


    /**
     * Get registration count for an event
     */
    private function get_registration_count($event_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'en_registrations';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d",
            $event_id
        ));
        
        return intval($count);
    }


    /**
     * Add admin menu for registrations
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=en_event',
            'Event Registrations',
            'Registrations',
            'manage_options',
            'en-registrations',
            array($this, 'admin_registrations_page')
        );
    }
    
    /**
     * Admin registrations page
     */
    public function admin_registrations_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_registrations';
        
        // Get all registrations with event titles
        $registrations = $wpdb->get_results(
            "SELECT r.*, p.post_title as event_title 
             FROM $table_name r 
             LEFT JOIN {$wpdb->posts} p ON r.event_id = p.ID 
             ORDER BY r.registration_date DESC"
        );
        
        ?>
        <div class="wrap">
            <h1>Event Registrations</h1>
            
            <?php if (empty($registrations)): ?>
                <p>No registrations found.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <tr>
                                <td><?php echo esc_html($registration->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($registration->event_title); ?></strong><br>
                                    <small>Event ID: <?php echo esc_html($registration->event_id); ?></small>
                                </td>
                                <td><?php echo esc_html($registration->user_name); ?></td>
                                <td><?php echo esc_html($registration->user_email); ?></td>
                                <td><?php echo esc_html(date('F j, Y g:i A', strtotime($registration->registration_date))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p><strong>Total Registrations:</strong> <?php echo count($registrations); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('en-style', EN_PLUGIN_URL . 'style.css', array(), EN_VERSION);
    }


    /**
     * Add event details and registration form to event content
     */
    public function add_event_details_and_form($content) {
        if (is_singular('en_event')) {
            global $post;
            
            // Add event details first
            $content .= $this->get_event_details($post->ID);
            
            $event_date = get_post_meta($post->ID, '_en_event_date', true);
            
            // Only show form for future events
            if ($event_date && strtotime($event_date) >= strtotime('today')) {
                $content .= $this->get_registration_form($post->ID);
            } else {
                $content .= '<div class="en-notice"><p>Registration for this event has closed.</p></div>';
            }
        }
        
        return $content;
    }
    
    /**
     * Generate event details HTML
     */
    private function get_event_details($event_id) {
        $event_date = get_post_meta($event_id, '_en_event_date', true);
        $event_time = get_post_meta($event_id, '_en_event_time', true);
        $event_location = get_post_meta($event_id, '_en_event_location', true);
        $event_capacity = get_post_meta($event_id, '_en_event_capacity', true);
        
        if (!$event_date && !$event_time && !$event_location && !$event_capacity) {
            return ''; // No details to show
        }
        
        ob_start();
        ?>
        <div class="en-event-meta">
            <h4>Event Details</h4>
            
            <?php if ($event_date): ?>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($event_date)); ?></p>
            <?php endif; ?>
            
            <?php if ($event_time): ?>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event_time)); ?></p>
            <?php endif; ?>
            
            <?php if ($event_location): ?>
                <p><strong>Location:</strong> <?php echo esc_html($event_location); ?></p>
            <?php endif; ?>
            
            <?php if ($event_capacity): ?>
                <?php 
                $registered_count = $this->get_registration_count($event_id);
                $available_spots = $event_capacity - $registered_count;
                ?>
                <p><strong>Capacity:</strong> <?php echo $event_capacity; ?> people</p>
                <p><strong>Available Spots:</strong> 
                    <?php if ($available_spots > 0): ?>
                        <span style="color: green;"><?php echo $available_spots; ?> remaining</span>
                    <?php else: ?>
                        <span style="color: red;">Event is full</span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate registration form HTML
     */
    private function get_registration_form($event_id) {
        // Check capacity first
        $capacity = get_post_meta($event_id, '_en_event_capacity', true);
        if ($capacity) {
            $current_registrations = $this->get_registration_count($event_id);
            if ($current_registrations >= $capacity) {
                return '<div class="en-notice en-error"><p>This event is fully booked. No more registrations accepted.</p></div>';
            }
        }
        
        ob_start();
        ?>
        <div class="en-registration-form">
            <h3>Register for this Event</h3>
            
            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                <div class="en-notice en-success">
                    <p>Registration successful! Thank you for signing up.</p>
                </div>
            <?php elseif (isset($_GET['registered']) && $_GET['registered'] == 'error'): ?>
                <div class="en-notice en-error">
                    <p>Registration failed. Please try again.</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('en_register_event', 'en_registration_nonce'); ?>
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                <input type="hidden" name="action" value="en_register_event">
                
                <div class="en-form-row">
                    <label for="en_user_name">Full Name *</label>
                    <input type="text" id="en_user_name" name="en_user_name" required>
                </div>
                
                <div class="en-form-row">
                    <label for="en_user_email">Email Address *</label>
                    <input type="email" id="en_user_email" name="en_user_email" required>
                </div>
                
                <div class="en-form-row">
                    <button type="submit" class="en-button">Register Now</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }


    /**
     * Handle registration form submission
     */
    public function handle_registration() {
        if (isset($_POST['action']) && $_POST['action'] == 'en_register_event') {
            
            // Security check
            if (!wp_verify_nonce($_POST['en_registration_nonce'], 'en_register_event')) {
                return;
            }
            
            $event_id = intval($_POST['event_id']);
            $user_name = sanitize_text_field($_POST['en_user_name']);
            $user_email = sanitize_email($_POST['en_user_email']);
            
            if ($event_id && $user_name && $user_email) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'en_registrations';
                
                // Check if already registered
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND user_email = %s",
                    $event_id, $user_email
                ));
                
                if ($existing > 0) {
                    wp_redirect(add_query_arg('registered', 'duplicate', get_permalink($event_id)));
                    exit;
                }
                
                // Check capacity
                $capacity = get_post_meta($event_id, '_en_event_capacity', true);
                if ($capacity) {
                    $current_registrations = $this->get_registration_count($event_id);
                    if ($current_registrations >= $capacity) {
                        wp_redirect(add_query_arg('registered', 'full', get_permalink($event_id)));
                        exit;
                    }
                }
                
                // Insert registration
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'event_id' => $event_id,
                        'user_name' => $user_name,
                        'user_email' => $user_email,
                        'registration_date' => current_time('mysql'),
                    ),
                    array('%d', '%s', '%s', '%s')
                );
                
                if ($result) {
                    wp_redirect(add_query_arg('registered', 'success', get_permalink($event_id)));
                } else {
                    wp_redirect(add_query_arg('registered', 'error', get_permalink($event_id)));
                }
                exit;
            }
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


// Admin notice for successful activation
add_action('admin_notices', function() {
    if (get_transient('en_activated')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>🥷 EventNinja activated!</strong> Ready to manage events stealthily and efficiently.</p>';
        echo '</div>';
        delete_transient('en_activated');
    }
});

// Set activation notice
register_activation_hook(__FILE__, function() {
    set_transient('en_activated', true, 60);
});
