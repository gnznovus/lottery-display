<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;

// Load column mapping from JSON
$mapping_file = plugin_dir_path(__FILE__) . '../assets/json/column_mapping.json';
$column_mapping = file_exists($mapping_file) ? json_decode(file_get_contents($mapping_file), true) : [];
$rewardTypeMapping = $column_mapping['rewardTypeMapping'] ?? [];
$sourceDisplayNames = $column_mapping['sourceDisplayNames'] ?? [];

function lottery_get_reward_label($reward_code, $fallback = '') {
    global $rewardTypeMapping;
    if (!empty($rewardTypeMapping[$reward_code])) {
        return $rewardTypeMapping[$reward_code];
    }
    return $fallback ?: $reward_code;
}

function lottery_get_source_label($source_code, $fallback = '') {
    global $sourceDisplayNames;
    if (!empty($sourceDisplayNames[$source_code])) {
        return $sourceDisplayNames[$source_code];
    }
    return $fallback ?: $source_code;
}

function generate_lottery_display($source_code, $payload) {
    if (empty($payload) || !is_array($payload)) {
        return '<p class="lottery-error">⚠️ No valid data received for display.</p>';
    }

    $source = $payload['source'] ?? [];
    $draw_event = $payload['draw_event'] ?? [];
    $results = $payload['results'] ?? [];

    $draw_date = $draw_event['resolved_date'] ?? $draw_event['scheduled_date'] ?? null;

    if ($source_code === 'huayrat') {
        return generate_huayrat_layout($results, $draw_date, $source, $source_code);
    }

    return generate_default_table_layout($results, $draw_date, $source);
}

function generate_huayrat_layout($results, $reward_date, $source = [], $source_code = '') {
    if (empty($results)) {
        return '<p class="lottery-error">⚠️ No results found.</p>';
    }

    $result_map = [];
    foreach ($results as $row) {
        $code = $row['reward_type_code'] ?? '';
        if (empty($code)) {
            continue;
        }
        if (!isset($result_map[$code])) {
            $result_map[$code] = [];
        }
        $result_map[$code][] = $row;
    }

    ob_start();

    echo '<div class="lottery-container huayrat">';

    if ($reward_date) {
        $reward_date_be = lottery_convert_date($reward_date);
        $fallback_name = !empty($source['name']) ? $source['name'] : 'สลากกินแบ่งรัฐบาล';
        $title = lottery_get_source_label($source_code ?: ($source['code'] ?? ''), $fallback_name);
        echo '<h2 class="lottery-draw-date">ผล' . esc_html($title) . ' งวดวันที่ ' . esc_html($reward_date_be) . '</h2>';
    }

    $mainPrize = $result_map['first_prize'][0] ?? null;
    $frontThree = $result_map['front_3_digits'] ?? [];
    $backThree = $result_map['back_3_digits'] ?? [];
    $backTwo = $result_map['last_2_digits'] ?? [];

    echo '<div class="lottery-huayrat-wrapper">';

    if (!empty($mainPrize)) {
        echo '<div class="lottery-main-prize">';
        echo '<h2 class="lottery-title">' . esc_html(lottery_get_reward_label('first_prize', $mainPrize['reward_type_name'] ?? 'รางวัลที่ 1')) . '</h2>';
        echo '<p class="lottery-prize-amount">รางวัลละ 6,000,000 บาท</p>';
        echo '<p class="lottery-prize-number">' . esc_html($mainPrize['value'] ?? '-') . '</p>';
        echo '</div>';
    }

    echo '<div class="lottery-three-container">';
    render_three_digit_block(
        lottery_get_reward_label('front_3_digits', 'เลขหน้า 3 ตัว'),
        '4,000',
        $frontThree
    );
    render_three_digit_block(
        lottery_get_reward_label('back_3_digits', 'เลขท้าย 3 ตัว'),
        '4,000',
        $backThree
    );
    render_three_digit_block(
        lottery_get_reward_label('last_2_digits', 'เลขท้าย 2 ตัว'),
        '2,000',
        $backTwo
    );
    echo '</div>';

    echo '</div>';

    echo '</div>';
    return ob_get_clean();
}

function render_three_digit_block($label, $amount, $dataBlock) {
    if (empty($dataBlock)) return;
    echo '<div class="lottery-three-digit">';
    echo '<h3 class="lottery-subtitle">' . esc_html($label) . '</h3>';
    echo '<p class="lottery-prize-amount">รางวัลละ ' . esc_html($amount) . ' บาท</p>';
    echo '<div class="lottery-prize-numbers">';
    foreach ($dataBlock as $prize) {
        echo '<span class="lottery-prize-number">' . esc_html($prize['value'] ?? '-') . '</span>';
    }
    echo '</div></div>';
}

function generate_default_table_layout($results, $reward_date, $source = []) {
    if (empty($results)) {
        return '<p class="lottery-error">⚠️ No results found.</p>';
    }

    $reward_date_be = $reward_date ? lottery_convert_date($reward_date) : '-';
    $fallback_name = !empty($source['name']) ? $source['name'] : 'ผลหวย';
    $title = lottery_get_source_label($source['code'] ?? '', $fallback_name);

    $groups = [];
    foreach ($results as $row) {
        $code = $row['reward_type_code'] ?? '';
        if (empty($code)) {
            continue;
        }
        if (!isset($groups[$code])) {
            $groups[$code] = [
                'label' => lottery_get_reward_label($code, $row['reward_type_name'] ?? $code),
                'values' => [],
            ];
        }
        $groups[$code]['values'][] = $row['value'] ?? '-';
    }

    ob_start();

    echo '<div class="lottery-container lottery-default">';
    echo '<h2 class="lottery-draw-date">ผล' . esc_html($title) . ' งวดวันที่ ' . esc_html($reward_date_be) . '</h2>';

    echo '<table class="lottery-table">';
    echo '<tr class="lottery-table-header">';
    foreach ($groups as $group) {
        echo '<th class="lottery-header-cell">' . esc_html($group['label']) . '</th>';
    }
    echo '</tr>';

    echo '<tr class="lottery-row">';
    foreach ($groups as $group) {
        $value_text = implode(' ', array_map('esc_html', $group['values']));
        echo '<td class="lottery-cell">' . $value_text . '</td>';
    }
    echo '</tr>';

    echo '</table>';
    echo '</div>';

    return ob_get_clean();
}

?>
