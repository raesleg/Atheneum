<?php
$pageTitle = "Edit Book";
$extraCSS  = ["admin/css/edit_book.css"];
$extraJS   = [["src" => "../assets/js/edit_book.js", "defer" => true]];
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

$book           = null;
$errorMsg       = '';
$allowed_genres = ['Fiction & Literature', 'Non-Fiction & Self Help', 'Science & Technology'];

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate book ID from GET
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $errorMsg = "Invalid or missing book ID.";
} else {
    $bookId = (int)$_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $errorMsg .= "Invalid request. Please reload the page and try again.<br>";
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Sanitisation & validatation
        $title       = trim($_POST['title']       ?? '');
        $author      = trim($_POST['author']      ?? '');
        $genre       = trim($_POST['genre']       ?? '');
        $price       = $_POST['price']    ?? '';
        $quantity    = $_POST['quantity'] ?? '';
        $description = trim($_POST['description'] ?? '');

        $raw_cover   = $_POST['existing_cover'] ?? '';
        $cover_image = null;
        if ($raw_cover !== '') {
            $norm = str_replace('\\', '/', $raw_cover);
            $norm = preg_replace('#\.\./#', '', $norm);
            // Fixed the path allow it to find the images
            if (strpos($norm, 'assets/images/') === 0) {
                $cover_image = $norm;
            }
        }

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
            $errorMsg .= "Price must be a more than 0.<br>";
        else
            $price = round(floatval($price), 2);

        if (!ctype_digit((string)$quantity) || intval($quantity) < 0)
            $errorMsg .= "Quantity must be a non-negative whole number.<br>";
        else
            $quantity = intval($quantity);

        if (strlen($description) > 2000)
            $errorMsg .= "Description must be under 2000 characters.<br>";

        // File upload check
        if (!empty($_FILES['cover_image']['name'])) {
            $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_ext, true)) {
                $errorMsg .= "Cover image must be JPG, PNG, WEBP or GIF.<br>";
            } else {
                // MANUAL CHECK: getimagesize() reads the file header
                $check = getimagesize($_FILES['cover_image']['tmp_name']);
                
                if ($check === false) {
                    $errorMsg .= "File is not a valid image.<br>";
                } else {
                    $mimeType = $check['mime'];
                    
                    if (!in_array($mimeType, $allowed_mime, true)) {
                        $errorMsg .= "Cover image content does not match its extension.<br>";
                    } else {
                        // Save to genre-specific folder
                        $uploadBase = dirname(__DIR__) . '/assets/images/';
                        $uploadDir  = $uploadBase . $genre . '/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                        // Use sanitized original name instead of uniqid
                        $originalName = pathinfo($_FILES['cover_image']['name'], PATHINFO_FILENAME);
                        $sanitizedName = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $originalName);
                        $filename = $sanitizedName . '.' . $ext;
                        $dest     = $uploadDir . $filename;

                        // Simple duplicate prevention same as add_book
                        if (file_exists($dest)) {
                            $filename = $sanitizedName . '_' . time() . '.' . $ext;
                            $dest     = $uploadDir . $filename;
                        }
                        
                        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                            $cover_image = 'assets/images/' . $genre . '/' . $filename;
                        } else {
                            $errorMsg .= "Failed to upload image.<br>";
                        }
                    }
                }
            }
        }

        if (empty($errorMsg)) {
            $stmt = $conn->prepare("
                UPDATE Products
                SET title = ?, author = ?, genre = ?, price = ?, quantity = ?, description = ?, cover_image = ?
                WHERE productId = ?
            ");
            $stmt->bind_param("sssdissi", $title, $author, $genre, $price, $quantity, $description, $cover_image, $bookId);
            if ($stmt->execute()) {
                $_SESSION['admin_flash'] = "success: \"$title\" was updated successfully.";
                header("Location: dashboard.php"); exit();
            } else {
                $errorMsg .= "Database error. Please try again.";
            }
            $stmt->close();
        }

        $book = [
            'productId'   => $bookId,
            'title'       => $title,
            'author'      => $author,
            'genre'       => $genre,
            'price'       => $price,
            'quantity'    => $quantity,
            'description' => $description,
            'cover_image' => $cover_image,
        ];
    }

    // Load from DB
    if ($book === null) {
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
}

include '../inc/nav.php';
?>

<main class="edit-book-wrapper">

    <header class="page-header">
        <a href="dashboard.php"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <h1><?= $book ? 'Editing: ' . htmlspecialchars($book['title']) : 'Edit Book' ?></h1>
    </header>

    <?php if ($errorMsg): ?>
        <div class="error-box" role="alert"><?= $errorMsg ?></div>
    <?php endif; ?>

    <?php if ($book): ?>
    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="existing_cover" value="<?= htmlspecialchars($book['cover_image'] ?? '') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-section">
                <label for="title">Title <span class="req">*</span></label>
                <input type="text" id="title" name="title" maxlength="150"
                       value="<?= htmlspecialchars($book['title']) ?>"
                       placeholder="Book title" required>
            </div>

            <div class="form-row">
                <div class="form-section">
                    <label for="author">Author <span class="req">*</span></label>
                    <input type="text" id="author" name="author" maxlength="100"
                           value="<?= htmlspecialchars($book['author']) ?>"
                           placeholder="Author name" required>
                </div>
                <div class="form-section">
                    <label for="genre">Genre <span class="req">*</span></label>
                    <select id="genre" name="genre" required>
                        <option value="">Select genre</option>
                        <?php foreach ($allowed_genres as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>"
                                <?= ($book['genre'] === $g) ? 'selected' : '' ?>>
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
                           value="<?= htmlspecialchars($book['price']) ?>"
                           placeholder="0.00" required>
                </div>
                <div class="form-section">
                    <label for="quantity">Stock Quantity <span class="req">*</span></label>
                    <input type="number" id="quantity" name="quantity" min="0"
                           value="<?= htmlspecialchars($book['quantity']) ?>"
                           required>
                </div>
            </div>

            <div class="form-section">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          placeholder="Brief synopsis or description"
                          maxlength="2000"><?= htmlspecialchars($book['description'] ?? '') ?></textarea>
            </div>

            <div class="form-section">
                <label for="cover-input">Cover Image <span class="label-hint">(leave blank to keep current)</span></label>

                <?php if (!empty($book['cover_image'])): ?>
                <div class="current-cover">
                    <img id="cover-preview"
                         src="<?= htmlspecialchars($baseUrl . '/' . asset_url($book['cover_image'])) ?>"
                         alt="Current cover">
                    <p class="cover-caption">Current cover — upload a new file to replace</p>
                </div>
                <?php else: ?>
                <img id="cover-preview" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Preview"
                     style="display:none;max-height:160px;border-radius:6px;margin-bottom:10px;object-fit:contain;">
                <?php endif; ?>

                <div class="cover-upload-area" id="upload-area">
                    <input type="file" name="cover_image" accept="image/*" id="cover-input">
                    <i class="bi bi-arrow-up-circle upload-icon"></i>
                    <p>Click to upload a new cover<br><small>JPG, PNG, WEBP or GIF</small></p>
                </div>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>

        </form>
    </div>
    <?php elseif (!$errorMsg): ?>
        <div class="error-box">Something went wrong loading the book.</div>
    <?php endif; ?>
</main>

<?php include '../inc/footer.php'; ?>
