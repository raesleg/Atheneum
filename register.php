<?php
$pageTitle = "Register";
$extraCSS = [
    "assets/css/login.css"
];

$extraJS = [
    ["src" => "https://www.google.com/recaptcha/api.js", "async" => true, "defer" => true],
    ["src" => "assets/js/main.js", "defer" => true]
];
include 'inc/conn.php'; 
include 'inc/header.php';
include "inc/nav.php";

//CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMsg = [];
$success = true;
if (isset($_SESSION['error'])) {
    $errorMsg = (array)$_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg[] = "Invalid request. Please reload the page and try again.";
        $success = false;
    }
    // Optionally regenerate the token after a successful check
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $recaptcha_secret = "6LdCK5wsAAAAAC12fTpTk88DcWeDc5niNbSWNbLd";
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
    $result = json_decode($verify);

    if (!$result->success) {
        $errorMsg[] = "Please complete the reCAPTCHA.";
        // header("Location: login.php");
        // exit();
    } 
    else {
        
        if (empty($_POST["username"])) {
            $errorMsg[] = "Username is required.";
            $success = false;
        } else {
            $username = sanitize_input($_POST["username"]);
            if (!preg_match('/^[A-Za-z0-9_]{3,45}$/', $username)) {
                $errorMsg[] = "Username must be 3-45 characters and contain only letters, numbers, and underscores.";
                $success = false;
            }
        }
        if (!empty($_POST["fname"])) {
            $fname = sanitize_input($_POST["fname"]);
            if (!preg_match("/^[A-Za-z\s'-]{1,45}$/", $fname)) {
                $errorMsg[] = "Invalid first name format.";
                $success = false;
            }
        }
        if (empty($_POST["lname"])) {
            $errorMsg[] = "Last name is required.";
            $success = false;
        } else {
            $lname = sanitize_input($_POST["lname"]);
            if (!preg_match("/^[A-Za-z\s'-]{1,45}$/", $lname)) {
                $errorMsg[] = "Invalid last name format.";
                $success = false;
            }
        }
        if (empty($_POST["email"])) {
            $errorMsg[] = "Email is required.";
            $success = false;
        } else {
            $email = sanitize_input($_POST["email"]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorMsg[] = "Invalid email format.";
                $success = false;
            }
        }
        if (empty($_POST["pwd"]) || empty($_POST["pwd_confirm"])) {
            $errorMsg[] = "Password is required.";
            $success = false;
        } else {
            if ($_POST["pwd"] !== $_POST["pwd_confirm"]) {
                $errorMsg[] = "Passwords do not match.";
                $success = false;
            } elseif (strlen($_POST["pwd"]) < 8) {
                $errorMsg[] = "Password must be at least 8 characters long.";
                $success = false;
            } else {
                $pwd_hashed = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
            }
        }
        if ($success) {
            saveMemberToDB();
            
        }
        if ($success) {
            session_regenerate_id(true);
            $_SESSION['alert'] = "Account created successfully. Please login";
            header("Location: login.php");
                exit();
        }
    }
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function saveMemberToDB() {
    global $conn, $username, $fname, $lname, $email, $pwd_hashed, $errorMsg, $success;

    if (!$conn) {
        $errorMsg[] = "Database connection failed.";
        $success = false;
        return;
    }
    $profilePicPath = 'assets/images/default-avatar.jpg';
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
    $uploadDir = 'uploads/profile_pic/';
    
    if (!is_dir($uploadDir)){
        mkdir($uploadDir, 0755, true);
    } 

    $fileName = time() . '_' . basename($_FILES['profile_pic']['name']);
    $targetFilePath = $uploadDir . $fileName;

    // Check file type (Simple check)
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');

    if (in_array(strtolower($fileType), $allowTypes)) {
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFilePath)) {
            $profilePicPath = $targetFilePath; 
        }
    }
}

    // Check duplicate username or email
    $checkStmt = $conn->prepare("SELECT username FROM Users WHERE username = ? OR email = ?");
    if (!$checkStmt) {
        $errorMsg[] = "Prepare failed: " . $conn->error;
        $success = false;
        return;
    }

    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $errorMsg[] = "Username already exists.";
        $success = false;
        $checkStmt->close();
        return;
    }
    $checkStmt->close();

    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO Users (username, fname, lname, email, password, profile_pic)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        $errorMsg[] = "Prepare failed: " . $conn->error;
        $success = false;
        return;
    }

    $stmt->bind_param("ssssss", $username, $fname, $lname, $email, $pwd_hashed, $profilePicPath);

    if (!$stmt->execute()) {
        $errorMsg[] = "Execute failed: " . $stmt->error;
        $success = false;
    }

    $stmt->close();
}

?>

<main>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Sign Up</h1>
            </div>
            <div class="error"><?php foreach ($errorMsg as $error): ?>
                <?php echo htmlspecialchars($error); ?>
                <?php endforeach; ?>
            </div>
            <form method="post"  enctype="multipart/form-data">
                <div id="step1">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="mb-3">
                        <label class="form-label" for="username">Username:</label>
                        <input required maxlength="45" class="form-control" type="text" id="username" name="username"
                        placeholder="Enter username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email:</label>
                        <input required maxlength="45" class="form-control" type="email" id="email" name="email"
                        placeholder="Enter email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="fname">First name:</label>
                        <input maxlength="45" class="form-control" type="text" id="fname" name="fname"
                        placeholder="Enter first name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="lname">Last name:</label>
                        <input required maxlength="45" class="form-control" type="text" id="lname" name="lname"
                        placeholder="Enter last name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pwd">Password:</label>
                        <input required minlength="8" maxlength="64" class="form-control" type="password" id="pwd" name="pwd"
                        placeholder="Enter password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pwd_confirm">Confirm Password:</label>
                        <input required minlength="8" maxlength="64" class="form-control" type="password" id="pwd_confirm" name="pwd_confirm"
                        placeholder="Confirm password">
                    </div>
                    <div class="mb-3 text-center" >
                        <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                    </div>
                </div>
                        

                <div id="step2" style="display: none;">
                    <div class="card-header mb-3">
                    <h3>Step 2: Upload Profile Picture</h3>
                    </div>

                    <div class="mb-3 text-center">
                    <img id="imgPreview" src="assets/images/default-avatar.jpg" 
                    style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd;" 
                    alt="Profile Preview">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="profile_pic">Select Image (JPG, PNG):</label>
                        <input class="form-control" type="file" id="profile_pic" name="profile_pic" 
                        accept="image/*">
                    </div>
                    <div class="g-recaptcha text-center" data-sitekey="6LdCK5wsAAAAAF-um6W9E8AJCCQh8rLHjr2F9gkF"></div>
                    <div class="mb-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" id="backBtn">Back</button>
                        <!-- <div class="g-recaptcha" data-sitekey="6LdCK5wsAAAAAF-um6W9E8AJCCQh8rLHjr2F9gkF"></div> -->
                        <button class="btn btn-success" type="submit">Complete Registration</button>
                    </div>
                </div>
                <!-- <div class="mb-3 submit">
                <button class="btn btn-primary" type="submit">Submit</button>
                </div> -->
            </form>
            <div>Already have an account? <a href="login.php">Sign in here! </a>
            </div>
        </div>
    </div>
</main>

<?php include 'inc/footer.php'; ?>
