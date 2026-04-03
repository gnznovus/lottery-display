<?php
// Configuration file for Lottery Display Plugin

// Django API Endpoint & Access Key
// Example for local Docker: http://localhost:8000
// Example for production: https://api.yourdomain.com
if (!defined('LOTTERY_API_BASE_URL')) {
    define('LOTTERY_API_BASE_URL', 'http://localhost:8000');
}
if (!defined('LOTTERY_API_KEY')) {
    define('LOTTERY_API_KEY', 'change-me');
}

// Default Settings
if (!defined('DEFAULT_LOTTERY_TYPE')) {
    define('DEFAULT_LOTTERY_TYPE', 'huayrat_display');
}
?>