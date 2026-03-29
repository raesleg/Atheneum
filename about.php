<?php
$pageTitle = "About Us";
$extraCSS  = ["assets/css/about.css"];
include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';

$totalBooks = (int)$conn->query("SELECT COUNT(*) AS c FROM Products")->fetch_assoc()['c'];
?>

<main>

<!-- Hero -->
<section class="about-hero" aria-labelledby="about-hero-heading">
    <div class="container-fluid px-4 px-lg-5 about-hero-inner">
        <p class="section-eyebrow">Our Story</p>
        <h1 class="section-heading about-main-heading" id="about-hero-heading">
            Books that inspire,<br>
            <em>delivered to your door.</em>
        </h1>
        <p class="about-hero-sub">
            Atheneum was founded in 2019 by a small group of avid readers who believed that
            Singapore deserved a bookstore that was as thoughtful about curation as it was about
            service. What started as a passion project has grown into the island's most trusted
            independent online bookstore.
        </p>
    </div>
</section>

<!-- Mission & Values -->
<section class="about-section" aria-labelledby="mission-heading">
    <div class="container-fluid px-4 px-lg-5">
        <div class="about-two-col">
            <!-- Mission copy -->
            <div>
                <p class="section-eyebrow">Our Mission</p>
                <h2 class="section-heading" id="mission-heading">Reading without barriers</h2>
                <hr class="section-rule" aria-hidden="true">
                <p class="about-body">
                    We believe that great books should be accessible to everyone. From timeless
                    classics to contemporary voices, our catalogue is built to reflect the full
                    breadth of human experience. We keep our prices fair, our delivery fast,
                    and our service personal.
                </p>
                <p class="about-body">
                    Based in Singapore, we ship to every corner of the island with same-week
                    delivery on all in-stock titles. Free delivery applies on all orders
                    above $50.
                </p>
                <p class="about-body">
                    Every title in our catalogue goes through a manual review process. We ask
                    ourselves: Is this worth a reader's time? If the answer is yes, it earns
                    a place on our shelves.
                </p>
            </div>

            <!-- Values grid -->
            <div class="about-values-grid" role="list">
                <?php
                $values = [
                    ['icon'=>'collection',     'title'=>'Curation',    'desc'=>'Every title is handpicked. No filler — only books we genuinely believe are worth reading.'],
                    ['icon'=>'heart',         'title'=>'Community',   'desc'=>'We are readers first. Our reviews and FAQs are written by people who love books.'],
                    ['icon'=>'truck',         'title'=>'Reliability', 'desc'=>'Orders ship within one business day. We take fulfilment personally.'],
                    ['icon'=>'shield-check',  'title'=>'Trust',       'desc'=>'Secure checkout, easy returns, and a team that actually answers emails within 24 hours.'],
                ];
                foreach ($values as $v): ?>
                <div class="value-card" role="listitem">
                    <div class="value-icon" aria-hidden="true">
                        <i class="bi bi-<?= $v['icon'] ?>"></i>
                    </div>
                    <h3 class="value-title"><?= $v['title'] ?></h3>
                    <p class="value-desc"><?= $v['desc'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Stats strip -->
<section class="about-stats-strip" aria-label="Store statistics">
    <div class="container-fluid px-4 px-lg-5 about-stats-inner">
        <?php
        $stats = [
            [$totalBooks . '+', 'Titles in Stock'],
            ['3',               'Curated Genres'],
            ['2019',            'Founded'],
            ['14 Days',         'Return Window'],
        ];
        foreach ($stats as $s): ?>
        <div class="about-stat">
            <span class="about-stat-num"><?= $s[0] ?></span>
            <span class="about-stat-label"><?= $s[1] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA -->
<section class="cta-banner" aria-label="Browse books call to action">
    <div class="container-fluid px-4 px-lg-5 cta-inner">
        <div>
            <h2 class="cta-heading">Start exploring our catalogue</h2>
            <p class="cta-sub">Free delivery on orders above $50.</p>
        </div>
        <a href="<?= $baseUrl ?>/products.php" class="btn-cta-gold">
            <i class="bi bi-book" aria-hidden="true"></i> Browse Books
        </a>
    </div>
</section>

</main>

<?php include 'inc/footer.php'; ?>
