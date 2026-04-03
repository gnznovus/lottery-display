<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

// Include display settings handler
require_once plugin_dir_path(dirname(__DIR__, 2)) . '/includes/display-handler.php';

// Load available fonts from JSON
$font_file = plugin_dir_path(dirname(__DIR__, 2)) . '/assets/json/font.json';
$fonts = file_exists($font_file) ? json_decode(file_get_contents($font_file), true) : [];
$font_kits = $fonts['font_kits'] ?? [];
$google_fonts = $fonts['google_fonts'] ?? [];

// 🛠 Define Default Settings
$defaults = [
        'border_enabled' => false,
        'border_size' => '3px',
        'border_radius' => '8px',
        'border_color' => '#ffffff',
        'font_source' => 'kit', // Default to 'kit'
        'font_kit' => 'Arial',
        'google_font' => '',
        'font_family' => 'Arial', // Assigned dynamically
        'header_tag' => 'h2',
        'header_color' => '#0073aa',
        'table_header_color' => '#0073aa',
        'table_header_text_color' => '#ffffff',
        'font_size' => '16px',
        'table_bg_color' => '#ffffff',
        'container_bg_color' => '#ffffff',
        'text_color' => '#333333'
    ];

// Get stored settings and merge with defaults
$stored_settings = lottery_get_display_settings();
$settings = array_merge($defaults, $stored_settings);

// Dynamically set `font_family`
$settings['font_family'] = ($settings['font_source'] === 'kit') ? $settings['font_kit'] : $settings['google_font'];

// 🛠 Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lottery_display_settings'])) {
    if (isset($_POST['reset_settings'])) {
        $base_path = plugin_dir_path(dirname(__DIR__, 2)) . '/assets/';
        $json_path = $base_path . 'json/display-settings.json';
        $css_files = [
            $base_path . 'css/core-style.css',
            $base_path . 'css/layout-huayrat.css',
            $base_path . 'css/layout-default.css',
        ];

        if (file_exists($json_path)) {
            unlink($json_path);
        }
        foreach ($css_files as $css_file) {
            if (file_exists($css_file)) {
                unlink($css_file);
            }
        }

        $settings = $defaults;
        lottery_save_display_settings($settings);
    } else {
        $sanitized_settings = [];
        foreach ($_POST['lottery_display_settings'] as $key => $value) {
            if (is_string($value)) {
                $sanitized_settings[$key] = sanitize_text_field($value);
            }
        }
        $settings = array_merge($defaults, $sanitized_settings);
        lottery_save_display_settings($settings);
    }
}
?>

