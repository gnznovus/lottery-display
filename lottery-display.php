<?php
/*
Plugin Name: Lottery Display Plugin
Description: A WordPress plugin for displaying lottery results dynamically using Django API.
Version: 1.1
Author: Ginz
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path
define('LOTTERY_DISPLAY_PLUGIN_PATH', plugin_dir_path(__FILE__));

// ✅ Include core files safely (prevent double inclusion)
if (!function_exists('lottery_api_request')) {
    include_once LOTTERY_DISPLAY_PLUGIN_PATH . 'config.php';
    include_once LOTTERY_DISPLAY_PLUGIN_PATH . 'includes/api.php';
    include_once LOTTERY_DISPLAY_PLUGIN_PATH . 'includes/helper.php';
    include_once LOTTERY_DISPLAY_PLUGIN_PATH . 'includes/display.php';
    include_once LOTTERY_DISPLAY_PLUGIN_PATH . 'includes/ajax-handler.php';
    include_once LOTTERY_DISPLAY_PLUGIN_PATH . 'shortcode/main-lottery.php';
    include_once LOTTERY_DISPLAY_PLUGIN_PATH . 'shortcode/search.php';
    include_once LOTTERY_DISPLAY_PLUGIN_PATH . 'admin/setting.php';
}

// ✅ Register shortcodes
function register_lottery_shortcodes() {
    add_shortcode('lottery_display', 'lottery_display_shortcode');
    add_shortcode('lottery_search', 'lottery_search_shortcode');
}
add_action('init', 'register_lottery_shortcodes');

// ✅ Ensure CSS is Generated and Loaded Correctly
function lottery_enqueue_styles() {
    $base_url = plugin_dir_url(__FILE__) . 'assets/css/';
    $base_path = plugin_dir_path(__FILE__) . 'assets/css/';

    wp_enqueue_style('lottery-display-style', $base_url . 'lottery-display.css', array(), filemtime($base_path . 'lottery-display.css'));
    if (file_exists($base_path . 'search-box.css')) {
        wp_enqueue_style('lottery-search-box', $base_url . 'search-box.css', array('lottery-display-style'), filemtime($base_path . 'search-box.css'));
    }

    // Load generated display settings styles if present.
    $generated_styles = array('core-style.css', 'layout-huayrat.css', 'layout-default.css');
    foreach ($generated_styles as $css_file) {
        $full_path = $base_path . $css_file;
        if (file_exists($full_path)) {
            wp_enqueue_style(
                'lottery-display-' . sanitize_title($css_file),
                $base_url . $css_file,
                array('lottery-display-style'),
                filemtime($full_path)
            );
        }
    }

    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'lottery_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));

    // Optional UI helpers for search box
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
    wp_enqueue_script('canvas-confetti', 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'lottery_enqueue_styles');

// Ensure AJAX works in admin
function lottery_enqueue_admin_scripts($hook) {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'lottery_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'lottery_enqueue_admin_scripts');





