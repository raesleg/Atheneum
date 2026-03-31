<?php
$pageTitle = "Books";
$extraCSS  = ["assets/css/products.css"];
include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';

// ── Inputs ────────────────────────────────────────────────────────
$search  = trim($_GET['search'] ?? '');
$genre   = trim($_GET['genre']  ?? '');
$sort    = $_GET['sort'] ?? 'newest';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$allowedSorts = ['newest', 'oldest', 'price_asc', 'price_desc', 'title_asc'];
if (!in_array($sort, $allowedSorts)) $sort = 'newest';

$sortMap = [
    'newest'     => 'p.created_at DESC',
    'oldest'     => 'p.created_at ASC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'title_asc'  => 'p.title ASC',
];
$orderBy = $sortMap[$sort];

// ── Build WHERE clause ────────────────────────────────────────────
$conditions = [];
$params     = [];
$types      = '';

if ($search !== '') {
    $conditions[] = '(p.title LIKE ? OR p.author LIKE ?)';
    $params[]     = "%$search%";
    $params[]     = "%$search%";
    $types       .= 'ss';
}
if ($genre !== '') {
    $conditions[] = 'p.genre = ?';
    $params[]     = $genre;
    $types       .= 's';
}

$whereSQL = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ── Count total ───────────────────────────────────────────────────
$countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM Products p $whereSQL");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalBooks = (int)$countStmt->get_result()->fetch_assoc()['c'];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalBooks / $perPage));
$page = min($page, $totalPages);

// ── Fetch books ───────────────────────────────────────────────────
$bookStmt = $conn->prepare("SELECT p.* FROM Products p $whereSQL ORDER BY $orderBy LIMIT ? OFFSET ?");
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$bookStmt->bind_param($allTypes, ...$allParams);
$bookStmt->execute();
$books = $bookStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$bookStmt->close();

// ── Genre list + counts ───────────────────────────────────────────
$genreList   = ['Fiction & Literature', 'Non-Fiction & Self Help', 'Science & Technology'];
$genreCounts = [];
foreach ($genreList as $g) {
    $gStmt = $conn->prepare("SELECT COUNT(*) AS c FROM Products WHERE genre=?");
    $gStmt->bind_param("s", $g);
    $gStmt->execute();
    $genreCounts[$g] = (int)$gStmt->get_result()->fetch_assoc()['c'];
    $gStmt->close();
}
$totalAllBooks = array_sum($genreCounts);
?>

<!-- Page header -->
<header class="products-page-header">
    <div class="container-fluid px-4 px-lg-5 products-header-inner">
        <div>
            <p class="section-eyebrow">Catalogue</p>
            <h1 class="section-heading">
                <?php if ($genre !== ''): ?>
                    <?= htmlspecialchars($genre) ?>
                <?php elseif ($search !== ''): ?>
                    Results for &ldquo;<?= htmlspecialchars($search) ?>&rdquo;
                <?php else: ?>
                    All Books
                <?php endif; ?>
            </h1>
        </div>
        <p class="products-count" aria-live="polite">
            <?= number_format($totalBooks) ?> title<?= $totalBooks !== 1 ? 's' : '' ?>
            <?php if ($page > 1): ?>&mdash; Page <?= $page ?> of <?= $totalPages ?><?php endif; ?>
        </p>
    </div>
</header>

