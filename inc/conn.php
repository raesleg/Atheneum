<?php
session_start(); // cache session for user login state
require_once(__DIR__ . "/db.php"); 
$conn = getDBConnection();
?>