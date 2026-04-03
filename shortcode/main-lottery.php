<?php
// Main Lottery Display Shortcode
function lottery_display_shortcode($atts) {
    // Set default shortcode attributes
    $atts = shortcode_atts(array(
        'type' => DEFAULT_LOTTERY_TYPE,
    ), $atts, 'lottery_display');

    $lottery_type = strtolower($atts['type']);
    $source_code = lottery_get_source_code_from_type($lottery_type);

    if (empty($source_code)) {
        return '<p class="lottery-error">?? Error: Invalid lottery type selected.</p>';
    }

    $payload = lottery_api_request("/api/sources/{$source_code}/results/latest/");

    if (!empty($payload)) {
        return generate_lottery_display($source_code, $payload);
    }

    return '<p class="lottery-error">?? No results found.</p>';
}

// Register the shortcode
add_shortcode('lottery_display', 'lottery_display_shortcode');

?>
