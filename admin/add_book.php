<?php
$pageTitle = "Add Book";
$extraCSS  = ["admin/css/add_book.css"];
$extraJS   = [["src" => "../assets/js/add_book.js", "defer" => true]];
include '../inc/conn.php';
include '../inc/header.php';

if (!$isLoggedIn) {
    header("Location: ../login.php"); exit();
}
$stmt = $conn->prepare("SELECT role FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row || $row['role'] !== 'admin') {
    header("Location: ../index.php"); exit();
}

$errorMsg       = '';
$allowed_genres = ['Fiction', 'Non-Fiction', 'Mystery', 'Thriller', 'Romance', 'Sci-Fi', 'Fantasy'];

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg .= "Invalid request. Please reload the page and try again.<br>";
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate after use

    //  Sanitisation & validatation
    $title       = trim($_POST['title']       ?? '');
    $author      = trim($_POST['author']      ?? '');
    $genre       = trim($_POST['genre']       ?? '');
    $price       = $_POST['price']    ?? '';
    $quantity    = $_POST['quantity'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $cover_image = null;

    if (empty($title))
        $errorMsg .= "Title is required.<br>";
    elseif (strlen($title) > 150)
        $errorMsg .= "Title must be under 150 characters.<br>";

    if (empty($author))
        $errorMsg .= "Author is required.<br>";
    elseif (strlen($author) > 100)
        $errorMsg .= "Author must be under 100 characters.<br>";

    if (!in_array($genre, $allowed_genres, true))
        $errorMsg .= "Please select a valid genre.<br>";

    if (!is_numeric($price) || floatval($price) <= 0)
        $errorMsg .= "Price must more than 0.<br>";
    else
        $price = round(floatval($price), 2);

    if (!ctype_digit((string)$quantity) || intval($quantity) < 0)
        $errorMsg .= "Quantity must be a non-negative whole number.<br>";
    else
        $quantity = intval($quantity);

    if (strlen($description) > 2000)
        $errorMsg .= "Description must be under 2000 characters.<br>";

    if (!empty($_FILES['cover_image']['name'])) {
        $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext, true)) {
            $errorMsg .= "Cover image must be JPG, PNG, WEBP or GIF.<br>";
        } else {
            $check = getimagesize($_FILES['cover_image']['tmp_name']);
            
            if ($check === false) {
                $errorMsg .= "File is not a valid image.<br>";
            } else {
                $mimeType = $check['mime'];
                
                if (!in_array($mimeType, $allowed_mime, true)) {
                    $errorMsg .= "Cover image content does not match its extension.<br>";
                } else {
                    $uploadDir = '../assets/images/covers/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    $filename = uniqid('book_') . '.' . $ext;
                    $dest     = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                        $cover_image = 'assets/images/covers/' . $filename;
                    } else {
                        $errorMsg .= "Failed to upload image.<br>";
                    }
                }
            }
        }
    }

    if (empty($errorMsg)) {
        $stmt = $conn->prepare("
            INSERT INTO Products (title, author, genre, price, quantity, description, cover_image)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssdiss", $title, $author, $genre, $price, $quantity, $description, $cover_image);
        if ($stmt->execute()) {
            $_SESSION['admin_flash'] = "success: \"$title\" was added successfully.";
            header("Location: dashboard.php"); exit();
        } else {
            $errorMsg .= "Database error. Please try again.";
        }
        $stmt->close();
    }
}

include '../inc/nav.php';
?>

<main class="add-book-wrapper">

    <header class="page-header">
        <a href="dashboard.php"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <h1>Add New Book</h1>
    </header>

    <?php if ($errorMsg): ?>
        <div class="error-box" role="alert"><?= $errorMsg ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-section">
                <label for="title">Title <span class="req">*</span></label>
                <input type="text" id="title" name="title" maxlength="150"
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                       placeholder="e.g. Operating Systems and where to find pass them" required>
            </div>

            <div class="form-row">
                <div class="form-section">
                    <label for="author">Author <span class="req">*</span></label>
                    <input type="text" id="author" name="author" maxlength="100"
                           value="<?= htmlspecialchars($_POST['author'] ?? '') ?>"
                           placeholder="e.g. Chippy" required>
                </div>
                <div class="form-section">
                    <label for="genre">Genre <span class="req">*</span></label>
                    <select id="genre" name="genre" required>
                        <option value="">Select genre</option>
                        <?php
                        $selected = $_POST['genre'] ?? '';
                        foreach ($allowed_genres as $g):
                        ?>
                            <option value="<?= htmlspecialchars($g) ?>"
                                <?= ($selected === $g) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-section">
                    <label for="price">Price (SGD) <span class="req">*</span></label>
                    <input type="number" id="price" name="price" min="0.01" step="0.01"
                           value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                           placeholder="0.00" required>
                </div>
                <div class="form-section">
                    <label for="quantity">Stock Quantity <span class="req">*</span></label>
                    <input type="number" id="quantity" name="quantity" min="0"
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '0') ?>"
                           required>
                </div>
            </div>

            <div class="form-section">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          placeholder="Brief synopsis or description"
                          maxlength="2000"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-section">
                <label for="cover-input">Cover Image</label>
                <div class="cover-upload-area" id="upload-area">
                    <input type="file" name="cover_image" accept="image/*" id="cover-input">
                    <i class="bi bi-image upload-icon"></i>
                    <p>Click to upload cover image<br><small>JPG, PNG, WEBP or GIF</small></p>
                </div>
                <img id="cover-preview" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Preview">
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Add Book</button>
            </div>

        </form>
    </div>
</main>

<?php include '../inc/footer.php'; ?>
