<?php
ob_start(); // buffer output so header() works even after HTML is sent
session_start(); // cache session for user login state
require_once(__DIR__ . "/db.php");
$conn = getDBConnection();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function asset_url($path) {
    if (!$path) return '';
    return implode('/', array_map('rawurlencode', explode('/', $path)));
}

function validate_csrf_json() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (
        !is_array($data) ||
        !isset($data['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])
    ) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token.']);
        exit;
    }

    return $data;
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}
?>
