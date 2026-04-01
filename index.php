<?php
$pageTitle = "Home";
$extraCSS  = ["assets/css/index.css"];
include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';

// book slideshow (random order)
$stmtSlide = $conn->query("SELECT productId, title, author, genre, price, cover_image FROM Products ORDER BY RAND() LIMIT 30");
$slideBooks = $stmtSlide->fetch_all(MYSQLI_ASSOC);

// Fetch newest books per genre (4 each) for featured sections
$genres = ['Fiction & Literature', 'Non-Fiction & Self Help', 'Science & Technology'];
$featuredByGenre = [];
foreach ($genres as $g) {
    $stmt = $conn->prepare("SELECT productId, title, author, genre, price, cover_image FROM Products WHERE genre = ? ORDER BY created_at DESC LIMIT 4");
    $stmt->bind_param("s", $g);
    $stmt->execute();
    $featuredByGenre[$g] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Total book count for hero
$totalBooks = $conn->query("SELECT COUNT(*) AS c FROM Products")->fetch_assoc()['c'];
?>

<main>
<!-- HERO — slideshow + CTA -->
<section class="hero-section" aria-label="Featured books slideshow">
    <div class="hero-inner container-fluid px-4 px-lg-5">

        <!-- Left: text -->
        <div class="hero-text">
            <p class="hero-eyebrow">Singapore's Premier Bookstore</p>
            <h1 class="hero-heading">
                Find your next<br>
                <em>favourite story.</em>
            </h1>
            <p class="hero-sub">
                Browse <?= $totalBooks ?>+ handpicked titles across Fiction, Non-Fiction, and Science &amp; Technology.
                Fast delivery. Free shipping over $50.
            </p>
            <div class="hero-cta-group">
                <a href="<?= $baseUrl ?>/products.php" class="btn-hero-primary">
                    <i class="bi bi-book" aria-hidden="true"></i> Browse Books
                </a>
                <a href="<?= $baseUrl ?>/products.php?genre=Fiction+%26+Literature" class="btn-hero-outline">
                    Explore Fiction
                </a>
            </div>
            <!-- Stats -->
            <div class="hero-stats" role="group" aria-label="Store highlights">
                <div class="hero-stat">
                    <span class="hero-stat-num"><?= $totalBooks ?>+</span>
                    <span class="hero-stat-label">Titles</span>
                </div>
                <div class="hero-stat-divider" aria-hidden="true"></div>
                <div class="hero-stat">
                    <span class="hero-stat-num">3</span>
                    <span class="hero-stat-label">Genres</span>
                </div>
                <div class="hero-stat-divider" aria-hidden="true"></div>
                <div class="hero-stat">
                    <span class="hero-stat-num">$3.99</span>
                    <span class="hero-stat-label">Delivery</span>
                </div>
            </div>
        </div>

        <!-- Right: carousel -->
        <div class="hero-carousel-wrap">
            <div class="hero-carousel" id="heroCarousel" role="region" aria-label="Book slideshow" aria-live="polite">
                <?php foreach ($slideBooks as $i => $b): ?>
                <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
                     role="group"
                     aria-roledescription="slide"
                     aria-label="<?= $i + 1 ?> of <?= count($slideBooks) ?>: <?= htmlspecialchars($b['title']) ?>"
                     aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
                    <a href="<?= $baseUrl ?>/book.php?id=<?= $b['productId'] ?>"
                       tabindex="<?= $i === 0 ? '0' : '-1' ?>">
                        <div class="hero-book-cover">
                            <?php if ($b['cover_image']): ?>
                            <img src="<?= htmlspecialchars(asset_url($b['cover_image'])) ?>"
                                 alt="Cover of <?= htmlspecialchars($b['title']) ?>"
                                 loading="<?= $i < 2 ? 'eager' : 'lazy' ?>"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="hero-book-placeholder" style="display:none" aria-hidden="true">
                                <i class="bi bi-book"></i>
                            </div>
                            <?php else: ?>
                            <div class="hero-book-placeholder" aria-hidden="true">
                                <i class="bi bi-book"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="hero-book-info">
                            <p class="hero-book-genre"><?= htmlspecialchars($b['genre']) ?></p>
                            <h2 class="hero-book-title"><?= htmlspecialchars($b['title']) ?></h2>
                            <p class="hero-book-author">by <?= htmlspecialchars($b['author']) ?></p>
                            <p class="hero-book-price">$<?= number_format($b['price'], 2) ?></p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Controls -->
            <div class="carousel-controls">
                <button class="carousel-btn" id="heroPrev" aria-label="Previous book">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </button>
                <div class="carousel-dots" role="group" aria-label="Slideshow navigation">
                    <?php foreach ($slideBooks as $i => $b): ?>
                    <button class="carousel-dot <?= $i === 0 ? 'active' : '' ?>"
                            data-index="<?= $i ?>"
                            aria-label="Go to slide <?= $i + 1 ?>">
                    </button>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-btn" id="heroNext" aria-label="Next book">
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</section>

<!--GENRE PILLS-->
<section class="genre-strip container-fluid px-4 px-lg-5" aria-label="Browse by genre">
    <?php
    $genreMeta = [
        'Fiction & Literature'    => ['icon' => 'book-half',   'color' => '#edf2fb'],
        'Non-Fiction & Self Help' => ['icon' => 'lightbulb',   'color' => '#fdf8ec'],
        'Science & Technology'    => ['icon' => 'cpu',         'color' => '#edf7f2'],
    ];
    foreach ($genreMeta as $gName => $gData):
        $count = $conn->prepare("SELECT COUNT(*) AS c FROM Products WHERE genre=?");
        $count->bind_param("s", $gName);
        $count->execute();
        $gCount = $count->get_result()->fetch_assoc()['c'];
        $count->close();
    ?>
    <a href="<?= $baseUrl ?>/products.php?genre=<?= urlencode($gName) ?>"
       class="genre-pill" aria-label="Browse <?= htmlspecialchars($gName) ?>, <?= $gCount ?> books">
        <span class="genre-pill-icon" style="background:<?= $gData['color'] ?>" aria-hidden="true">
            <i class="bi bi-<?= $gData['icon'] ?>"></i>
        </span>
        <div class="genre-pill-text">
            <span class="genre-pill-name"><?= htmlspecialchars($gName) ?></span>
            <span class="genre-pill-count"><?= $gCount ?> books</span>
        </div>
        <i class="bi bi-arrow-right genre-pill-arrow" aria-hidden="true"></i>
    </a>
    <?php endforeach; ?>
</section>

<!--FEATURED BY GENRE-->
<?php
$sectionTitles = [
    'Fiction & Literature'    => 'Stories Worth Telling',
    'Non-Fiction & Self Help' => 'Expand Your Mind',
    'Science & Technology'    => 'Curious About the World',
];
foreach ($featuredByGenre as $gName => $books):
    if (empty($books)) continue;
?>
<section class="featured-section" aria-labelledby="section-<?= preg_replace('/\W+/', '-', strtolower($gName)) ?>">
    <div class="container-fluid px-4 px-lg-5">
        <div class="featured-header">
            <div>
                <p class="section-eyebrow"><?= htmlspecialchars($gName) ?></p>
                <h2 class="section-heading" id="section-<?= preg_replace('/\W+/', '-', strtolower($gName)) ?>">
                    <?= htmlspecialchars($sectionTitles[$gName] ?? $gName) ?>
                </h2>
            </div>
            <a href="<?= $baseUrl ?>/products.php?genre=<?= urlencode($gName) ?>"
               class="btn-view-all" aria-label="View all <?= htmlspecialchars($gName) ?> books">
                View All <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </div>
        <hr class="section-rule" aria-hidden="true">

        <div class="books-grid">
            <?php foreach ($books as $b): ?>
            <article class="book-card">
                <a href="<?= $baseUrl ?>/book.php?id=<?= $b['productId'] ?>" class="book-card-cover-link"
                   aria-label="<?= htmlspecialchars($b['title']) ?> by <?= htmlspecialchars($b['author']) ?>">
                    <div class="book-card-cover" aria-hidden="true">
                        <?php if ($b['cover_image']): ?>
                        <img src="<?= htmlspecialchars(asset_url($b['cover_image'])) ?>"
                             alt=""
                             loading="lazy"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="book-card-placeholder" style="display:none"><i class="bi bi-book"></i></div>
                        <?php else: ?>
                        <div class="book-card-placeholder"><i class="bi bi-book"></i></div>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="book-card-body">
                    <p class="book-card-genre"><?= htmlspecialchars($b['genre']) ?></p>
                    <a href="<?= $baseUrl ?>/book.php?id=<?= $b['productId'] ?>">
                        <h3 class="book-card-title"><?= htmlspecialchars($b['title']) ?></h3>
                    </a>
                    <p class="book-card-author">by <?= htmlspecialchars($b['author']) ?></p>
                    <p class="book-card-price">
                        $<?= number_format($b['price'], 2) ?>
                    </p>
                    <a href="<?= $baseUrl ?>/book.php?id=<?= $b['productId'] ?>"
                       class="btn-view-book" aria-label="View <?= htmlspecialchars($b['title']) ?>">
                        View Book
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endforeach; ?>

<!--WHY ATHENEUM-->
<section class="why-section" aria-labelledby="why-heading">
    <div class="container-fluid px-4 px-lg-5">
        <div class="text-center mb-5">
            <p class="section-eyebrow">Why Atheneum</p>
            <h2 class="section-heading" id="why-heading">Reading made simple</h2>
        </div>
        <div class="why-grid" role="list">
            <?php
            $whys = [
                ['icon'=>'truck',           'title'=>'Fast Delivery',    'desc'=>'Get your books in 3–5 business days. Free delivery on all orders above $50.'],
                ['icon'=>'shield-check',    'title'=>'Secure Checkout',  'desc'=>'Pay safely with Stripe. Your payment details are encrypted and never stored.'],
                ['icon'=>'collection',   'title'=>'Curated Titles',   'desc'=>'Every book is handpicked across Fiction, Non-Fiction, and Science & Technology.'],
                ['icon'=>'arrow-return-left','title'=>'Easy Returns',    'desc'=>'Not satisfied? Return any book within 14 days of delivery, no questions asked.'],
            ];
            foreach ($whys as $w): ?>
            <div class="why-card" role="listitem">
                <div class="why-icon" aria-hidden="true">
                    <i class="bi bi-<?= $w['icon'] ?>"></i>
                </div>
                <h3 class="why-title"><?= $w['title'] ?></h3>
                <p class="why-desc"><?= $w['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!--CTA BANNER-->
<section class="cta-banner" aria-label="Call to action">
    <div class="container-fluid px-4 px-lg-5 cta-inner">
        <div>
            <h2 class="cta-heading">Ready to find your next read?</h2>
            <p class="cta-sub">Browse our full collection of <?= $totalBooks ?>+ titles today.</p>
        </div>
        <a href="<?= $baseUrl ?>/products.php" class="btn-cta-gold">
            <i class="bi bi-book" aria-hidden="true"></i> Shop All Books
        </a>
    </div>
</section>
</main>

<!-- Slideshow JS -->
<script>
(function () {
    const slides = Array.from(document.querySelectorAll('.hero-slide'));
    const dots   = Array.from(document.querySelectorAll('.carousel-dot'));
    if (!slides.length) return;

    let current = 0;
    let timer;

    function goTo(idx) {
        slides[current].classList.remove('active');
        slides[current].setAttribute('aria-hidden', 'true');
        slides[current].querySelector('a').tabIndex = -1;
        dots[current].classList.remove('active');
        dots[current].setAttribute('aria-selected', 'false');

        current = ((idx % slides.length) + slides.length) % slides.length;

        slides[current].classList.add('active');
        slides[current].setAttribute('aria-hidden', 'false');
        slides[current].querySelector('a').tabIndex = 0;
        dots[current].classList.add('active');
        dots[current].setAttribute('aria-selected', 'true');
    }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(function () { goTo(current + 1); }, 3500);
    }

    document.getElementById('heroNext').addEventListener('click', function () { goTo(current + 1); startTimer(); });
    document.getElementById('heroPrev').addEventListener('click', function () { goTo(current - 1); startTimer(); });
    dots.forEach(function (d) {
        d.addEventListener('click', function () { goTo(parseInt(this.dataset.index)); startTimer(); });
    });

    const carousel = document.getElementById('heroCarousel');
    carousel.addEventListener('mouseenter', function () { clearInterval(timer); });
    carousel.addEventListener('mouseleave', startTimer);

    startTimer();
})();
</script>

<?php include 'inc/footer.php'; ?>
