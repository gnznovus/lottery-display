<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;

// ✅ Get Server Time
$server_time = date("Y-m-d H:i:s");

// ✅ Get Last Draw Date Fetch Time (stored in options)
$last_fetch_time = get_option('lottery_last_fetch_time', 'Never Fetched');

// ✅ Get Next Cron Job Time
$next_cron_time = wp_next_scheduled('update_draw_dates_json');
$next_cron_display = $next_cron_time ? date("Y-m-d H:i:s", $next_cron_time) : 'No Cron Scheduled';

// ✅ Save settings when updated
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lottery_general_settings'])) {
    update_option('lottery_enable_cache', isset($_POST['lottery_enable_cache']) ? 1 : 0);
    update_option('lottery_cache_time', max(intval($_POST['lottery_cache_time']), 3600)); // Min 1 hour
    update_option('lottery_enable_cron', isset($_POST['lottery_enable_cron']) ? 1 : 0);
    update_option('lottery_cron_time', sanitize_text_field($_POST['lottery_cron_time']));

    echo '<div class="updated"><p>✅ Settings saved successfully!</p></div>';
}

// ✅ Manual Fetch handled via AJAX (see includes/ajax-handler.php)

// ✅ Default values
$enable_cache = get_option('lottery_enable_cache', 1);
$cache_time = get_option('lottery_cache_time', 86400); // Default 24 hours
$enable_cron = get_option('lottery_enable_cron', 0);
$cron_time = get_option('lottery_cron_time', '03:00');

?>

<div class="wrap">
    <!-- ✅ System Information Panel -->
    <h2>📊 System Information</h2>
    <table class="form-table">
        <tr>
            <th>🕒 Server Time:</th>
            <td><strong><?php echo esc_html($server_time); ?></strong></td>
        </tr>
        <tr>
            <th>📅 Last Draw Date Fetch:</th>
            <td><strong><?php echo esc_html($last_fetch_time); ?></strong></td>
        </tr>
        <tr>
            <th>⏳ Next Scheduled Cron Job:</th>
            <td><strong><?php echo esc_html($next_cron_display); ?></strong></td>
        </tr>
    </table>

    <h2>🎯 Lottery General Settings</h2>
    <form method="post">
        <input type="hidden" name="lottery_general_settings" value="1">

        <!-- ✅ Cache Settings -->
        <h3>📌 Caching</h3>
        <label>
            <input type="checkbox" name="lottery_enable_cache" value="1" <?php checked($enable_cache, 1); ?>>
            Enable Draw Date Caching (Store in options)
        </label>
        <p><small>Draw dates will be cached to reduce API calls.</small></p>

        <label for="lottery_cache_time">Cache Duration (Seconds):</label>
        <input type="number" name="lottery_cache_time" id="lottery_cache_time" value="<?php echo esc_attr($cache_time); ?>" min="3600">
        <p><small>Default: 86400 (24 hours)</small></p>

        <!-- ✅ Manual Fetch Button -->
        <h3>🔄 Manual Fetch</h3>
        <p>Click the button below to fetch the latest draw dates from Django API.</p>
        <button type="button" id="manualFetchBtn" class="button button-primary">📥 Fetch Draw Dates Now</button>
        <span id="manualFetchStatus" style="margin-left:10px;"></span>

        <!-- ✅ Cron Job Settings -->
        <h3>⏳ Cron Job (Auto Refresh)</h3>
        <label>
            <input type="checkbox" name="lottery_enable_cron" value="1" <?php checked($enable_cron, 1); ?>>
            Enable Auto Update (Runs on 1st & 16th of each month)
        </label>
        <p><small>This will automatically refresh draw dates at a specific time.</small></p>

        <label for="lottery_cron_time">Cron Job Time (Server Time):</label>
        <input type="time" name="lottery_cron_time" id="lottery_cron_time" value="<?php echo esc_attr($cron_time); ?>">
        <p><small>Default: 03:00 AM</small></p>

        <button type="submit" class="button button-primary">💾 Save Settings</button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("manualFetchBtn");
    const status = document.getElementById("manualFetchStatus");

    if (!btn) return;

    btn.addEventListener("click", function () {
        btn.disabled = true;
        status.textContent = "⏳ Fetching...";

        fetch(lottery_ajax.ajaxurl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: new URLSearchParams({ action: "lottery_manual_fetch" })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                status.textContent = "✅ " + (data.data?.message || "Updated!");
                setTimeout(() => location.reload(), 800);
            } else {
                status.textContent = "❌ " + (data.data?.message || "Failed");
            }
        })
        .catch(() => {
            status.textContent = "❌ Error";
        })
        .finally(() => {
            btn.disabled = false;
        });
    });
});
</script>
