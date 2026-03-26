<?php
$pageTitle = "Add Book";
$extraCSS = ["css/add_book.css"];
$extraJS = ["src" => "../assets/js/add_book.js", "defer" => true];
include '../inc/conn.php'; 
include '../inc/header.php';

if (!$isLoggedIn) {
    header("Location: ../login.php"); exit();
}
$stmt = $conn->prepare("SELECT role FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row || $row['role'] !== 'admin') {
    header("Location: ../index.php"); exit();
}

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim(htmlspecialchars($_POST['title'] ?? ''));
    $author      = trim(htmlspecialchars($_POST['author'] ?? ''));
    $genre       = trim(htmlspecialchars($_POST['genre'] ?? ''));
    $price       = floatval($_POST['price'] ?? 0);
    $quantity    = intval($_POST['quantity'] ?? 0);
    $description = trim(htmlspecialchars($_POST['description'] ?? ''));
    $cover_image = null;

    if (empty($title))        $errorMsg .= "Title is required.<br>";
    if (empty($author))       $errorMsg .= "Author is required.<br>";
    if (empty($genre))        $errorMsg .= "Genre is required.<br>";
    if ($price <= 0)          $errorMsg .= "Price must be greater than 0.<br>";
    if ($quantity < 0)        $errorMsg .= "Quantity cannot be negative.<br>";

    if (!empty($_FILES['cover_image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errorMsg .= "Cover image must be JPG, PNG, WEBP or GIF.<br>";
        } else {
            $uploadDir = '../assets/images/covers/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = uniqid('book_') . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                $cover_image = 'assets/images/covers/' . $filename;
            } else {
                $errorMsg .= "Failed to upload image.<br>";
            }
        }
    }

    if (empty($errorMsg)) {
        $stmt = $conn->prepare("
            INSERT INTO Products (title, author, genre, price, quantity, description, cover_image)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssdiis", $title, $author, $genre, $price, $quantity, $description, $cover_image);

        if ($stmt->execute()) {
            $_SESSION['admin_flash'] = "success: \"$title\" was added successfully.";
            header("Location: dashboard.php"); exit();
        } else {
            $errorMsg .= "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<?php include '../inc/nav.php'; ?>

<div class="add-book-wrapper">

    <div class="page-header">
        <a href="dashboard.php"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <h1>Add New Book</h1>
    </div>

    <?php if ($errorMsg): ?>
        <div class="error-box"><?= $errorMsg ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">

            <div class="form-section">
                <label>Title <span class="req">*</span></label>
                <input type="text" name="title" maxlength="150"
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                       placeholder="e.g. Operating Systems and where to f&#824;i&#824;n&#824;d&#824; pass them" required>
            </div>

            <div class="form-row">
                <div class="form-section">
                    <label>Author <span class="req">*</span></label>
                    <input type="text" name="author" maxlength="100"
                           value="<?= htmlspecialchars($_POST['author'] ?? '') ?>"
                           placeholder="e.g. Chippy" required>
                </div>
                <div class="form-section">
                    <label>Genre <span class="req">*</span></label>
                    <select name="genre" required>
                        <option value="">Select genre</option>
                        <?php
                        $genres = ['Fiction','Non-Fiction','Mystery','Thriller','Romance','Sci-Fi','Fantasy',];
                        $selected = $_POST['genre'] ?? '';
                        foreach ($genres as $g):
                        ?>
                            <option value="<?= $g ?>" <?= $selected === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-section">
                    <label>Price (SGD) <span class="req">*</span></label>
                    <input type="number" name="price" min="0.01" step="0.01"
                           value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                           placeholder="0.00" required>
                </div>
                <div class="form-section">
                    <label>Stock Quantity <span class="req">*</span></label>
                    <input type="number" name="quantity" min="0"
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '0') ?>"
                           required>
                </div>
            </div>

            <div class="form-section">
                <label>Description</label>
                <textarea name="description" placeholder="Brief synopsis or description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-section">
                <label>Cover Image</label>
                <div class="cover-upload-area" id="upload-area">
                    <input type="file" name="cover_image" accept="image/*" id="cover-input">
                    <i class="bi bi-image upload-icon"></i>
                    <p>Click to upload cover image<br><small>JPG, PNG, WEBP or GIF</small></p>
                </div>
                <img id="cover-preview" src="" alt="Preview">
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Add Book</button>
            </div>

        </form>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
