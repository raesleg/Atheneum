<?php
require_once 'includes/db.php';

$conn = getDBConnection();

echo "<h1>Database connected successfully</h1>";

$conn->close();
?>