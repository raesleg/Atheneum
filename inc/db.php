<?php
function getDBConnection() {
    // THIS IS LOCAL DB TESTING, DELETE WHEN PRODUCTION
    // $config = parse_ini_file(__DIR__ . '/../config/db-config.ini');
    // GOOGLE SHARED CLOUD DB
    $config = parse_ini_file('/var/www/private/db-config.ini');

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