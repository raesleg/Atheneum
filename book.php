<?php
$pageTitle = "Book Details";
$extraCSS  = ["assets/css/book.css"];
$extraJS   = [
    ["src" => "assets/js/review.js", "defer" => true],
    ["src" => "assets/js/book.js","defer" => true],
];

include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';
require_once 'config/shipment_config.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM Products WHERE productId = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: index.php");
    exit();
}

$stmtStats = $conn->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        COALESCE(AVG(rating), 0) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star1
    FROM Reviews WHERE productId = ?
");
$stmtStats->bind_param("i", $productId);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();
$stmtStats->close();

$totalReviews = (int)$stats['total_reviews'];
$avgRating    = round((float)$stats['avg_rating'], 1);
$starCounts   = [
    5 => (int)$stats['star5'],
    4 => (int)$stats['star4'],
    3 => (int)$stats['star3'],
    2 => (int)$stats['star2'],
    1 => (int)$stats['star1'],
];

$stmtReviews = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, u.username, u.fname, u.lname
    FROM Reviews r
    JOIN Users u ON r.userId = u.userId
    WHERE r.productId = ?
    ORDER BY r.created_at DESC
");
$stmtReviews->bind_param("i", $productId);
$stmtReviews->execute();
$reviews = $stmtReviews->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtReviews->close();

