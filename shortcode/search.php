<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

// ✅ Register AJAX handlers for search functionality
add_action('wp_ajax_lottery_search', 'fetch_lottery_results');
add_action('wp_ajax_nopriv_lottery_search', 'fetch_lottery_results'); // Allow guests

// ✅ Function to Fetch Draw Dates
function get_draw_dates() {
    $response = lottery_api_request('/api/draw-events/', [
        'source' => 'huayrat',
        'status' => 'completed',
        'page_size' => 10,
    ]);

    $items = $response['results'] ?? [];
    $dates = [];

    foreach ($items as $row) {
        $date_value = $row['resolved_date'] ?? $row['scheduled_date'] ?? null;
        if (!$date_value) {
            continue;
        }
        $dates[] = [
            'date' => lottery_convert_date($date_value),
            'date_value' => $date_value,
        ];
    }

    return $dates;
}

// ✅ Function to Fetch Search Results
function fetch_lottery_results() {
    if (WP_DEBUG) {
        error_log("[DEBUG] Lottery Search Request Received: " . print_r($_POST, true));
    }

    $reward_date = isset($_POST['reward_date']) ? sanitize_text_field($_POST['reward_date']) : '';
    $lottery_number = isset($_POST['lottery_number']) ? sanitize_text_field($_POST['lottery_number']) : '';

    if (empty($reward_date) || strlen($lottery_number) !== 6) {
        wp_send_json_error(['message' => 'กรุณาเลือกงวดวันที่ และกรอกเลขให้ครบ 6 หลัก'], 400);
    }

    $response = lottery_api_request('/api/search/', [
        'source' => 'huayrat',
        'draw_date' => $reward_date,
        'number' => $lottery_number,
    ]);

    if (!$response || empty($response['matches'])) {
        wp_send_json_error(['message' => 'ไม่พบผลการออกางวัลของเลขนี้'], 404);
    }

    $html_results = generate_compact_search_layout($response['matches']);
    wp_send_json_success(['html' => $html_results]);
}

// ✅ AJAX: Fetch Draw Dates
add_action('wp_ajax_get_lottery_draw_dates', 'handle_get_lottery_draw_dates');
add_action('wp_ajax_nopriv_get_lottery_draw_dates', 'handle_get_lottery_draw_dates');

function handle_get_lottery_draw_dates() {
    $dates = get_draw_dates();
    wp_send_json_success($dates);
}

// ✅ Function to Render Search Results
function generate_compact_search_layout($results) {
    ob_start();
    echo '<div class="lottery-results-container">';
    foreach ($results as $result) {
        $label = lottery_get_reward_label($result['reward_type_code'] ?? '', $result['reward_type_name'] ?? '');
        echo '<p><strong>' . esc_html($label) . ':</strong> ';
        echo esc_html($result['value'] ?? '-') . '</p>';
    }
    echo '</div>';
    return ob_get_clean();
}

