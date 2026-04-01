<?php
ob_start(); // buffer output so header() works even after HTML is sent
session_start(); // cache session for user login state
require_once(__DIR__ . "/db.php");
$conn = getDBConnection();

function asset_url($path) {
    if (!$path) return '';
    return implode('/', array_map('rawurlencode', explode('/', $path)));
}
?>
