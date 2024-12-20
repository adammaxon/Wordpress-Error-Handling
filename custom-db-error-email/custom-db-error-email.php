<?php
/*
Plugin Name: Custom Database Error Email
Description: Captures database errors and sends an email notification to the administrator.
Version: 1.1
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CustomDBErrorEmail {
    private $recipient_email;

    public function __construct() {
        // Set the recipient email address
        $this->recipient_email = get_option('admin_email'); // Defaults to admin email

        // Hook into WordPress database queries
       // add_filter('query', [$this, 'monitor_db_errors']);
        
        // Optional: Add a daily log email feature
        add_action('daily_debug_email', [$this, 'send_debug_log_email']);
        if (!wp_next_scheduled('daily_debug_email')) {
            wp_schedule_event(time(), 'daily', 'daily_debug_email');
        }
    }

    /**
     * Monitor database errors during queries
     */
    public function monitor_db_errors($query) {
        global $wpdb;

        // Execute query and check for errors
        $result = @$wpdb->query($query); // Suppress warnings to avoid fatal errors
        if (!is_null($wpdb->last_error) && !empty($wpdb->last_error)) {
            $this->send_error_email($wpdb->last_error);
        }

        return $query;
    }

    /**
     * Send error details via email
     */
    private function send_error_email($error_message) {
        if (empty($error_message)) {
            return; // Avoid sending empty emails
        }

        $subject = 'WordPress Database Error Detected';
        $message = "A database error occurred on your WordPress site:\n\n" . $error_message;
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($this->recipient_email, $subject, $message, $headers);
    }

    /**
     * Send the debug log via email (optional)
     */
    public function send_debug_log_email() {
        $log_file = ABSPATH . 'wp-content/debug.log';
        if (file_exists($log_file) && filesize($log_file) > 0) {
            $subject = 'WordPress Debug Log';
            $message = 'Here is the debug log from your WordPress site.';
            $headers = ['Content-Type: text/plain; charset=UTF-8'];

            wp_mail($this->recipient_email, $subject, $message, $headers, $log_file);

            // Clear the log file after emailing
            file_put_contents($log_file, '');
        }
    }
}

// Safely initialize the plugin
function initialize_custom_db_error_email() {
    if (class_exists('wpdb')) {
        new CustomDBErrorEmail();
    }
}
add_action('plugins_loaded', 'initialize_custom_db_error_email');