// Filter related books (same genre, exclude current)
$stmtRelated = $conn->prepare("
    SELECT productId, title, author, price, cover_image
    FROM Products
    WHERE genre = ? AND productId != ?
    ORDER BY RAND()
    LIMIT 6
");
$stmtRelated->bind_param("si", $book['genre'], $productId);
$stmtRelated->execute();
$relatedBooks = $stmtRelated->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtRelated->close();

$canReview     = false;
$alreadyReviewed = false;
$reviewEligibleOrderId = null;

if ($isLoggedIn) {
    $stmtCheck = $conn->prepare("SELECT reviewId FROM Reviews WHERE userId = ? AND productId = ?");
    $stmtCheck->bind_param("ii", $userId, $productId);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        $alreadyReviewed = true;
    }
    $stmtCheck->close();

    if (!$alreadyReviewed) {
        $stmtEligible = $conn->prepare("
            SELECT o.orderId
            FROM Orders o
            JOIN OrderItems oi ON o.orderId = oi.orderId
            JOIN OrderShipments s ON o.orderId = s.orderId
            WHERE o.userId = ?
              AND oi.productId = ?
              AND o.paymentStatus = 'paid'
              AND s.currentStatus = 'delivered'
              AND s.delivered_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            LIMIT 1
        ");
        $stmtEligible->bind_param("iii", $userId, $productId, $REVIEW_WINDOW_DAYS);
        $stmtEligible->execute();
        $eligibleRow = $stmtEligible->get_result()->fetch_assoc();
        if ($eligibleRow) {
            $canReview = true;
            $reviewEligibleOrderId = $eligibleRow['orderId'];
        }
        $stmtEligible->close();
    }
}
?>

<main>
    <article class="book-detail-wrapper" aria-label="Book details for <?= htmlspecialchars($book['title']) ?>">
        <div class="book-detail-grid">
            <div class="book-cover-col">
                <?php if (!empty($book['cover_image'])): ?>
                    <img src="<?= htmlspecialchars($book['cover_image']) ?>" 
                         alt="Cover of <?= htmlspecialchars($book['title']) ?> by <?= htmlspecialchars($book['author']) ?>" 
                         class="book-cover-large"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="cover-placeholder-lg" style="display:none" role="img" aria-label="No cover image available">
                        <i class="bi bi-book" aria-hidden="true"></i>
                    </div>
                <?php else: ?>
                    <div class="cover-placeholder-lg" role="img" aria-label="No cover image available">
                        <i class="bi bi-book" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="book-info-col">
                <p class="book-genre"><?= htmlspecialchars($book['genre']) ?></p>
                <h1 class="book-title-lg"><?= htmlspecialchars($book['title']) ?></h1>
                <p class="book-author-lg">by <?= htmlspecialchars($book['author']) ?></p>

                <div class="inline-rating" role="img" aria-label="Average rating: <?= $avgRating ?> out of 5 stars, based on <?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?>">
                    <div class="stars-display" aria-hidden="true">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= floor($avgRating)): ?>
                                <i class="bi bi-star-fill"></i>
                            <?php elseif ($i - $avgRating < 1 && $i - $avgRating > 0): ?>
                                <i class="bi bi-star-half"></i>
                            <?php else: ?>
                                <i class="bi bi-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-number" aria-hidden="true"><?= $avgRating ?></span>
                    <span class="rating-count" aria-hidden="true">(<?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?>)</span>
                </div>

                <p class="book-price-lg" aria-label="Price: $<?= number_format($book['price'], 2) ?>">$<?= number_format($book['price'], 2) ?></p>

                <?php if (!empty($book['description'])): ?>
                    <p class="book-desc"><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                <?php endif; ?>

                <p class="book-stock <?= $book['quantity'] > 0 ? 'in-stock' : 'out-of-stock' ?>" role="status">
                    <?= $book['quantity'] > 0 ? 'In Stock (' . $book['quantity'] . ' available)' : 'Out of Stock' ?>
                </p>

                <?php if ($isLoggedIn && $book['quantity'] > 0): ?>
                    <button class="add-to-cart-btn" id="addToCartBtn"
                            data-product-id="<?= $productId ?>"
                            data-book-title="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>"
                            data-book-cover="<?= htmlspecialchars($book['cover_image'] ?? '', ENT_QUOTES) ?>"
                            aria-label="Add <?= htmlspecialchars($book['title']) ?> to cart">
                        <i class="bi bi-bag-plus" aria-hidden="true"></i> Add to Cart
                    </button>
                <?php elseif (!$isLoggedIn): ?>
                    <a href="login.php" class="add-to-cart-btn" aria-label="Log in to purchase this book">Login to Purchase</a>
                <?php endif; ?>
            </div>
        </div>
    </article>

    <section class="reviews-section" aria-labelledby="reviews-heading">
        <h2 class="reviews-heading" id="reviews-heading">Customer Reviews</h2>

        <div class="reviews-layout">
            <div class="rating-summary" aria-label="Rating summary" role="group">
                <div class="avg-rating-big" aria-hidden="true">
                    <span class="big-number"><?= $avgRating ?></span>
                    <span class="out-of">out of 5</span>
                </div>
                <div class="stars-display avg-stars" aria-hidden="true">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= floor($avgRating)): ?>
                            <i class="bi bi-star-fill"></i>
                        <?php elseif ($i - $avgRating < 1 && $i - $avgRating > 0): ?>
                            <i class="bi bi-star-half"></i>
                        <?php else: ?>
                            <i class="bi bi-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <p class="total-ratings"><?= $totalReviews ?> global rating<?= $totalReviews !== 1 ? 's' : '' ?></p>

                <div class="star-bars" role="list" aria-label="Rating breakdown by stars">
                    <?php for ($s = 5; $s >= 1; $s--): ?>
                        <?php $pct = $totalReviews > 0 ? round(($starCounts[$s] / $totalReviews) * 100) : 0; ?>
                        <div class="star-bar-row" role="listitem" aria-label="<?= $s ?> star: <?= $pct ?> percent, <?= $starCounts[$s] ?> review<?= $starCounts[$s] !== 1 ? 's' : '' ?>">
                            <span class="star-label" aria-hidden="true"><?= $s ?> star</span>
                            <div class="bar-track" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= $s ?> star ratings">
                                <div class="bar-fill" style="width: <?= $pct ?>%"></div>
                            </div>
                            <span class="bar-pct" aria-hidden="true"><?= $pct ?>%</span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="reviews-list-col">

                <?php if ($canReview): ?>
                <div class="review-form-card" id="reviewFormCard">
                    <h3 id="review-form-heading">Write a Review</h3>
                    <form id="reviewForm" aria-labelledby="review-form-heading" novalidate>
                        <input type="hidden" name="productId" value="<?= $productId ?>">
                        <input type="hidden" name="orderId" value="<?= $reviewEligibleOrderId ?>">

                        <fieldset class="star-fieldset">
                            <legend class="form-label-sm">Your Rating <span class="req" aria-hidden="true">*</span><span class="sr-only"> (required)</span></legend>
                            <div class="star-picker" id="starPicker" role="radiogroup" aria-required="true">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star-pick" 
                                          data-value="<?= $i ?>" 
                                          role="radio" 
                                          aria-checked="false" 
                                          aria-label="<?= $i ?> star<?= $i !== 1 ? 's' : '' ?>" 
                                          tabindex="<?= $i === 1 ? '0' : '-1' ?>">
                                        <i class="bi bi-star" aria-hidden="true"></i>
                                    </span>
                                <?php endfor; ?>
                            </div>
                        </fieldset>
                        <input type="hidden" name="rating" id="ratingInput" value="0">

                        <div class="form-group">
                            <label class="form-label-sm" for="reviewComment">Comment <span class="optional">(optional, max 200 characters)</span></label>
                            <textarea name="comment" id="reviewComment" maxlength="200" rows="3"
                                      placeholder="Share your thoughts in 1-2 sentences..."
                                      aria-describedby="charCountDesc"></textarea>
                            <div class="char-counter" id="charCountDesc" aria-live="polite">
                                <span id="charCount">0</span>/200 characters used
                            </div>
                        </div>

                        <div id="reviewError" class="review-error" role="alert" aria-live="assertive"></div>
                        <button type="submit" class="submit-review-btn">Submit Review</button>
                    </form>
                </div>
                <?php elseif ($alreadyReviewed): ?>
                    <div class="review-notice" role="status">
                        <i class="bi bi-check-circle" aria-hidden="true"></i> You have already reviewed this book.
                    </div>
                <?php elseif ($isLoggedIn): ?>
                    <div class="review-notice" role="status">
                        <i class="bi bi-info-circle" aria-hidden="true"></i> You can leave a review after purchasing this book and receiving delivery.
                    </div>
                <?php else: ?>
                    <div class="review-notice" role="status">
                        <i class="bi bi-person" aria-hidden="true"></i> <a href="login.php">Log in</a> to leave a review.
                    </div>
                <?php endif; ?>

                <div id="reviewsList" role="list" aria-label="Customer reviews">
                    <?php if (empty($reviews)): ?>
                        <div class="no-reviews" role="listitem">
                            <p>No reviews yet. Be the first to share your thoughts!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                        <?php $reviewerName = htmlspecialchars($rev['fname'] ? $rev['fname'] . ' ' . $rev['lname'] : $rev['username']); ?>
                        <article class="review-card" aria-label="Review by <?= $reviewerName ?>, <?= $rev['rating'] ?> out of 5 stars">
                            <div class="review-header">
                                <i class="bi bi-person-circle review-avatar" aria-hidden="true"></i>
                                <div>
                                    <span class="reviewer-name"><?= $reviewerName ?></span>
                                    <div class="stars-display stars-sm" aria-label="<?= $rev['rating'] ?> out of 5 stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= $rev['rating'] ? '-fill' : '' ?>" aria-hidden="true"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <time class="review-date" datetime="<?= date('Y-m-d', strtotime($rev['created_at'])) ?>">
                                    <?= date('d M Y', strtotime($rev['created_at'])) ?>
                                </time>
                            </div>
                            <?php if (!empty($rev['comment'])): ?>
                                <p class="review-comment"><?= htmlspecialchars($rev['comment']) ?></p>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($relatedBooks)): ?>
    <section class="related-section" aria-labelledby="related-heading">
        <div class="container-fluid px-4 px-lg-5">
            <p class="section-eyebrow">More Like This</p>
            <h2 class="section-heading" id="related-heading">You Might Enjoy</h2>
            <hr class="section-rule">
            <div class="related-grid">
                <?php foreach ($relatedBooks as $rb): ?>
                <a href="<?= $baseUrl ?>/book.php?id=<?= $rb['productId'] ?>" class="related-card"
                   aria-label="<?= htmlspecialchars($rb['title']) ?> by <?= htmlspecialchars($rb['author']) ?>">
                    <div class="related-cover" aria-hidden="true">
                        <?php if (!empty($rb['cover_image'])): ?>
                        <img src="<?= htmlspecialchars($rb['cover_image']) ?>"
                             alt=""
                             loading="lazy"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="related-placeholder" style="display:none"><i class="bi bi-book"></i></div>
                        <?php else: ?>
                        <div class="related-placeholder"><i class="bi bi-book"></i></div>
                        <?php endif; ?>
                    </div>
                    <p class="related-title"><?= htmlspecialchars($rb['title']) ?></p>
                    <p class="related-author">by <?= htmlspecialchars($rb['author']) ?></p>
                    <p class="related-price">$<?= number_format($rb['price'], 2) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include 'inc/footer.php'; ?>
