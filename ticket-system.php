<?php
/*
Plugin Name: Ticket System
Description: Custom ticket booking system
Version: 1.0
*/

// Include files
require_once plugin_dir_path(__FILE__) . 'screenings.php';
require_once plugin_dir_path(__FILE__) . 'form.php';
require_once plugin_dir_path(__FILE__) . 'handler.php';

// Set default seats ONLY once on plugin activation
function sts_set_default_seats() {
    // Migrate hardcoded screenings to database if not already saved
    if (!get_option('sts_screenings')) {
        $screenings = sts_get_screenings();
        update_option('sts_screenings', $screenings);
    }

    $screenings = sts_get_screenings();

    foreach ($screenings as $key => $data) {
        if (get_option("remaining_seats_$key") === false) {
            update_option("remaining_seats_$key", 200);
        }
    }
}
register_activation_hook(__FILE__, 'sts_set_default_seats');

/**
 * Migrate screenings for already-activated plugins
 * Runs on admin_init so it works even if plugin was activated before this code was added
 */
function sts_migrate_screenings() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if migration has already been done
    if (get_transient('sts_migration_done')) {
        return;
    }

    // Migrate hardcoded screenings to database if not already saved
    if (!get_option('sts_screenings')) {
        $screenings = sts_get_screenings();
        update_option('sts_screenings', $screenings);
    }

    // Mark migration as done (transient lasts 1 hour)
    set_transient('sts_migration_done', true, HOUR_IN_SECONDS);
}
add_action('admin_init', 'sts_migrate_screenings');

// Register shortcode
add_shortcode('ticket_form', 'sts_render_ticket_form');

// Handle form submission
add_action('init', 'sts_handle_ticket_submission');

// Admin page
add_action('admin_menu', 'sts_register_admin_menu');

/**
 * Register admin menu page
 */
function sts_register_admin_menu() {
    add_submenu_page(
        'tools.php',
        'Manage Screenings',
        'Manage Screenings',
        'manage_options',
        'sts_manage_screenings',
        'sts_render_admin_page'
    );
}

/**
 * Handle admin actions (update seats, delete screening)
 */
function sts_handle_admin_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle seat update
    if (isset($_POST['sts_action']) && $_POST['sts_action'] === 'update_seats' && isset($_POST['screening_key'])) {
        check_admin_referer('sts_update_seats_nonce');
        
        $key = sanitize_text_field($_POST['screening_key']);
        $new_seats = intval($_POST['remaining_seats']);
        
        update_option("remaining_seats_$key", $new_seats);
        
        // Clear sold out flag if seats > 0
        if ($new_seats > 0) {
            delete_option("screening_full_$key");
        }
        
        $_GET['sts_message'] = 'updated';
    }

    // Handle screening deletion
    if (isset($_POST['sts_action']) && $_POST['sts_action'] === 'delete_screening' && isset($_POST['screening_key'])) {
        check_admin_referer('sts_delete_screening_nonce');
        
        $key = sanitize_text_field($_POST['screening_key']);
        $screenings = get_option('sts_screenings', []);
        
        if (isset($screenings[$key])) {
            unset($screenings[$key]);
            update_option('sts_screenings', $screenings);
            wp_cache_delete('sts_screenings', 'options');
            
            // Clean up seat count and full flag
            delete_option("remaining_seats_$key");
            delete_option("screening_full_$key");
            
            $_GET['sts_message'] = 'deleted';
        }
    }

    // Handle add screening
    if (isset($_POST['sts_action']) && $_POST['sts_action'] === 'add_screening') {
        check_admin_referer('sts_add_screening_nonce');
        
        $name = sanitize_text_field($_POST['screening_name']);
        $key = sanitize_text_field($_POST['screening_key']);
        $date = sanitize_text_field($_POST['screening_date']);
        $location = sanitize_text_field($_POST['screening_location']);
        $initial_seats = intval($_POST['initial_seats']);
        
        // Validate inputs
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Screening name is required.';
        }
        
        if (empty($key)) {
            $errors[] = 'Short key is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            $errors[] = 'Short key can only contain letters, numbers, and underscores.';
        }
        
        if (empty($date)) {
            $errors[] = 'Date is required.';
        }
        
        if (empty($location)) {
            $errors[] = 'Location is required.';
        }
        
        if ($initial_seats <= 0) {
            $errors[] = 'Initial seat count must be greater than 0.';
        }
        
        $screenings = get_option('sts_screenings', []);
        
        if (isset($screenings[$key])) {
            $errors[] = 'A screening with this short key already exists.';
        }
        
        if (!empty($errors)) {
            $_GET['sts_error'] = implode(' ', $errors);
        } else {
            $screenings[$key] = [
                'name' => $name,
                'date' => $date,
                'location' => $location
            ];
            
            update_option('sts_screenings', $screenings);
            update_option("remaining_seats_$key", $initial_seats);
            
            $_GET['sts_message'] = 'added';
        }
    }
}

