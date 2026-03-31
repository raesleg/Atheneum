<?php
function getDBConnection() {
    // Try local config first (XAMPP), then fall back to server path (Google Cloud)
    $localPath  = __DIR__ . '/../config/db-config.ini';
    $serverPath = '/var/www/private/db-config.ini';
    $configPath = file_exists($localPath) ? $localPath : $serverPath;

    $config = parse_ini_file($serverPath);
    if (!$config) {
        die("Database config file not found.");
    }

    $conn = new mysqli(
        $config['servername'],
        $config['username'],
        $config['password'],
        $config['dbname']
    );

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>