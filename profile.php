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

    $stmt = $conn->prepare("UPDATE users SET fname=?, lname=?, email=? WHERE username=?");
    $stmt->bind_param("ssss", $fname, $lname, $email, $username);
    $stmt->execute();
    $stmt->close();
    header("Location: profile.php?updated=1");
    exit();
}
include 'inc/nav.php';
$stmt = $conn->prepare("SELECT username, fname, lname, email FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $uname = $row["username"] ?? '';
    $fname = $row["fname"] ?? '';
    $lname = $row["lname"] ?? '';
    $email = $row["email"] ?? '';

}
$stmt->close();
?>

<main>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success mx-4 mt-3">Profile updated successfully.</div>
    <?php endif; ?>

    <div class="profile-title">Your profile</div>
    <form method="post">
        <div class="profile">
            <div class="profile-details">
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