<?php
/*
Plugin Name: Book a Dome
Description: Manage and display booking demo data with CRUD functionality.
Version: 1.0
Author: Your Name
*/
///insert table Book a Demo form data start
function handle_cf7_ajax_submission() {
    global $wpdb;
  
    if (isset($_POST['name'], $_POST['email'], $_POST['mobile'], $_POST['industry_type'], $_POST['industry'])) {

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $mobile = sanitize_text_field($_POST['mobile']);
        $industry_type = sanitize_text_field($_POST['industry_type']);
        $industry = sanitize_text_field($_POST['industry']);
        $table_name = $wpdb->prefix.'book_demo';
        
        $data = array(
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'industry_type' => $industry_type,
            'industry' => $industry
        );

        $wpdb->insert($table_name, $data);
        
        wp_send_json_success('Data inserted successfully.');
    } else {
        wp_send_json_error('Required fields are missing.');
    }

    wp_die();
}
add_action('wp_ajax_nopriv_handle_cf7_submission', 'handle_cf7_ajax_submission');
add_action('wp_ajax_handle_cf7_submission', 'handle_cf7_ajax_submission');

function enqueue_custom_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('custom-ajax-script', plugin_dir_url(__FILE__) . 'custom.js', array('jquery'), null, true);
    wp_localize_script('custom-ajax-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');
function enqueue_validation_scripts() {
    wp_enqueue_script('jquery-validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js', array('jquery'), null, true);
    wp_enqueue_script('custom-js', plugin_dir_url(__FILE__)  . '/custom.js', array('jquery', 'jquery-validate'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_validation_scripts');




///insert table Book a Demo form data end


function custom_book_demo_menu() {
    add_menu_page(
        'Book Demo',                // Page title
        'Book Demo',                // Menu title
        'manage_options',           // Capability required to access menu item
        'book-demo',                // Menu slug
        'display_book_demo_data',   // Callback function to display content
        'dashicons-welcome-learn-more', // Icon URL or Dashicon class
        12                          // Position in the menu
    );
}
add_action('admin_menu', 'custom_book_demo_menu');

// Display data
function display_book_demo_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'book_demo';

    // Handle export CSV request
    if (isset($_POST['export_csv'])) {
        check_admin_referer('export_csv_nonce', 'export_csv_nonce');
        export_book_demo_data();
    }

    // Retrieve and sanitize filter parameters
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $per_page = 13;
    $offset = ($paged - 1) * $per_page;

    // Prepare conditions and parameters for query
    $conditions = [];
    $params = [];

    if (!empty($search)) {
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        $conditions[] = "(name LIKE %s OR email LIKE %s OR mobile LIKE %s OR industry_type LIKE %s OR industry LIKE %s)";
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
    }

    if (!empty($start_date) && !empty($end_date)) {
        $conditions[] = "created_at BETWEEN %s AND %s";
        $params[] = $start_date;
        $params[] = $end_date;
    } elseif (!empty($start_date)) {
        $conditions[] = "created_at >= %s";
        $params[] = $start_date;
    } elseif (!empty($end_date)) {
        $conditions[] = "created_at <= %s";
        $params[] = $end_date;
    }

    // Construct the main query
    $query = "SELECT * FROM $table_name";
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
    $query .= " LIMIT %d OFFSET %d";

    $params[] = $per_page;
    $params[] = $offset;

    // Prepare and execute the query
    $prepared_query = $wpdb->prepare($query, ...$params);
    $results = $wpdb->get_results($prepared_query, ARRAY_A);

    // Total count query for pagination
    $count_query = "SELECT COUNT(*) FROM $table_name";
    if (!empty($conditions)) {
        $count_query .= " WHERE " . implode(' AND ', $conditions);
    }
    $prepared_count_query = $wpdb->prepare($count_query, ...array_slice($params, 0, -2));
    $total_items = $wpdb->get_var($prepared_count_query);

    // Output HTML for the admin page
    echo '<div class="wrap">';
    echo '<h1>Book Demo</h1>';

    // Export CSV form
    echo '<form method="post" action="">';
    wp_nonce_field('export_csv_nonce', 'export_csv_nonce');
    echo '<input type="submit" style="padding:10px 30px;" name="export_csv" value="Export CSV" class="export_csv">';
    echo '<button class="btn btn-secondary " style="margin: 25px; padding:10px 30px;  "><a href="' . esc_url(admin_url('admin.php?page=book-demo')) . '" style="text-decoration: none;">Reset</a></button>';
    echo '</form>';

    // Search form with jQuery validation
    echo '<p id="error" style="color: red; display: none;"></p>';
    echo '<div style="margin:20px">';
    echo '<form method="get" id="dateForm">';
    echo '<input type="hidden" name="page" value="book-demo">';
    echo '<input type="text" name="s" value="' . esc_attr($search) . '" placeholder="Search...">';
    echo '<label>Start Date</label>';
    echo '<input type="date" id="start_date" name="start_date" value="' . esc_attr($start_date) . '">';
    echo '<label>End Date</label>';
    echo '<input type="date" id="end_date" name="end_date" value="' . esc_attr($end_date) . '">';
    echo '<input type="submit" value="Search" class="button">';
    echo '</form>';
    echo '</div>';

    // Display results table
    if ($results) {
        echo '<table class="widefat fixed" border="1" cellspacing="0">
            <thead style="background-color:grey;">
                <tr>
                    <th class="manage-column column-columnname" scope="col" style="font-size: 30px;">ID</th>
                    <th class="manage-column column-columnname" scope="col" style="font-size: 30px;">Name</th>
                    <th class="manage-column column-columnname" scope="col" style="font-size: 30px;">Email</th>
                    <th class="manage-column column-columnname" scope="col" style="font-size: 30px;">Mobile</th>
                    <th class="manage-column column-columnname" scope="col" style="font-size: 30px;">Industry Type</th>
                    <th class="manage-column column-columnname" scope="col" style="font-size: 30px;">Industry</th>
                    <th class="manage-column column-columnname" scope="col" style="font-size: 30px;">Created at</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($results as $row) {
            echo '<tr>
                <td>' . esc_html($row['id']) . '</td>
                <td>' . esc_html($row['name']) . '</td>
                <td>' . esc_html($row['email']) . '</td>
                <td>' . esc_html($row['mobile']) . '</td>
                <td>' . esc_html($row['industry_type']) . '</td>
                <td>' . esc_html($row['industry']) . '</td>
                <td>' . esc_html($row['created_at']) . '</td>
            </tr>';
        }

        echo '</tbody></table>';

        // Pagination
        $page_links = paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;'),
            'next_text' => __('&raquo;'),
            'total' => ceil($total_items / $per_page),
            'current' => $paged
        ));

        if ($page_links) {
            echo '<div class="tablenav" style="margin-right:900px;"><div class="tablenav-pages" style="margin: 1em 0; padding:20px; ">' . $page_links . '</div></div>';
        }

    } else {
        echo '<p>No data found.</p>';
    }

    echo '</div>';
}

