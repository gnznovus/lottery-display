<?php
$tabs_path = __DIR__ . '/tabs.php';

// Ensure `tabs.php` exists
if (!file_exists($tabs_path)) {
    error_log("[Error] `tabs.php` not found: " . $tabs_path);
    exit('<strong style="color: red;">⚠️ ERROR: `tabs.php` missing.</strong>');
}

$tabs = include($tabs_path);
if (!is_array($tabs)) {
    error_log("[Error] `tabs.php` returned invalid data.");
    exit('<strong style="color: red;">⚠️ ERROR: `tabs.php` is invalid.</strong>');
}

// Lottery Display Settings Page
function lottery_display_settings_page() {
    global $tabs;
    ?>
    <div class="wrap">
        <h1>Lottery Display Settings</h1>

        <!-- Tabs Menu -->
        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_key => $tab_data) : ?>
                <a href="?page=lottery_display&tab=<?php echo esc_attr($tab_key); ?>"
                   class="nav-tab main-tab"
                   data-tab="<?php echo esc_attr($tab_key); ?>">
                    <?php echo esc_html($tab_data['label']); ?>
                </a>
            <?php endforeach; ?>
        </h2>


        <!-- Tab Content -->
        <?php foreach ($tabs as $tab_key => $tab_data) : ?>
            <div id="<?php echo esc_attr($tab_key); ?>" class="tab-content" style="display: none;">
                <?php
                if (!is_dir($tab_data['directory'])) {
                    error_log("[Error] Tab directory missing: " . $tab_data['directory']);
                    echo "<p style='color: red;'>⚠️ Error: Directory <strong>{$tab_data['directory']}</strong> not found.</p>";
                    continue;
                }

                $files = glob($tab_data['directory'] . '/*.php');

                if (!empty($files)) {
                    foreach ($files as $file) {
                        echo "<p style='color: green;'>✅ Loading: <strong>" . basename($file) . "</strong></p>"; // Debugging Output
                        include_once $file; // Ensure each file is loaded only once
                    }
                } else {
                    echo "<p style='color: red;'>⚠️ No settings files found for this tab.</p>";
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
        const tabs = document.querySelectorAll(".main-tab");
        const contents = document.querySelectorAll(".tab-content");

        // Restore last active tab from localStorage
        const savedTab = localStorage.getItem("lottery_last_tab");
        let activeTab = tabs[0]; // Default to the first tab if none is saved

        if (savedTab) {
            const matchedTab = [...tabs].find(tab => tab.getAttribute("href") === savedTab);
            if (matchedTab) activeTab = matchedTab;
        }

        // Function to switch tabs
        function switchTab(tab) {
            tabs.forEach(t => t.classList.remove("nav-tab-active"));
            contents.forEach(c => c.style.display = "none");

            tab.classList.add("nav-tab-active");
            document.getElementById(tab.dataset.tab).style.display = "block";

            // Save active tab to localStorage
            localStorage.setItem("lottery_last_tab", tab.getAttribute("href"));
        }

        // Event Listener for Tab Clicks
        tabs.forEach(tab => {
            tab.addEventListener("click", function (e) {
                e.preventDefault();
                switchTab(this);
            });
        });

        // Activate stored or default tab on page load
        switchTab(activeTab);
    });

    </script>
    <?php
}

function lottery_display_generate_settings_fields($tab_key) {
    global $tabs; // Use global instead of reloading

    if (!isset($tabs[$tab_key]['settings'])) return;

    echo '<form method="post" action="options.php">';
    settings_fields('lottery_display_' . $tab_key . '_settings');

    foreach ($tabs[$tab_key]['settings'] as $setting_key => $setting_data) {
        $value = get_option($setting_key, $setting_data['default']);
        echo "<label for='$setting_key'>{$setting_data['label']}</label><br>";

        switch ($setting_data['type']) {
            case 'text':
                echo "<input type='text' id='$setting_key' name='$setting_key' value='$value'><br><br>";
                break;
            case 'checkbox':
                $checked = $value ? 'checked' : '';
                echo "<input type='checkbox' id='$setting_key' name='$setting_key' value='1' $checked><br><br>";
                break;
            case 'color':
                echo "<input type='color' id='$setting_key' name='$setting_key' value='$value'><br><br>";
                break;
        }
    }

    submit_button('Save Settings');
    echo '</form>';
}

// Add settings page to WordPress admin
function lottery_display_add_admin_menu() {
    add_options_page('Lottery Display', 'Lottery Display', 'manage_options', 'lottery_display', 'lottery_display_settings_page');
}
add_action('admin_menu', 'lottery_display_add_admin_menu');
?>