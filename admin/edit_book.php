<?php
$pageTitle = "Edit Book";
include '../inc/header.php';
include '../inc/nav.php';

// Admin only
if (!$isLoggedIn) {
    header("Location: ../login.php"); exit();
}
$stmt = $conn->prepare("SELECT role FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row || $row['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$book = null;
$errorMsg = '';

// Check for book ID from GET request
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $errorMsg = "Invalid or missing book ID.";
} else {
    $bookId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM Products WHERE productId = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $book = $result->fetch_assoc();
    } else {
        $errorMsg = "Book not found.";
    }
    $stmt->close();
}

include '../inc/nav.php';
?>

<div class="admin-wrapper" style="padding: 2rem;">
    <h1><?= $book ? 'Editing: ' . htmlspecialchars($book['title']) : $errorMsg ?></h1>
</div>

<?php include '../inc/footer.php'; ?>