// Export data as CSV
function handle_export_request() {
    if (isset($_POST['export_csv'])) {
        if (!isset($_POST['export_csv_nonce']) || !wp_verify_nonce($_POST['export_csv_nonce'], 'export_csv_nonce')) {
            die('Security check failed');
        }
        export_book_demo_data();
    }
}
add_action('admin_init', 'handle_export_request');

function export_book_demo_data() {
    set_time_limit(0);
    date_default_timezone_set("Europe/London");

    global $wpdb;
    $table_name = $wpdb->prefix . 'book_demo';

    // Retrieve and sanitize filter parameters
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

    // Prepare conditions and parameters for query
    $conditions = [];
    $params = [];

    if (!empty($search)) {
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        $conditions[] = "(name LIKE %s OR email LIKE %s OR mobile LIKE %s OR industry_type LIKE %s OR industry LIKE %s)";
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
    }

    if (!empty($start_date) && !empty($end_date)) {
        $conditions[] = "created_at BETWEEN %s AND %s";
        $params[] = $start_date;
        $params[] = $end_date;
    } elseif (!empty($start_date)) {
        $conditions[] = "created_at >= %s";
        $params[] = $start_date;
    } elseif (!empty($end_date)) {
        $conditions[] = "created_at <= %s";
        $params[] = $end_date;
    }

    // Construct the main query
    $query = "SELECT * FROM $table_name";
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    if (!empty($params)) {
        $query = $wpdb->prepare($query, ...$params);
    }

    // Fetch results as associative arrays
    $results = $wpdb->get_results($query, ARRAY_A);

    if ($results) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="book_demo_data.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Name', 'Email', 'Mobile', 'Industry Type', 'Industry', 'Created at'));

        foreach ($results as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    } else {
        echo 'No data found.';
        exit;
    }
}

function activate_book_a_dome() {
    // Activation code here
}
register_activation_hook(__FILE__, 'activate_book_a_dome');

function deactivate_book_a_dome() {
    // Deactivation code here
}
register_deactivation_hook(__FILE__, 'deactivate_book_a_dome');


?>