add_action('admin_init', 'sts_handle_admin_actions');

/**
 * Render admin page
 */
function sts_render_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $screenings = get_option('sts_screenings', []);
    $message = isset($_GET['sts_message']) ? sanitize_text_field($_GET['sts_message']) : '';
    $error = isset($_GET['sts_error']) ? sanitize_text_field($_GET['sts_error']) : '';
    ?>
    <div class="wrap">
        <h1>Manage Screenings</h1>

        <?php if ($message === 'updated'): ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Success:</strong> Seat count updated.</p>
            </div>
        <?php elseif ($message === 'deleted'): ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Success:</strong> Screening removed.</p>
            </div>
        <?php elseif ($message === 'added'): ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Success:</strong> New screening added.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="notice notice-error">
                <p><strong>Error:</strong> <?php echo esc_html($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Current Screenings Section -->
        <h2>Current Screenings</h2>
        <?php if (!empty($screenings)): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Screening Name</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Seats Remaining</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($screenings as $key => $data): ?>
                        <?php
                        $remaining = (int) get_option("remaining_seats_$key", 200);
                        $is_sold_out = $remaining <= 0;
                        ?>
                        <tr>
                            <td><?php echo esc_html($data['name']); ?></td>
                            <td><?php echo esc_html($data['date']); ?></td>
                            <td><?php echo esc_html($data['location']); ?></td>
                            <td>
                                <form method="post" style="display:inline-flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="sts_action" value="update_seats">
                                    <input type="hidden" name="screening_key" value="<?php echo esc_attr($key); ?>">
                                    <?php wp_nonce_field('sts_update_seats_nonce'); ?>
                                    <input type="number" name="remaining_seats" value="<?php echo esc_attr($remaining); ?>" min="0" style="width: 80px; padding: 4px;">
                                    <button type="submit" class="button button-primary" style="padding: 4px 12px;">Update</button>
                                </form>
                            </td>
                            <td>
                                <?php if ($is_sold_out): ?>
                                    <span style="background: #d63638; color: white; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;">Sold Out</span>
                                <?php else: ?>
                                    <span style="background: #00a32a; color: white; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;">Available</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="sts_action" value="delete_screening">
                                    <input type="hidden" name="screening_key" value="<?php echo esc_attr($key); ?>">
                                    <?php wp_nonce_field('sts_delete_screening_nonce'); ?>
                                    <button type="submit" class="button button-link-delete" onclick="return confirm('Are you sure you want to delete this screening?');">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No screenings added yet.</p>
        <?php endif; ?>

        <!-- Add New Screening Section -->
        <h2 style="margin-top: 40px;">Add New Screening</h2>
        <form method="post" style="max-width: 600px;">
            <input type="hidden" name="sts_action" value="add_screening">
            <?php wp_nonce_field('sts_add_screening_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="screening_name">Screening Name</label></th>
                    <td><input type="text" name="screening_name" id="screening_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="screening_key">Short Key</label></th>
                    <td>
                        <input type="text" name="screening_key" id="screening_key" class="regular-text" required>
                        <p class="description">Unique identifier (e.g., wd, kc, sf1). Letters, numbers, and underscores only. No spaces.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="screening_date">Date</label></th>
                    <td><input type="text" name="screening_date" id="screening_date" class="regular-text" placeholder="e.g. Apr 10, 7PM" required></td>
                </tr>
                <tr>
                    <th><label for="screening_location">Location</label></th>
                    <td><input type="text" name="screening_location" id="screening_location" class="regular-text" placeholder="e.g. Hall A" required></td>
                </tr>
                <tr>
                    <th><label for="initial_seats">Initial Seat Count</label></th>
                    <td><input type="number" name="initial_seats" id="initial_seats" class="regular-text" min="1" value="200" required></td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Add Screening</button>
            </p>
        </form>
    </div>
    <?php
}