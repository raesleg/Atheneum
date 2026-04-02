<?php
$pageTitle = "Profile";
$extraCSS = ["assets/css/profile.css"];
$extraJS = [["src" => "assets/js/main.js", "defer" => true]];
include 'inc/conn.php';
include 'inc/header.php';

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? null;
if (!$username) {
    header("Location: login.php");
    exit();
}

$errorMsg = [];
$alertMsg = "";
$success = true;
$profilePicPath = null;
if (isset($_SESSION['error'])) {
    $errorMsg = (array)$_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg[] = "Invalid request. Please reload the page and try again.";
        $success = false;
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $fname = sanitize_input($_POST['fname']);
    $lname = sanitize_input($_POST['lname']);
    $email = sanitize_input($_POST['email']);

    // file details
    $fileError = $_FILES['profile_pic']['error'];
    $fileSize  = $_FILES['profile_pic']['size'];
    $fileTmp   = $_FILES['profile_pic']['tmp_name'];
    $fileName  = $_FILES['profile_pic']['name'];

    // file uploaded successfully
    if ($fileError === 0){
        $tmpPath = $fileTmp;

        // Sanitise and validate file upload
        if ($tmpPath && file_exists($tmpPath)) {
            $uploadDir = 'uploads/profile_pic/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // regex to replace non alphanumeric characters with _
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($_FILES['profile_pic']['name']));
            $targetFilePath = $uploadDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

            $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
            $checkMime = getimagesize($_FILES['profile_pic']['tmp_name']);
            // check file type
            if ($checkMime !== false && in_array(strtolower($fileType), $allowTypes)) {
                // check file size is <2mb
                if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
                    $errorMsg[] = "File too large (max 2MB).";
                    $success = false;
                } else {
                    // add file to server
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFilePath)) {
                        $profilePicPath = $targetFilePath;
                    } else {
                        $errorMsg[] = "Error uploading file.";
                        $success = false;
                    }
                }
            } else {
                $errorMsg[] = "Invalid file type. Only images are allowed.";
                $success = false;
            }
        }
    }
    // File size exceed web server size set as 2MB
     elseif ($fileError === UPLOAD_ERR_INI_SIZE) {
        $errorMsg[] = "File too large (max 2MB).";
        $success = false;
    } 
    // File was not uploaded successfully
    elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
        $errorMsg[] = "Error uploading file.";
        $success = false;
    }     

    if ($success) {
        if ($profilePicPath != null) {
            $stmt = $conn->prepare("UPDATE Users SET fname=?, lname=?, email=?, profile_pic=? WHERE username=?");
            $stmt->bind_param("sssss", $fname, $lname, $email, $profilePicPath, $username);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE Users SET fname=?, lname=?, email=? WHERE username=?");
            $stmt->bind_param("ssss", $fname, $lname, $email, $username);
            $stmt->execute();
            $stmt->close();
        }
        $alertMsg = "Profile updated successfully.";
    }
}

include 'inc/nav.php';
$stmt = $conn->prepare("SELECT username, fname, lname, email, profile_pic FROM Users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $uname = $row["username"] ?? '';
    $fname = $row["fname"] ?? '';
    $lname = $row["lname"] ?? '';
    $email = $row["email"] ?? '';
    $profilePic = $row["profile_pic"] ?? 'assets/images/default-avatar.jpg';
}
$stmt->close();
?>

<main>
    <?php if ($alertMsg): ?>
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($alertMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="error">
        <?php foreach ($errorMsg as $error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
    <h1 class="profile-title">Your profile</h1>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="profile">
            <div class="profile-details">
                <div class="mb-3">
                    <label>Username</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($username) ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fname">First Name</label>
                    <input maxlength="45" type="text" id="fname" name="fname" value="<?= htmlspecialchars($fname) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="lname">Last Name</label>
                    <input maxlength="45" type="text" id="lname" name="lname" value="<?= htmlspecialchars($lname) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input maxlength="45" type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" class="form-control">
                </div>
            </div>
            <div class="profile-details">
                <div class="mb-3 text-center">
                    <img id="profileImg" src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture">
                </div>
                <div class="mb-3">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" name="profile_pic" id="profile_pic" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
        <div class="submit">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</main>

<?php include 'inc/footer.php'; ?>