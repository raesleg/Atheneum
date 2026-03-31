<?php
session_start();
$username = $_SESSION['username'] ?? null;

session_unset();
session_destroy();

// Start a fresh session just to show toast message
session_start();
if ($username) {
    $_SESSION['alert'] = "You've been logged out. See you soon, " . htmlspecialchars($username) . "!";
}

header("Location: index.php");
exit();
?>