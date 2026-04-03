<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

// Register AJAX Action
add_action('wp_ajax_lottery_import_google_font', 'lottery_import_google_font');
add_action('wp_ajax_lottery_manual_fetch', 'lottery_manual_fetch');

function lottery_import_google_font() {
    // Ensure request is valid
    if (!isset($_POST['font_import']) || empty($_POST['font_import'])) {
        wp_send_json_error(['message' => 'No input received']);
    }

    $import_text = sanitize_text_field($_POST['font_import']);

    // Extract font names from import URL
    if (preg_match_all('/family=([^&:]+)/', $import_text, $matches)) {
        $new_fonts = array_map('urldecode', $matches[1]);

        // Load existing font.json
        $font_file = plugin_dir_path(__FILE__) . '../assets/json/font.json';
        $fonts = file_exists($font_file) ? json_decode(file_get_contents($font_file), true) : [];

        // Merge new fonts
        $fonts['google_fonts'] = array_values(array_unique(array_merge($fonts['google_fonts'] ?? [], $new_fonts)));

        // Save updated fonts
        file_put_contents($font_file, json_encode($fonts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // Return updated font list
        wp_send_json_success(['fonts' => $fonts['google_fonts']]);
    } else {
        wp_send_json_error(['message' => 'Invalid Google Font link']);
    }
}

function lottery_manual_fetch() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $response = lottery_api_request('/api/draw-events/', [
        'source' => 'huayrat',
        'status' => 'completed',
        'page_size' => 10,
    ]);

    if (!empty($response['results'])) {
        update_option('lottery_last_fetch_time', date("Y-m-d H:i:s"));
        wp_send_json_success(['message' => 'Draw dates updated successfully!']);
    }

    wp_send_json_error(['message' => 'Failed to fetch draw dates.'], 500);
}
?>
