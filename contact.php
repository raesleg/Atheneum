<?php
$pageTitle = "Contact Us";
$extraCSS  = ["assets/css/contact.css"];
include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';

$success = false;
$errors  = [];
$values  = [];

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

        if (empty($name))                                $errors[] = 'Your name is required.';
        if (empty($email))                               $errors[] = 'Your email address is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (empty($message))                             $errors[] = 'Please enter a message.';
        elseif (strlen($message) < 10)                   $errors[] = 'Your message must be at least 10 characters.';

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
            Have a question about an order, a book recommendation, or just want to say hello?
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
                aria-label="Map of Atheneum store location">
            </iframe>
        </div>
    </aside>

    <!-- Contact form -->
    <div class="contact-form-col">

        <?php if ($success): ?>
        <div class="contact-success" role="alert" aria-live="polite">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>Message sent!</strong>
                <p>Thank you for reaching out. We will get back to you within one business day.</p>
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

        <form method="POST" action="<?= $baseUrl ?>/contact.php" class="contact-form" novalidate
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
                           required aria-required="true">
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
                           required aria-required="true">
                </div>
            </div>

            <div class="form-group">
                <label for="contactSubject" class="form-label-custom">Subject</label>
                <input type="text" id="contactSubject" name="subject"
                       class="form-input-custom"
                       value="<?= htmlspecialchars($values['subject'] ?? '') ?>"
                       placeholder="Order enquiry, book recommendation…">
            </div>

            <div class="form-group">
                <label for="contactMessage" class="form-label-custom">
                    Message <span class="req" aria-hidden="true">*</span>
                    <span class="visually-hidden">(required)</span>
                </label>
                <textarea id="contactMessage" name="message"
                          class="form-input-custom"
                          rows="7"
                          placeholder="Tell us how we can help…"
                          required
                          aria-required="true"><?= htmlspecialchars($values['message'] ?? '') ?></textarea>
            </div>

            <div class="form-footer">
                <p class="form-note">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    Your information is never shared with third parties.
                </p>
                <button type="submit" class="btn-hero-primary">
                    <i class="bi bi-send" aria-hidden="true"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

</main>

<?php include 'inc/footer.php'; ?>
