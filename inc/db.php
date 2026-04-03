<?php
function getDBConnection() {
    $serverPath = '/var/www/private/db-config.ini';
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

    // Use '+08:00' for Singapore/Perth/Malaysia, or your specific offset
    $conn->query("SET time_zone = '+08:00'");

    return $conn;
}
?>