<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

// 🧠 Load Display Settings from JSON
function lottery_get_display_settings() {
    $base_path = plugin_dir_path(__FILE__) . '../assets/';
    $json_path = $base_path . 'json/display-settings.json';

    $defaults = [
        'border_enabled' => false,
        'border_size' => '3px',
        'border_radius' => '8px',
        'border_color' => '#ffffff',
        'font_source' => 'kit',
        'font_kit' => 'Arial',
        'google_font' => '',
        'font_family' => 'Arial',
        'header_tag' => 'h2',
        'header_color' => '#0073aa',
        'table_header_color' => '#0073aa',
        'table_header_text_color' => '#ffffff',
        'font_size' => '16px',
        'table_bg_color' => '#ffffff',
        'container_bg_color' => '#ffffff',
        'text_color' => '#333333'
    ];

    if (!file_exists($json_path)) {
        return $defaults;
    }

    $json = file_get_contents($json_path);
    $settings = json_decode($json, true);

    if (!is_array($settings)) {
        $settings = [];
    }

    if (isset($settings['font_source'])) {
        if ($settings['font_source'] === 'kit') {
            $settings['font_family'] = $settings['font_kit'] ?? $defaults['font_kit'];
            unset($settings['google_font']);
        } elseif ($settings['font_source'] === 'google') {
            $settings['font_family'] = $settings['google_font'] ?? $defaults['google_font'];
            unset($settings['font_kit']);
        }
    } else {
        $settings['font_family'] = $defaults['font_family'];
    }

    return array_merge($defaults, $settings);
}

// 💾 Save Display Settings to JSON + Split CSS Files
function lottery_save_display_settings($settings) {
    if (!is_array($settings)) return;

    $base_path = plugin_dir_path(__FILE__) . '../assets/';
    $json_path = $base_path . 'json/display-settings.json';
    $css_dir = $base_path . 'css/';
    $core_css_file = $css_dir . 'core-style.css';
    $huayrat_css_file = $css_dir . 'layout-huayrat.css';
    $default_css_file = $css_dir . 'layout-default.css';

    if (!is_dir($css_dir)) mkdir($css_dir, 0755, true);
    if (!is_dir(dirname($json_path))) mkdir(dirname($json_path), 0755, true);

    if (isset($settings['font_source'])) {
        if ($settings['font_source'] === 'kit') {
            $settings['font_family'] = $settings['font_kit'] ?? 'Arial';
            unset($settings['google_font']);
        } elseif ($settings['font_source'] === 'google') {
            $settings['font_family'] = $settings['google_font'] ?? '';
            unset($settings['font_kit']);
        }
    }

    file_put_contents($json_path, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $borderStyle = $settings['border_enabled'] ? "{$settings['border_size']}px solid {$settings['border_color']}" : "none !important;";
    $borderRadius = !empty($settings['border_radius']) ? "{$settings['border_radius']}px" : "0px !important;";

    // 🧩 Core CSS
    $core_css = "
    .lottery-container {
        max-width: 800px !important;
        margin: 20px auto !important;
        padding: 15px !important;
        border: {$borderStyle} !important;
        background: {$settings['container_bg_color']} !important;
        border-radius: {$borderRadius} !important;
        font-family: {$settings['font_family']} !important;
        font-size: {$settings['font_size']}px !important;
    }

    .lottery-draw-date {
        color: {$settings['header_color']} !important;
    }

    .lottery-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
    }

    .lottery-table-header {
        text-align: center;
    }

    .lottery-header-cell {
        background: {$settings['table_header_color']} !important;
        color: {$settings['table_header_text_color']} !important;
        text-align: center;
    }

    .lottery-cell {
        background: {$settings['table_bg_color']} !important;
        color: {$settings['text_color']} !important;
        text-align: center;
    }

    .lottery-cell, .lottery-header-cell {
        border: {$borderStyle} !important;
        border-radius: {$borderRadius} !important;
    }";

    // 🎯 Huayrat-specific
    $huayrat_css = "
    .lottery-container.huayrat {
        border: {$borderStyle} !important;
        padding: 20px !important;
        background: {$settings['container_bg_color']} !important;
    }

    .lottery-huayrat-wrapper {
        display: flex !important;
        justify-content: space-between !important;
        align-items: stretch !important;
        gap: 20px !important;
    }

    .lottery-main-prize {
        flex: 1 !important;
        padding: 20px !important;
        border: {$borderStyle} !important;
        background: {$settings['table_bg_color']} !important;
        text-align: center !important;
        border-radius: {$borderRadius} !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
    }

    .lottery-main-prize h2 {
        color: {$settings['header_color']} !important;
    }

    .lottery-main-prize .lottery-prize-number {
        font-size: 30px !important;
        font-weight: bold !important;
        color: {$settings['text_color']} !important;
        display: block !important;
        width: 100% !important;
        text-align: center !important;
    }

    .lottery-three-container {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
    }

    .lottery-three-digit {
        padding: 10px !important;
        border: {$borderStyle} !important;
        background: {$settings['table_bg_color']} !important;
        text-align: center !important;
        border-radius: {$borderRadius} !important;
    }

    .lottery-three-digit h3 {
        font-size: 20px !important;
        font-weight: bold !important;
        color: {$settings['header_color']} !important;
    }

    .lottery-three-digit .lottery-prize-amount {
        font-size: 16px !important;
        color: {$settings['text_color']} !important;
    }

    .lottery-three-digit .lottery-prize-numbers {
        display: flex !important;
        justify-content: center !important;
        gap: 10px !important;
    }

    .lottery-three-digit .lottery-prize-number {
        font-weight: bold !important;
        font-size: 20px !important;
        color: {$settings['text_color']} !important;
        display: block !important;
        width: 100% !important;
        text-align: center !important;
    }
        
    .lottery-three-container.count-2 {
        flex-direction: column !important;
    }

    .lottery-three-container.count-3 {
        display: flex !important;
        flex-direction: row !important;
        gap: 10px !important;
        justify-content: space-between !important;
        margin-top: 10px !important;
    }

    .lottery-three-container.count-3 .lottery-three-digit {
        flex: 1 !important;
    }";

    // 🧩 Default Layout
    $default_css = "
    .lottery-default .lottery-table {
        border: {$borderStyle} !important;
    }

    .lottery-default .lottery-cell {
        font-weight: normal;
        padding: 8px;
    }

    .lottery-default .lottery-header-cell {
        font-weight: bold;
        font-size: 18px;
    }";

    // 💾 Write to disk
    file_put_contents($core_css_file, trim($core_css));
    file_put_contents($huayrat_css_file, trim($huayrat_css));
    file_put_contents($default_css_file, trim($default_css));
}
?>
