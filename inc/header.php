<?php
$pageTitle = $pageTitle ?? "Atheneum"; //dynamic page title, css, js
$extraCSS = $extraCSS ?? [];
$extraJS = $extraJS ?? [];

$username   = $_SESSION['username'] ?? null; //caching session username
$userId     = $_SESSION['userId']   ?? null; //caching session userId for cart
$isLoggedIn = isset($username);

// Detect base URL automatically (works on localhost:8080 and Google Cloud)
$protocol        = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host            = $_SERVER['HTTP_HOST']; // includes port e.g. localhost:8080
$hostWithoutPort = explode(':', $host)[0];
$isLocal         = in_array($hostWithoutPort, ['localhost', '127.0.0.1']);
$basePath        = $isLocal ? '/Atheneum' : '';
$baseUrl         = $protocol . $host . $basePath;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css"
    rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/main.css">
    <?php foreach ($extraCSS as $css): ?>
        <link rel="stylesheet" href="<?= $baseUrl ?>/<?= htmlspecialchars(ltrim($css, '/')) ?>">
    <?php endforeach; ?>
</head>
<body>