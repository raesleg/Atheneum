<?php
include 'inc/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$data = validate_csrf_json();

$userId = (int)$_SESSION['userId'];
$password = $data['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Password is required to delete your account.']);
    exit;
}

// verify user pw before del
$stmt = $conn->prepare("SELECT password, role FROM Users WHERE userId = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    exit;
}

// stop admin del
if ($user['role'] === 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admin accounts cannot be deleted from the profile page.']);
    exit;
}

if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'Incorrect password.']);
    exit;
}

// deletion
$stmt = $conn->prepare("DELETE FROM Users WHERE userId = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    // end session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo json_encode(['success' => true]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Could not delete account. Please try again.']);
}
