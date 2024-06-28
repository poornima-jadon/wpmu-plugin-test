<?php
add_action('rest_api_init', function () {
    register_rest_route('wpmudev/v1', '/scan-posts', array(
        'methods' => 'GET',
        'callback' => 'wpmudev_pm_handle_scan_posts',
     ));
});

function wpmudev_pm_handle_scan_posts() {
    $post_types = array('post', 'page'); // Add more post types as needed

    $args = array(
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            update_post_meta(get_the_ID(), 'wpmudev_test_last_scan', current_time('timestamp'));
        }
        wp_reset_postdata();

        return rest_ensure_response('Posts scanned successfully.');
    } else {
        return new WP_Error('no_posts_found', 'No posts found to scan.', array('status' => 404));
    }
}
function wpmudev_pm_schedule_daily_maintenance() {
    if (!wp_next_scheduled('wpmudev_pm_daily_maintenance_event')) {
        wp_schedule_event(time(), 'daily', 'wpmudev_pm_daily_maintenance_event');
    }
}
add_action('wp', 'wpmudev_pm_schedule_daily_maintenance');

// Hook into the scheduled event
add_action('wpmudev_pm_daily_maintenance_event', 'wpmudev_pm_run_daily_maintenance');

// Function to run daily maintenance
function wpmudev_pm_run_daily_maintenance() {
    // Run the scan posts function
    wpmudev_pm_handle_scan_posts();
}
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    // Register WP-CLI command
    WP_CLI::add_command( 'scan-posts', 'wpmudev_pm_handle_scan_posts' );
}
?>