<div class="container-fluid px-4 px-lg-5 products-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="products-sidebar" aria-label="Filter and search books">

        <!-- Search -->
        <div class="sidebar-block">
            <p class="sidebar-block-title">Search</p>
            <form method="GET" action="<?= $baseUrl ?>/products.php" role="search">
                <?php if ($genre): ?>
                <input type="hidden" name="genre" value="<?= htmlspecialchars($genre) ?>">
                <?php endif; ?>
                <input type="hidden" name="sort"  value="<?= htmlspecialchars($sort) ?>">
                <div class="sidebar-search-wrap">
                    <label for="searchInput" class="visually-hidden">Search by title or author</label>
                    <input type="search" id="searchInput" name="search"
                           class="sidebar-search-input"
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Title or author…"
                           autocomplete="off">
                    <button type="submit" class="sidebar-search-btn" aria-label="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Genre filter -->
        <div class="sidebar-block">
            <p class="sidebar-block-title">Genre</p>
            <ul class="sidebar-genre-list" role="list">
                <li>
                    <a href="<?= $baseUrl ?>/products.php?search=<?= urlencode($search) ?>&sort=<?= $sort ?>"
                       class="sidebar-genre-link <?= $genre === '' ? 'active' : '' ?>"
                       <?= $genre === '' ? 'aria-current="page"' : '' ?>>
                        All Books
                        <span class="sidebar-count" aria-label="<?= $totalAllBooks ?> books"><?= $totalAllBooks ?></span>
                    </a>
                </li>
                <?php foreach ($genreList as $g): ?>
                <li>
                    <a href="<?= $baseUrl ?>/products.php?genre=<?= urlencode($g) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>"
                       class="sidebar-genre-link <?= $genre === $g ? 'active' : '' ?>"
                       <?= $genre === $g ? 'aria-current="page"' : '' ?>>
                        <?= htmlspecialchars($g) ?>
                        <span class="sidebar-count" aria-label="<?= $genreCounts[$g] ?> books"><?= $genreCounts[$g] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Sort -->
        <div class="sidebar-block">
            <p class="sidebar-block-title">Sort By</p>
            <form method="GET" action="<?= $baseUrl ?>/products.php" id="sortForm">
                <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                <?php if ($genre):  ?><input type="hidden" name="genre"  value="<?= htmlspecialchars($genre) ?>"><?php endif; ?>
                <input type="hidden" name="page" value="1">
                <label for="sortSelect" class="visually-hidden">Sort books by</label>
                <select id="sortSelect" name="sort" class="sidebar-select"
                        onchange="document.getElementById('sortForm').submit()"
                        aria-label="Sort books">
                    <option value="newest"     <?= $sort==='newest'    ? 'selected':''?>>Newest First</option>
                    <option value="title_asc"  <?= $sort==='title_asc' ? 'selected':''?>>Title A–Z</option>
                    <option value="price_asc"  <?= $sort==='price_asc' ? 'selected':''?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort==='price_desc'? 'selected':''?>>Price: High to Low</option>
                </select>
            </form>
        </div>

        <!-- Active filters -->
        <?php if ($search !== '' || $genre !== ''): ?>
        <div class="sidebar-block">
            <p class="sidebar-block-title">Active Filters</p>
            <?php if ($search !== ''): ?>
            <div class="active-filter-pill">
                <span>Search: <?= htmlspecialchars($search) ?></span>
                <a href="<?= $baseUrl ?>/products.php?genre=<?= urlencode($genre) ?>&sort=<?= $sort ?>"
                   aria-label="Remove search filter">&times;</a>
            </div>
            <?php endif; ?>
            <?php if ($genre !== ''): ?>
            <div class="active-filter-pill">
                <span><?= htmlspecialchars($genre) ?></span>
                <a href="<?= $baseUrl ?>/products.php?search=<?= urlencode($search) ?>&sort=<?= $sort ?>"
                   aria-label="Remove genre filter">&times;</a>
            </div>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>/products.php" class="clear-all-link">Clear all filters</a>
        </div>
        <?php endif; ?>
    </aside>

    <!-- ── BOOK GRID ── -->
    <main id="main-content" aria-label="Books listing">
        <?php if (empty($books)): ?>
        <div class="no-results" role="status">
            <i class="bi bi-search" aria-hidden="true"></i>
            <h2>No books found</h2>
            <p>Try a different search term or browse all titles.</p>
            <a href="<?= $baseUrl ?>/products.php" class="btn-view-book">Browse All Books</a>
        </div>

        <?php else: ?>
        <div class="books-grid">
            <?php foreach ($books as $b): ?>
            <article class="book-card">
                <a href="<?= $baseUrl ?>/book.php?id=<?= $b['productId'] ?>" class="book-card-cover-link"
                   aria-label="<?= htmlspecialchars($b['title']) ?> by <?= htmlspecialchars($b['author']) ?>">
                    <div class="book-card-cover" aria-hidden="true">
                        <?php if ($b['cover_image']): ?>
                        <img src="<?= htmlspecialchars($b['cover_image']) ?>"
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
                        <h2 class="book-card-title"><?= htmlspecialchars($b['title']) ?></h2>
                    </a>
                    <p class="book-card-author">by <?= htmlspecialchars($b['author']) ?></p>

                    <?php if (!empty($b['description'])): ?>
                    <p class="book-card-desc">
                        <?= htmlspecialchars(mb_substr($b['description'], 0, 100)) ?>…
                    </p>
                    <?php endif; ?>

                    <div class="book-card-footer">
                        <span class="book-card-price" aria-label="Price: $<?= number_format($b['price'], 2) ?>">
                            $<?= number_format($b['price'], 2) ?>
                        </span>
                        <?php if ($b['quantity'] <= 0): ?>
                            <span class="stock-badge out" role="status">Out of Stock</span>
                        <?php elseif ($b['quantity'] <= 5): ?>
                            <span class="stock-badge low" role="status">Only <?= $b['quantity'] ?> left</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= $baseUrl ?>/book.php?id=<?= $b['productId'] ?>"
                       class="btn-view-book w-100 text-center mt-2"
                       aria-label="View <?= htmlspecialchars($b['title']) ?>">
                        View Book
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="pagination-wrap" aria-label="Books pagination">
            <?php if ($page > 1): ?>
            <a href="products.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
               class="page-btn" aria-label="Previous page">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="products.php?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"
               aria-current="<?= $p === $page ? 'page' : 'false' ?>"
               aria-label="Page <?= $p ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="products.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
               class="page-btn" aria-label="Next page">
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                searchInput.closest('form').submit();
            }, 400);
        });
    }
</script>

<?php include 'inc/footer.php'; ?>
