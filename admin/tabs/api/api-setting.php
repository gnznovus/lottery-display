<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

$config_path = plugin_dir_path(dirname(__DIR__, 2)) . 'config.php';
$api_base_url = defined('LOTTERY_API_BASE_URL') ? LOTTERY_API_BASE_URL : '';
$api_key = defined('LOTTERY_API_KEY') ? LOTTERY_API_KEY : '';

// ✅ Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lottery_api_settings'])) {
    check_admin_referer('lottery_api_settings_save');

    $api_base_url = sanitize_text_field($_POST['lottery_api_base_url'] ?? '');
    $api_key = sanitize_text_field($_POST['lottery_api_key'] ?? '');

    if (is_writable($config_path)) {
        $config_contents = file_get_contents($config_path);
        $config_contents = preg_replace(
            "/define\\('LOTTERY_API_BASE_URL'\\s*,\\s*'[^']*'\\);/",
            "define('LOTTERY_API_BASE_URL', '{$api_base_url}');",
            $config_contents
        );
        $config_contents = preg_replace(
            "/define\\('LOTTERY_API_KEY'\\s*,\\s*'[^']*'\\);/",
            "define('LOTTERY_API_KEY', '{$api_key}');",
            $config_contents
        );
        file_put_contents($config_path, $config_contents);
        echo '<div class="updated"><p>✅ API settings saved.</p></div>';
    } else {
        echo '<div class="error"><p>❌ config.php is not writable.</p></div>';
    }
}
?>

<div class="wrap">
    <h1>API Settings</h1>
    <form method="post">
        <?php wp_nonce_field('lottery_api_settings_save'); ?>
        <input type="hidden" name="lottery_api_settings" value="1">

        <table class="form-table">
            <tr>
                <th><label for="lottery_api_base_url">API Base URL</label></th>
                <td>
                    <input
                        type="text"
                        id="lottery_api_base_url"
                        name="lottery_api_base_url"
                        value="<?php echo esc_attr($api_base_url); ?>"
                        class="regular-text"
                        placeholder="http://localhost:8000"
                    >
                    <p class="description">Example: http://localhost:8000 (local) or https://api.yourdomain.com (production)</p>
                </td>
            </tr>
            <tr>
                <th><label for="lottery_api_key">API Key</label></th>
                <td>
                    <input
                        type="text"
                        id="lottery_api_key"
                        name="lottery_api_key"
                        value="<?php echo esc_attr($api_key); ?>"
                        class="regular-text"
                        placeholder="change-me"
                    >
                    <p class="description">Must match API_AUTH_KEY in the Django .env</p>
                </td>
            </tr>
        </table>

        <button type="submit" class="button button-primary">Save Settings</button>
    </form>
</div>
