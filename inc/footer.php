<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<?php if (!empty($extraJS)): ?>
    <?php foreach ($extraJS as $script): ?>
        <script
            src="<?= htmlspecialchars($script['src']) ?>"
            <?= !empty($script['async']) ? 'async' : '' ?>
            <?= !empty($script['defer']) ? 'defer' : '' ?>>
        </script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>