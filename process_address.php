<?php
ob_start();
include 'inc/conn.php';
ob_end_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['userId'];

// GET addresses
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT addressId, label, address_line1, address_line2,
               city, state, postal_code, country
        FROM Addresses
        WHERE userId = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'addresses' => $rows]);
    exit;
}

// POST save new address
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = validate_csrf_json();
    $newToken = $_SESSION['csrf_token'];

    $label         = trim($data['label']         ?? '');
    $address_line1 = trim($data['address_line1'] ?? '');
    $address_line2 = trim($data['address_line2'] ?? '');
    $city          = trim($data['city']          ?? '');
    $state         = trim($data['state']         ?? '');
    $postal_code   = trim($data['postal_code']   ?? '');
    $country       = trim($data['country']       ?? '');

    if (!$label || !$address_line1 || !$city || !$postal_code || !$country) {
        echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.', 'csrf_token' => $newToken]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO Addresses (userId, label, address_line1, address_line2, city, state, postal_code, country)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssssss",
        $userId, $label, $address_line1, $address_line2,
        $city, $state, $postal_code, $country
    );
    $stmt->execute();
    $newId = $conn->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'csrf_token' => $newToken,
        'address' => [
            'addressId'     => $newId,
            'label'         => $label,
            'address_line1' => $address_line1,
            'address_line2' => $address_line2,
            'city'          => $city,
            'state'         => $state,
            'postal_code'   => $postal_code,
            'country'       => $country,
        ]
    ]);
    exit;
}

// DELETE an address
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = validate_csrf_json();
    $newToken = $_SESSION['csrf_token'];

    $addressId = (int)($data['addressId'] ?? 0);

    if (!$addressId) {
        echo json_encode(['success' => false, 'error' => 'Invalid address ID', 'csrf_token' => $newToken]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM Addresses WHERE addressId = ? AND userId = ?");
    $stmt->bind_param("ii", $addressId, $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'csrf_token' => $newToken]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request method']);
