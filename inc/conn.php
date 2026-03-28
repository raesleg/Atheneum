<?php
session_start(); //cache session for user login state
require_once(__DIR__ . "/db.php"); //db connection (local)
$conn = getDBConnection();
// remove echo for production, only for debugging
echo "<script>console.log(" . json_encode("db connected") . ");</script>";
?>