<div class="wrap">
    <h1>🎨 Lottery Display Settings</h1>
    <form method="post">
        <h2>🖋️ Font Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="font_size">Font Size (px)</label></th>
                <td><input type="number" name="lottery_display_settings[font_size]" value="<?php echo esc_attr($settings['font_size']); ?>" min="10" max="36"></td>
            </tr>
            <tr>
                <th><label for="current_font">Current Font</label></th>
                <td>
                    <p id="currentFontDisplay" style="margin: 0; font-family: <?php echo esc_attr($settings['font_family']); ?>;">
                        <strong><?php echo esc_html($settings['font_family'] ?? 'Default Font'); ?></strong>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="font_source">Font Source</label></th>
                <td>
                    <select name="lottery_display_settings[font_source]" id="font_source">
                        <option value="kit" <?php selected($settings['font_source'], 'kit'); ?>>Font Kit</option>
                        <option value="google" <?php selected($settings['font_source'], 'google'); ?>>Google Fonts</option>
                    </select>
                </td>
            </tr>

            <tr id="font-kit-section">
                <th><label for="font_kit">Font Kit</label></th>
                <td>
                    <select name="lottery_display_settings[font_kit]" id="font_kit">
                        <?php foreach ($font_kits as $font): ?>
                            <option value="<?php echo esc_attr($font); ?>" <?php selected($settings['font_kit'], $font); ?>>
                                <?php echo esc_html($font); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr id="google-font-section" style="display: none;">
                <th><label for="google_font">Google Font</label></th>
                <td>
                    <select name="lottery_display_settings[google_font]" id="google_font">
                        <?php foreach ($google_fonts as $font): ?>
                            <option value="<?php echo esc_attr($font); ?>" <?php selected($settings['google_font'], $font); ?>>
                                <?php echo esc_html($font); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="no-google-font" style="color: red;">
                        No Google Font Available
                    </p>
                </td>
            </tr>
        </table>

        <h2>🌐 Import Google Font</h2>
        <button type="button" id="toggleImportFont" class="button">+ Import Google Font</button>
        <div id="importFontSection" style="display: none;">
            <textarea id="google_font_import" rows="2" placeholder="@import url('https://fonts.googleapis.com/css2?family=Kanit&display=swap');"></textarea>
            <button type="button" id="importGoogleFont" class="button button-primary">Import</button>
            <span id="importResult" style="color: red;"></span>
        </div>
        
        <h2>🖼️ Appearance</h2>
        <table class="form-table">
            <tr>
                <th><label for="header_tag">Header Tag</label></th>
                <td>
                    <select name="lottery_display_settings[header_tag]" id="header_tag">
                        <?php foreach (['h1', 'h2', 'h3', 'h4', 'h5'] as $tag): ?>
                            <option value="<?php echo esc_attr($tag); ?>" <?php selected($settings['header_tag'], $tag); ?>>
                                <?php echo strtoupper($tag); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="table_bg_color">Container Background Color</label></th>
                <td><input type="color" name="lottery_display_settings[container_bg_color]" value="<?php echo esc_attr($settings['container_bg_color']); ?>"></td>
            </tr>
            <tr>
                <th><label for="table_bg_color">Table Background Color</label></th>
                <td><input type="color" name="lottery_display_settings[table_bg_color]" value="<?php echo esc_attr($settings['table_bg_color']); ?>"></td>
            </tr>
            <tr>
                <th><label for="table_header_color">Table Header Background Color</label></th>
                <td><input type="color" name="lottery_display_settings[table_header_color]" value="<?php echo esc_attr($settings['table_header_color']); ?>"></td>
            </tr>
            <tr>
                <th><label for="border_enabled">Enable Borders</label></th>
                <td>
                    <input type="checkbox" name="lottery_display_settings[border_enabled]" value="1" <?php checked($settings['border_enabled'], true); ?>>
                </td>
            </tr>
            <tr>
                <th><label for="border_radius">Border Size (px)</label></th>
                <td>
                    <input type="number" name="lottery_display_settings[border_size]" value="<?php echo esc_attr($settings['border_size']); ?>" min="0" max="50">
                </td>
            </tr>
            <tr>
                <th><label for="border_radius">Border Radius (px)</label></th>
                <td>
                    <input type="number" name="lottery_display_settings[border_radius]" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="50">
                </td>
            </tr>
            <tr>
                <th><label for="table_header_text_color">border Color</label></th>
                <td><input type="color" name="lottery_display_settings[border_color]" value="<?php echo esc_attr($settings['border_color']); ?>"></td>
            </tr>
            <tr>
                <th><label for="header_color">Header Text Color</label></th>
                <td><input type="color" name="lottery_display_settings[header_color]" value="<?php echo esc_attr($settings['header_color']); ?>"></td>
            </tr>
            <tr>
                <th><label for="table_header_text_color">Table Header Text Color</label></th>
                <td><input type="color" name="lottery_display_settings[table_header_text_color]" value="<?php echo esc_attr($settings['table_header_text_color']); ?>"></td>
            </tr>
            <tr>
                <th><label for="text_color">Text Color</label></th>
                <td><input type="color" name="lottery_display_settings[text_color]" value="<?php echo esc_attr($settings['text_color']); ?>"></td>
            </tr>
        </table>

        <input type="submit" name="save_settings" value="Save Settings" class="button button-primary">
        <input type="submit" name="reset_settings" value="Reset to Default" class="button button-secondary" onclick="return confirm('Are you sure you want to reset all settings?');">
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    function toggleFontOptions() {
        let fontSource = $('#font_source').val();
        $('#font-kit-section').toggle(fontSource === 'kit');
        $('#google-font-section').toggle(fontSource === 'google');
    }

    $('#font_source').change(toggleFontOptions);
    toggleFontOptions();

    $('#toggleImportFont').click(function() {
        $('#importFontSection').toggle();
    });

    $('#importGoogleFont').click(function() {
        let importText = $('#google_font_import').val().trim();
        if (!importText) {
            $('#importResult').text('⚠️ Please enter an @import URL.').css('color', 'red');
            return;
        }

        let activeTab = $('.nav-tab.main-tab.nav-tab-active').data('tab');
        localStorage.setItem('lottery_last_tab', `?page=lottery_display&tab=${activeTab}`);
        localStorage.setItem('lottery_scroll_position', $(window).scrollTop());

        $.post(lottery_ajax.ajaxurl, { action: 'lottery_import_google_font', font_import: importText }, function(response) {
            if (response.success) {
                $('#importResult').text('✅ Font Imported!').css('color', 'green');

                // ✅ Delay ensures fonts are added before checking
                setTimeout(function() { 
                    location.reload();
                }, 1000);
            } else {
                $('#importResult').text('❌ ' + response.data.message).css('color', 'red');
            }
        });
    });

    // 🔥 Function to Show/Hide "No Google Font Available" Message
    function updateGoogleFontMessage() {
        if ($('#google_font option').length > 1) {
            $('#no-google-font').hide();
        } else {
            $('#no-google-font').show();
        }
    }

    // ✅ Run on Page Load
    updateGoogleFontMessage();

    // ✅ Run After Importing a Font
    $('#importGoogleFont').click(function() {
        setTimeout(updateGoogleFontMessage, 1000);
    });

    $('.color-picker').wpColorPicker();
});
</script>

