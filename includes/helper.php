<?php
// Helper functions for date formatting and validation

function convert_to_buddhist_era($date) {
    return date('Y', strtotime($date)) + 543;
}

function lottery_convert_date($date) {
    if (empty($date)) {
        return '-';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    $day_month = date('d/m/', $timestamp);
    $year_be = date('Y', $timestamp) + 543;
    return $day_month . $year_be;
}

function lottery_get_source_code_from_type($type) {
    $type = strtolower(trim($type));

    $map = [
        'huayrat_display' => 'huayrat',
        'huaylao_display' => 'huaylao',
        'huaymaley_display' => 'huaymaley',
        'huayhanoy_special' => 'huayhanoy_special',
        'huayhanoy_normal' => 'huayhanoy_normal',
        'huayhanoy_vip' => 'huayhanoy_vip',
        'huaystock_display' => 'huaystock',
    ];

    if (isset($map[$type])) {
        return $map[$type];
    }

    return preg_replace('/_display$/', '', $type);
}

function validate_table_name($table) {
    $allowed_tables = array('huayrat_display', 'huayrat_drawdate', 'huayrat_period');
    return in_array($table, $allowed_tables) ? $table : DEFAULT_LOTTERY_TYPE;
}
?>
