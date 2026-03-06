<?php
require_once 'inc/db.php';

$conn = getDBConnection();

echo "<h1>Database connected successfully</h1>";

$conn->close();
?>