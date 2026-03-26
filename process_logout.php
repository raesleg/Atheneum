<?php
session_start();
// Unset all session variables
session_unset();

// Destroy the session
session_destroy();
if (isset($_SESSION['loggedin'])) {
    $_SESSION['loggedin'] = false;
    
}
header("Location: login.php");
exit();
//echo "You have been logged out."
?>