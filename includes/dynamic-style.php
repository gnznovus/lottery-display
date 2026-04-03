<?php
// Load settings from JSON
$settings_file = plugin_dir_path(__FILE__) . 'assets/json/display-settings.json';
$settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];

$font_family = isset($settings['font_family']) ? $settings['font_family'] : 'Arial, sans-serif';
$header_color = isset($settings['header_color']) ? $settings['header_color'] : '#0073aa';
$text_color = isset($settings['text_color']) ? $settings['text_color'] : '#333333';
$header_text_color = isset($settings['table_header_text_color']) ? $settings['table_header_text_color'] : '#ffffff';
$table_bg_color = isset($settings['table_bg_color']) ? $settings['table_bg_color'] : '#ffffff';

// Generate CSS
$css = "/* 🎨 Lottery Display Custom Styles */\n";

if (!empty($font_family)) {
    $css .= "@import url('https://fonts.googleapis.com/css2?family=" . urlencode($font_family) . "&display=swap');\n";
}

$css .= "
.lottery-container {
    font-family: '{$font_family}', sans-serif;
    color: {$text_color};
}

.lottery-container h2 {
    color: {$header_color};
}

.lottery-container table {
    background-color: {$table_bg_color};
    border-collapse: collapse;
    width: 100%;
}

.lottery-container th {
    background-color: {$header_color};
    color: {$header_text_color};
    padding: 10px;
}

.lottery-container td {
    padding: 8px;
    text-align: center;
}
";

// Save CSS to file
$css_file = plugin_dir_path(__FILE__) . 'assets/css/lottery-display.css';
file_put_contents($css_file, $css);

// Serve CSS
header("Content-type: text/css; charset=UTF-8");
echo $css;
exit;
