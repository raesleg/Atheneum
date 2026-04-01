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

    return $conn;
}
?>