<?php
$pageTitle = "FAQ";
$extraCSS  = ["assets/css/faq.css"];
include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';

// Fetch all active FAQs grouped by category
$result = $conn->query("
    SELECT faqId, question, answer, category
    FROM FAQ
    WHERE is_active = 1
    ORDER BY category, display_order ASC
");

$faqs = [];
while ($row = $result->fetch_assoc()) {
    $faqs[$row['category']][] = $row;
}
?>

<main>

<!-- Page header -->
<div class="faq-page-header">
    <div class="container-fluid px-4 px-lg-5 faq-header-inner">
        <p class="faq-eyebrow">Help Centre</p>
        <h1 class="faq-title">Frequently Asked Questions</h1>
        <p class="faq-subtitle">
            Can't find what you're looking for? <a href="<?= $baseUrl ?>/contact.php">Contact us</a> and we'll get back to you within one business day.
        </p>
    </div>
</div>

<!-- Category navigation pills -->
<div class="faq-category-nav container-fluid px-4 px-lg-5">
    <?php foreach (array_keys($faqs) as $cat): ?>
    <a href="#cat-<?= htmlspecialchars(preg_replace('/\s+/', '-', strtolower($cat))) ?>"
       class="faq-cat-pill">
        <?= htmlspecialchars($cat) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- FAQ sections -->
<div class="faq-content container-fluid px-4 px-lg-5">

    <?php if (empty($faqs)): ?>
    <p class="faq-empty">No FAQs available yet. Check back soon.</p>

    <?php else: ?>
    <?php foreach ($faqs as $category => $items): ?>

    <section class="faq-section"
             id="cat-<?= htmlspecialchars(preg_replace('/\s+/', '-', strtolower($category))) ?>"
             aria-labelledby="heading-<?= htmlspecialchars(preg_replace('/\s+/', '-', strtolower($category))) ?>">

        <h2 class="faq-section-heading"
            id="heading-<?= htmlspecialchars(preg_replace('/\s+/', '-', strtolower($category))) ?>">
            <?= htmlspecialchars($category) ?>
        </h2>

        <div class="faq-accordion" role="list">
            <?php foreach ($items as $i => $faq): ?>
            <div class="faq-item" role="listitem">
                <button class="faq-question"
                        aria-expanded="false"
                        aria-controls="answer-<?= $faq['faqId'] ?>"
                        id="question-<?= $faq['faqId'] ?>">
                    <span><?= htmlspecialchars($faq['question']) ?></span>
                    <i class="bi bi-plus faq-icon" aria-hidden="true"></i>
                </button>
                <div class="faq-answer"
                     id="answer-<?= $faq['faqId'] ?>"
                     role="region"
                     aria-labelledby="question-<?= $faq['faqId'] ?>"
                     hidden>
                    <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Still need help CTA -->
    <div class="faq-cta">
        <div class="faq-cta-icon" aria-hidden="true">
            <i class="bi bi-chat-dots"></i>
        </div>
        <h2 class="faq-cta-heading">Still have a question?</h2>
        <p class="faq-cta-sub">Our team is happy to help. We reply within one business day.</p>
        <a href="<?= $baseUrl ?>/contact.php" class="faq-cta-btn">
            <i class="bi bi-envelope" aria-hidden="true"></i> Contact Us
        </a>
    </div>

</div>
</main>

<script>
// Accessible accordion
document.querySelectorAll('.faq-question').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var expanded = this.getAttribute('aria-expanded') === 'true';
        var answer   = document.getElementById(this.getAttribute('aria-controls'));
        var icon     = this.querySelector('.faq-icon');

        // Close all others in same section
        var section = this.closest('.faq-accordion');
        section.querySelectorAll('.faq-question').forEach(function(other) {
            if (other !== btn) {
                other.setAttribute('aria-expanded', 'false');
                var otherAnswer = document.getElementById(other.getAttribute('aria-controls'));
                if (otherAnswer) otherAnswer.hidden = true;
                var otherIcon = other.querySelector('.faq-icon');
                if (otherIcon) { otherIcon.classList.remove('bi-dash'); otherIcon.classList.add('bi-plus'); }
            }
        });

        // Toggle this one
        this.setAttribute('aria-expanded', !expanded);
        answer.hidden = expanded;
        if (expanded) {
            icon.classList.remove('bi-dash');
            icon.classList.add('bi-plus');
        } else {
            icon.classList.remove('bi-plus');
            icon.classList.add('bi-dash');
        }
    });
});
</script>

<?php include 'inc/footer.php'; ?>
