<?php
$pageTitle = "Order Details";
$extraCSS = ["assets/css/orders.css"];
$extraJS = [["src" => "assets/js/orders.js", "defer" => true]];

include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';
require_once 'config/shipment_config.php';

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($orderId <= 0) {
    header("Location: orders.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT o.*, s.currentStatus AS shipmentStatus,
           s.order_placed_at, s.order_shipped_at, s.in_transit_at,
           s.out_for_delivery_at, s.delivered_at
    FROM Orders o
    LEFT JOIN OrderShipments s ON o.orderId = s.orderId
    WHERE o.orderId = ? AND o.userId = ?
");
$userId = $_SESSION['userId'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit();
}
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Refund logic to fetch details from the Refund table
$refundInfo = null;
$stmtRef = $conn->prepare("SELECT status, reason, admin_note, created_at FROM Refund WHERE orderId = ?");
$stmtRef->bind_param("i", $orderId);
$stmtRef->execute();
$refundInfo = $stmtRef->get_result()->fetch_assoc();
$stmtRef->close();

$stmtItems = $conn->prepare("
    SELECT oi.*, p.title, p.author, p.cover_image
    FROM OrderItems oi
    JOIN Products p ON oi.productId = p.productId
    WHERE oi.orderId = ?
");
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();

$shipmentStatus = $order['shipmentStatus'] ?? 'order_placed';
$statusIdx = array_search($shipmentStatus, $STATUS_ORDER);

$reviewEligible = [];
if ($shipmentStatus === 'delivered' && $order['delivered_at']) {
    $deliveredAt = new DateTime($order['delivered_at']);
    $now = new DateTime();
    $daysSince = $now->diff($deliveredAt)->days;

    if ($daysSince <= $REVIEW_WINDOW_DAYS) {
        foreach ($items as $item) {
            $stmtRev = $conn->prepare("SELECT reviewId FROM Reviews WHERE userId = ? AND productId = ?");
            $stmtRev->bind_param("ii", $userId, $item['productId']);
            $stmtRev->execute();
            $hasReview = $stmtRev->get_result()->num_rows > 0;
            $stmtRev->close();

            if (!$hasReview) {
                $reviewEligible[$item['productId']] = true;
            }
        }
    }
}

$jsTimings = json_encode($SHIPMENT_TIMINGS);
$jsStatusOrder = json_encode($STATUS_ORDER);
$jsStatusLabels = json_encode($STATUS_LABELS);
?>

<main>
    <div class="order-detail-wrapper">
        <nav aria-label="Breadcrumb">
            <a href="orders.php" class="back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to
                Orders</a>
        </nav>

        <div class="order-detail-header">
            <div>
                <h1>Order #<?= $orderId ?></h1>
                <p class="order-meta">
                    Placed on
                    <time datetime="<?= date('Y-m-d\TH:i', strtotime($order['created_at'])) ?>">
                        <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                    </time>
                </p>
            </div>
            <span class="status-pill <?= str_replace('_', '-', $shipmentStatus) ?>" role="status" id="headerStatusPill">
                <?= htmlspecialchars($STATUS_LABELS[$shipmentStatus] ?? 'Processing') ?>
            </span>
        </div>

        <?php
        // Refund logic to display an alert if a refund has been requested
        if ($refundInfo): ?>
            <div class="alert <?= $refundInfo['status'] === 'approved' ? 'alert-success' : ($refundInfo['status'] === 'rejected' ? 'alert-danger' : 'alert-warning') ?> shadow-sm border-0 mb-4"
                role="alert" style="border-radius: 12px; padding: 1.5rem;">
                <div role="heading" aria-level="2" class="alert-heading fw-bold mb-2" style="font-size: 1.25rem;">Refund
                    Request: <?= ucfirst($refundInfo['status']) ?></div>
                <p class="mb-1"><strong>Reason:</strong> <?= htmlspecialchars($refundInfo['reason']) ?></p>
                <?php if (!empty($refundInfo['admin_note'])): ?>
                    <hr style="opacity: 0.1;">
                    <p class="mb-0"><strong>Message from Atheneum:</strong>
                        <?= htmlspecialchars($refundInfo['admin_note']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
        // Refund logic to hide the tracker if the order is refunded
        if ($order['paymentStatus'] !== 'refunded'): ?>
            <section class="shipment-tracker" id="shipmentTracker" data-order-id="<?= $orderId ?>"
                data-current-status="<?= htmlspecialchars($shipmentStatus) ?>" aria-labelledby="tracker-title">

                <h2 class="tracker-title" id="tracker-title">Shipment Status</h2>

                <div class="sr-only" aria-live="assertive" id="statusAnnouncement"></div>

                <ol class="tracker-steps" aria-label="Shipment progress steps">
                    <?php
                    $icons = [
                        'order_placed' => 'bi-receipt',
                        'order_shipped' => 'bi-box-seam',
                        'in_transit' => 'bi-truck',
                        'out_for_delivery' => 'bi-geo-alt',
                        'delivered' => 'bi-check-circle',
                    ];
                    ?>
                    <?php foreach ($STATUS_ORDER as $idx => $step): ?>
                        <?php
                        $isCompleted = $idx <= $statusIdx;
                        $isCurrent = $idx === $statusIdx;
                        $timestamp = $order[$step . '_at'] ?? null;
                        $stepState = $isCurrent ? 'Current step' : ($isCompleted ? 'Completed' : 'Upcoming');
                        ?>
                        <li class="tracker-step <?= $isCompleted ? 'completed' : '' ?> <?= $isCurrent ? 'current' : '' ?>"
                            data-step="<?= $step ?>" id="step-<?= $step ?>"
                            aria-label="<?= $STATUS_LABELS[$step] ?>: <?= $stepState ?><?= $timestamp ? ', ' . date('d M, h:i A', strtotime($timestamp)) : '' ?>">
                            <div class="step-icon-wrap">
                                <div class="step-icon" aria-hidden="true">
                                    <i class="bi <?= $icons[$step] ?>"></i>
                                </div>
                                <?php if ($idx < count($STATUS_ORDER) - 1): ?>
                                    <div class="step-connector <?= $idx < $statusIdx ? 'completed' : '' ?>"
                                        id="connector-<?= $step ?>" aria-hidden="true"></div>
                                <?php endif; ?>
                            </div>
                            <div class="step-info">
                                <span class="step-label"><?= $STATUS_LABELS[$step] ?></span>
                                <span class="step-time" id="time-<?= $step ?>">
                                    <?php if ($timestamp): ?>
                                        <time datetime="<?= date('Y-m-d\TH:i', strtotime($timestamp)) ?>">
                                            <?= date('d M, h:i A', strtotime($timestamp)) ?>
                                        </time>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endif; ?>

        <section class="order-items-section" aria-labelledby="items-heading">
            <h2 class="section-heading" id="items-heading">Items in this Order</h2>
            <div role="list" aria-label="Ordered items">
                <?php foreach ($items as $item): ?>
                    <div class="order-item-row" role="listitem"
                        aria-label="<?= htmlspecialchars($item['title']) ?>, quantity <?= $item['quantity'] ?>">
                        <?php if (!empty($item['cover_image'])): ?>
                            <img src="<?= htmlspecialchars(asset_url($item['cover_image'])) ?>"
                                alt="Cover of <?= htmlspecialchars($item['title']) ?>" class="order-item-cover">
                        <?php else: ?>
                            <div class="order-item-cover-placeholder" role="img" aria-label="No cover image">
                                <i class="bi bi-book" aria-hidden="true"></i>
                            </div>
                        <?php endif; ?>

                        <div class="order-item-info">
                            <a href="book.php?id=<?= $item['productId'] ?>" class="order-item-title">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                            <p class="order-item-author"><?= htmlspecialchars($item['author']) ?></p>
                            <p class="order-item-qty">Qty: <?= $item['quantity'] ?></p>
                        </div>

                        <div class="order-item-right">
                            <span class="order-item-price">
                                <span class="visually-hidden">Item total: $<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></span>
                                <span aria-hidden="true">$<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></span>
                            </span>
                            <?php if (isset($reviewEligible[$item['productId']])): ?>
                                <a href="book.php?id=<?= $item['productId'] ?>#reviewFormCard" class="write-review-link"
                                    aria-label="Write a review for <?= htmlspecialchars($item['title']) ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Write a Review
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="order-summary-footer" aria-label="Order summary">
            <div class="summary-line">
                <span>Order Total</span>
                <span class="summary-total">
                    <span class="visually-hidden">Order total: $<?= number_format($order['totalPrice'], 2) ?></span>
                    <span aria-hidden="true">$<?= number_format($order['totalPrice'], 2) ?></span>
                </span>
            </div>
            <div class="summary-line">
                <span>Payment</span>
                <span class="status-pill <?= $order['paymentStatus'] ?>"><?= ucfirst($order['paymentStatus']) ?></span>
            </div>
        </section>
    </div>
</main>

<script>
    window.SHIPMENT_CONFIG = {
        orderId: <?= $orderId ?>,
        currentStatus: '<?= $shipmentStatus ?>',
        timings: <?= $jsTimings ?>,
        statusOrder: <?= $jsStatusOrder ?>,
        statusLabels: <?= $jsStatusLabels ?>,
    };
</script>

<?php include 'inc/footer.php'; ?>