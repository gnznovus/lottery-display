<?php
// Handles Django API requests

function lottery_api_request($path, $params = [], $method = 'GET') {
    $base_url = rtrim(LOTTERY_API_BASE_URL, '/');
    $path = '/' . ltrim($path, '/');
    $url = $base_url . $path;

    $headers = [
        'Accept' => 'application/json',
        'X-API-KEY' => LOTTERY_API_KEY,
    ];

    $args = [
        'method' => strtoupper($method),
        'headers' => $headers,
        'timeout' => 15,
    ];

    if ($args['method'] === 'GET' && !empty($params)) {
        $url = add_query_arg($params, $url);
    } elseif (!empty($params)) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body'] = wp_json_encode($params);
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        if (WP_DEBUG) {
            error_log('[Lottery API Error] ' . $response->get_error_message());
        }
        return null;
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status < 200 || $status >= 300) {
        if (WP_DEBUG) {
            error_log('[Lottery API Error] HTTP ' . $status . ' - ' . $body);
        }
        return null;
    }

    $decoded = json_decode($body, true);
    if ($decoded === null) {
        return null;
    }

    return $decoded;
}
?>
