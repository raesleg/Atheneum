<?php
$pageTitle = "Profile";
$extraCSS = [
    "assets/css/login.css"
];
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

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = sanitize_input($_POST['fname']);
    $lname = sanitize_input($_POST['lname']);
    $email = sanitize_input($_POST['email']);
    $profilePicPath = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $uploadDir = 'uploads/profile_pic/';

        // Make directory if not exists
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileName = time() . '_' . basename($_FILES['profile_pic']['name']);
        $targetFile = $uploadDir . $fileName;

        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (in_array($fileType, $allowed)) {
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                $profilePicPath = $targetFile;
            }
        }
    }

    if ($profilePicPath) {
        $stmt = $conn->prepare("UPDATE Users SET fname=?, lname=?, email=?, profile_pic=? WHERE username=?");
        $stmt->bind_param("sssss", $fname, $lname, $email, $profilePicPath, $username);
    } else {
        $stmt = $conn->prepare("UPDATE Users SET fname=?, lname=?, email=? WHERE username=?");
        $stmt->bind_param("ssss", $fname, $lname, $email, $username);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: profile.php?updated=1");
    exit();
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
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success mx-4 mt-3">
            Profile updated successfully.
        </div>
    <?php endif; ?>

    <div class="profile-title">Your profile</div>
    <form method="post" enctype="multipart/form-data">
        <div class="profile">
            <div class="profile-details">
                <div class="mb-3 text-center">
                    <img id="profileImg" src="<?= htmlspecialchars($profilePic) ?>" 
                        style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd;" 
                        alt="Profile Picture">
                </div>

                <div class="mb-3">
                    <label for="profile_pic">Change Profile Picture:</label>
                    <input type="file" name="profile_pic" id="profile_pic" class="form-control" accept="image/*">
                </div>
                <div class="mb-3">
                    <label>First Name</label>
                    <input type="text" name="fname" value="<?= htmlspecialchars($fname) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Last Name</label>
                    <input type="text" name="lname" value="<?= htmlspecialchars($lname) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" class="form-control">
                </div>
            </div>
            <div class="profile-details">
                <div class="mb-3">
                    <label>Username</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($username) ?></p>
                </div>
            </div>
            <div class="submit">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </div>
    </form>
</main>

<?php include 'inc/footer.php'; ?>