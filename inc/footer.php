<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<?php if (!empty($extraJS)): ?>
    <?php foreach ($extraJS as $script): ?>
        <?php
        $src = $script['src'];
        // Only prefix relative paths; leave CDN URLs (http/https) untouched
        if (!str_starts_with($src, 'http://') && !str_starts_with($src, 'https://')) {
            $src = $baseUrl . '/' . ltrim($src, '/');
        }
        ?>
        <script
            src="<?= htmlspecialchars($src) ?>"
            <?= !empty($script['async']) ? 'async' : '' ?>
            <?= !empty($script['defer']) ? 'defer' : '' ?>>
        </script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>