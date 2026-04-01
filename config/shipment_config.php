<?php
//demo timings for shipment status, yall can change the duration if needed
$SHIPMENT_TIMINGS = [
    'order_placed'      => 10,
    'order_shipped'     => 20,
    'in_transit'        => 60,  
    'out_for_delivery'  => 30,
    'delivered'         => null,
];

$REVIEW_WINDOW_DAYS = 14;

$STATUS_ORDER = ['order_placed', 'order_shipped', 'in_transit', 'out_for_delivery', 'delivered'];

$STATUS_LABELS = [
    'order_placed'     => 'Order Placed',
    'order_shipped'    => 'Order Shipped',
    'in_transit'       => 'In Transit',
    'out_for_delivery' => 'Out for Delivery',
    'delivered'        => 'Delivered',
];
?>