// ✅ Register Shortcode for Search Box
function lottery_search_shortcode() {
    ob_start(); ?>
    
    <div class="lottery-search-box">
        <label>ตรวจผลรางวัล</label>
        <select class="reward-date"><option>กำลังโหลด...</option></select>

        <label>กรอกเลข 6 หลัก</label>
        <input type="text" class="lottery-number" maxlength="6" pattern="\d{6}" placeholder="ตัวเลข 6 หลัก">

        <button class="search-btn">ตรวจสอบ</button>

        <div class="search-results" style="display:none;">
            <h3>ผลการตรวจสอบ</h3>
            <div class="result-content">รอผลลัพธ์...</div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function ($) {
        console.log("✅ Lottery Search Script Loaded");

        function lotteryAlert(options) {
            if (typeof Swal !== 'undefined') {
                Swal.fire(options);
                return;
            }
            const text = options.text || options.title || '';
            if (text) {
                alert(text);
            }
        }

        function lotteryConfetti() {
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 200,
                    spread: 120,
                    origin: { y: 0.6 }
                });
            }
        }

        $('.lottery-search-box').each(function () {
            const $box = $(this);
            const $dateSelect = $box.find('.reward-date');
            const $input = $box.find('.lottery-number');
            const $btn = $box.find('.search-btn');
            const $results = $box.find('.search-results');
            const $resultContent = $box.find('.result-content');

            // 🎯 Load draw dates
            $.ajax({
                url: lottery_ajax.ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: { action: 'get_lottery_draw_dates' },
                success: function (response) {
                    if (response.success && response.data.length > 0) {
                        let options = '<option value="">เลือกวันที่...</option>';
                        response.data.forEach(date => {
                            options += `<option value="${date.date_value}">${date.date}</option>`;
                        });
                        $dateSelect.html(options);
                    } else {
                        $dateSelect.html('<option value="">ไม่พบวันที่</option>');
                    }
                },
                error: function () {
                    $dateSelect.html('<option value="">โหลดวันที่ล้มเหลว</option>');
                }
            });

            // 🧪 Handle Search Click
            $btn.off('click').on('click', function (e) {
                e.preventDefault();

                const rewardDate = $dateSelect.val();
                const lotteryNumber = $input.val().trim();

                if (!rewardDate || lotteryNumber.length !== 6) {
                    lotteryAlert({
                        icon: 'warning',
                        title: '🚨 ข้อมูลไม่ครบ!',
                        text: 'กรุณาเลือกงวดวันที่ และกรอกเลขให้ครบ 6 หลัก',
                        confirmButtonText: 'ตกลง',
                        width: '350px',
                        padding: '1.5rem',
                        customClass: {
                            popup: 'swal-custom-popup',
                            title: 'swal-custom-title',
                            confirmButton: 'swal-custom-button'
                        }
                    });
                    return;
                }

                $.ajax({
                    url: lottery_ajax.ajaxurl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'lottery_search',
                        reward_date: rewardDate,
                        lottery_number: lotteryNumber
                    },
                    beforeSend: function () {
                        $resultContent.html("กำลังตรวจสอบ...").slideDown();
                    },
                    success: function (response) {
                        $results.slideDown();

                        if (response.success) {
                            $resultContent.html(response.data.html);

                            // 🎯 Extract Reward Types
                            let rewardTypeList = [];
                            $results.find('p').each(function () {
                                const text = $(this).text().trim();
                                if (text.includes(":")) {
                                    rewardTypeList.push(text.split(":")[0].trim());
                                }
                            });

                            const rewardDetails = rewardTypeList.length > 0
                                ? rewardTypeList.join('<br>')
                                : '🏆 รางวัลใหญ่ 🎉';

                            setTimeout(() => {
                                lotteryConfetti();
                            }, 200);

                            lotteryAlert({
                                icon: 'success',
                                title: '🎉 ยินดีด้วย! คุณถูกรางวัล!! 🎊',
                                html: `<p>🎯 รางวัลของคุณคือ:</p>
                                       <div style="font-size: 20px; color: #ff5733; font-weight: bold;">${rewardDetails}</div>
                                       <br>💰 ไปขึ้นเงินเลย! 💵`,
                                confirmButtonText: 'ฉลองกันเลย! 🥳',
                                width: '400px',
                                padding: '1.5rem',
                                customClass: {
                                    popup: 'swal-custom-popup',
                                    title: 'swal-custom-title',
                                    confirmButton: 'swal-custom-button'
                                }
                            });
                        }
                    },
                    error: function () {
                        const badLuckMessages = [
                            "💔 เฉียดนิดเดียว! 🎯 งวดหน้าต้องมาแน่!",
                            "😩 อย่าเพิ่งท้อ! ครั้งหน้าต้องได้! 🚀",
                            "😢 ดวงวันนี้ยังไม่มา แต่ความหวังยังอยู่ ✨",
                            "😵‍💫 ลองใหม่! ครั้งหน้ามีโอกาสมากกว่าเดิม! 🔥",
                            "😶‍🌫️ เลขนี้อาจไม่ใช่ของคุณ แต่อนาคตอาจเป็นของคุณ!",
                            "😞 ลองใหม่อีกที... บางทีครั้งหน้าอาจเป็นของคุณ!",
                            "🥺 ครั้งนี้อาจไม่ใช่ของคุณ แต่อย่าหยุดเชื่อ!",
                            "😓 วันนี้อาจไม่ใช่วันของคุณ แต่วันหน้าก็ยังมี!"
                        ];
                        const msg = badLuckMessages[Math.floor(Math.random() * badLuckMessages.length)];

                        $results.slideUp();

                        lotteryAlert({
                            icon: 'error',
                            title: '💔 ไม่พบข้อมูล!',
                            text: msg,
                            confirmButtonText: 'ลองอีกครั้ง!',
                            width: '400px',
                            padding: '1.5rem',
                            customClass: {
                                popup: 'swal-custom-popup',
                                title: 'swal-custom-title',
                                confirmButton: 'swal-custom-button'
                            }
                        });
                    }
                });
            });
        });
    });
    </script>

    <?php return ob_get_clean();
}

// ✅ Register the shortcode inside search.php
add_shortcode('lottery_search', 'lottery_search_shortcode');