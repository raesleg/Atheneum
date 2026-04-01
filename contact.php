<?php
$pageTitle = "Contact Us";
$extraCSS  = ["assets/css/contact.css"];
include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';

$success = false;
$errors  = [];
$values  = [];

// Refund Logic to fetch logged-in user's eligible orders (paid or no duplicate refund request)
$eligibleOrders = [];
if ($isLoggedIn && $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT o.orderId, o.totalPrice, o.created_at
            FROM Orders o
            LEFT JOIN Refund r ON o.orderId = r.orderId
            WHERE o.userId        = ?
              AND o.paymentStatus = 'paid'
              AND r.refundId      IS NULL
            ORDER BY o.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $eligibleOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) {
        // Silently fail or log
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check — matches existing pattern in login.php
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $name    = trim(htmlspecialchars($_POST['name']    ?? ''));
        $email   = trim(htmlspecialchars($_POST['email']   ?? ''));
        $subject = trim(htmlspecialchars($_POST['subject'] ?? ''));
        $message = trim(htmlspecialchars($_POST['message'] ?? ''));
        $values  = compact('name', 'email', 'subject', 'message');

        // Refund Logic for shared message length check
        if (empty($message)) {
            $errors[] = 'Please enter a message or reason.';
        } elseif (strlen($message) < 10) {
            $errors[] = 'Your message must be at least 10 characters.';
        }

        // Refund logic to distinguish between refund requests and general messages
        if ($subject === 'refund') {
            $orderId = (int)($_POST['orderId'] ?? 0);
            
            if (!$isLoggedIn) {
                $errors[] = 'You must be logged in to request a refund.';
            } elseif ($orderId <= 0) {
                $errors[] = 'Please select a valid order.';
            } else {
                try {
                    $stmt = $conn->prepare("INSERT INTO Refund (orderId, userId, reason, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->bind_param("iis", $orderId, $userId, $message);
                    if ($stmt->execute()) {
                        $success = true;
                        $values  = [];
                    } else {
                        $errors[] = 'Could not submit refund request.';
                    }
                    $stmt->close();
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() === 1062) {
                        $errors[] = 'You have already submitted a refund request for this order.';
                    } else {
                        $errors[] = 'A database error occurred while processing your refund.';
                    }
                }
            }
        } else {
            // Original Contact Logic
            if (empty($name))                                $errors[] = 'Your name is required.';
            if (empty($email))                               $errors[] = 'Your email address is required.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

            if (empty($errors)) {
                $stmt = $conn->prepare("
                    INSERT INTO ContactMessages (name, email, subject, message)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("ssss", $name, $email, $subject, $message);
                $stmt->execute();
                $stmt->close();
                $success = true;
                $values  = [];
            }
        }
    }
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<main>

<!-- Header -->
<div class="contact-page-header">
    <div class="container-fluid px-4 px-lg-5 contact-header-inner">
        <p class="section-eyebrow">Get in Touch</p>
        <h1 class="section-heading">Contact Us</h1>
        <p class="contact-header-sub">
            Have a question about an order, a book recommendation, or a refund?
            We read every message and aim to reply within one business day.
        </p>
    </div>
</div>

<div class="container-fluid px-4 px-lg-5 contact-layout">

    <!-- Info sidebar -->
    <aside class="contact-info" aria-label="Contact information">
        <?php
        $contactCards = [
            ['icon'=>'envelope',  'title'=>'Email',    'value'=>'hello@atheneum.sg',       'sub'=>'We reply within 1 business day'],
            ['icon'=>'telephone', 'title'=>'Phone',    'value'=>'+65 6234 5678',            'sub'=>'Mon–Fri, 9 am–6 pm SGT'],
            ['icon'=>'geo-alt',   'title'=>'Address',  'value'=>'1 Raffles Place, #20-01',  'sub'=>'Singapore 048616'],
            ['icon'=>'clock',     'title'=>'Hours',    'value'=>'Mon–Fri: 9 am–6 pm',       'sub'=>'Sat: 10 am–2 pm · Sun: Closed'],
        ];
        foreach ($contactCards as $c): ?>
        <div class="contact-info-card">
            <div class="contact-info-icon" aria-hidden="true">
                <i class="bi bi-<?= $c['icon'] ?>"></i>
            </div>
            <div>
                <p class="contact-info-title"><?= htmlspecialchars($c['title']) ?></p>
                <p class="contact-info-value"><?= htmlspecialchars($c['value']) ?></p>
                <p class="contact-info-sub"><?= htmlspecialchars($c['sub']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- OpenStreetMap -->
        <div class="contact-map">
            <iframe
                src="https://www.openstreetmap.org/export/embed.html?bbox=103.8480,1.2830,103.8560,1.2870&layer=mapnik&marker=1.2847,103.8521"
                title="Map showing Atheneum at 1 Raffles Place, Singapore"
                loading="lazy"
                aria-hidden="true"
                tabindex="-1">
            </iframe>
        </div>
    </aside>

    <!-- Contact form -->
    <div class="contact-form-col">

        <?php if ($success): ?>
        <div class="contact-success" role="alert" aria-live="polite">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>Success!</strong>
                <p>Your request has been sent. We will get back to you within one business day.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="contact-errors" role="alert" aria-live="assertive">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <ul>
                <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="contact.php" class="contact-form" novalidate
              aria-label="Contact form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="contactName" class="form-label-custom">
                        Your Name <span class="req" aria-hidden="true">*</span>
                        <span class="visually-hidden">(required)</span>
                    </label>
                    <input type="text" id="contactName" name="name"
                           class="form-input-custom"
                           value="<?= htmlspecialchars($values['name'] ?? '') ?>"
                           placeholder="Jane Doe"
                           autocomplete="name"
                           required>
                </div>
                <div class="form-group">
                    <label for="contactEmail" class="form-label-custom">
                        Email Address <span class="req" aria-hidden="true">*</span>
                        <span class="visually-hidden">(required)</span>
                    </label>
                    <input type="email" id="contactEmail" name="email"
                           class="form-input-custom"
                           value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                           placeholder="jane@example.com"
                           autocomplete="email"
                           required>
                </div>
            </div>

            <!-- Refund logic to convert subject to a select dropdown -->
            <div class="form-group">
                <label for="contactSubject" class="form-label-custom">How can we help? <span class="req">*</span></label>
                <select id="contactSubject" name="subject" class="form-input-custom" required>
                    <option value="">-- Choose a subject --</option>
                    <option value="enquiry" <?= ($values['subject'] ?? '') === 'enquiry' ? 'selected' : '' ?>>General Enquiry</option>
                    <option value="refund" <?= ($values['subject'] ?? '') === 'refund' ? 'selected' : '' ?>>Request a Refund</option>
                    <option value="feedback" <?= ($values['subject'] ?? '') === 'feedback' ? 'selected' : '' ?>>Feedback</option>
                </select>
            </div>

            <!-- Refund logic. Added refund section -->
            <div id="refund-section" class="refund-box form-group" style="display: none;">
                <label for="orderId" class="form-label-custom">Select Order <span class="req">*</span></label>
                <?php if (!$isLoggedIn): ?>
                    <p class="small text-danger">Please log in to view your orders.</p>
                <?php elseif (empty($eligibleOrders)): ?>
                    <p class="small text-muted">No orders eligible for refund found.</p>
                <?php else: ?>
                    <select name="orderId" id="orderId" class="form-input-custom">
                        <option value="">-- Choose an Order --</option>
                        <?php foreach ($eligibleOrders as $order): ?>
                            <option value="<?= $order['orderId'] ?>">
                                Order #<?= $order['orderId'] ?> ($<?= number_format($order['totalPrice'], 2) ?>) - <?= date('d M Y', strtotime($order['created_at'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="contactMessage" id="msgLabel" class="form-label-custom">
                    Message <span class="req" aria-hidden="true">*</span>
                    <span class="visually-hidden">(required)</span>
                </label>
                <textarea id="contactMessage" name="message"
                          class="form-input-custom"
                          rows="7"
                          placeholder="Tell us how we can help…"
                          required><?= htmlspecialchars($values['message'] ?? '') ?></textarea>
            </div>

            <div class="form-footer">
                <p class="form-note">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    Your information is never shared with third parties.
                </p>
                <button type="submit" id="submitBtn" class="btn-hero-primary">
                    <i class="bi bi-send" aria-hidden="true"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

</main>

<script src="assets/js/contact.js"></script>
<?php include 'inc/footer.php'; ?>