<!-- Sitewide alert toast -->
<?php if (!empty($_SESSION['alert'])): ?>
<div id="site-toast" aria-live="polite" aria-atomic="true"
     style="position:fixed;top:1.5rem;left:50%;transform:translateX(-50%);z-index:9999;min-width:280px;max-width:360px;">
    <div style="background:#fff;border:1px solid var(--line);border-radius:8px;
                box-shadow:0 4px 20px rgba(26,44,91,0.13);overflow:hidden;">
        <div style="background:var(--navy);padding:0.6rem 1rem;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-family:'DM Sans',sans-serif;font-size:0.75rem;font-weight:700;
                         letter-spacing:0.1em;text-transform:uppercase;color:#fff;">
                <i class="bi bi-check-circle me-1"></i> Atheneum
            </span>
            <button onclick="document.getElementById('site-toast').remove()"
                    style="background:none;border:none;color:rgba(255,255,255,0.7);cursor:pointer;font-size:1rem;line-height:1;padding:0;"
                    aria-label="Dismiss">&times;</button>
        </div>
        <div style="padding:0.85rem 1rem;font-family:'DM Sans',sans-serif;font-size:0.9rem;color:var(--ink);">
            <?= htmlspecialchars($_SESSION['alert']) ?>
        </div>
        <div id="site-toast-bar"
             style="height:3px;background:var(--gold);transform-origin:left;transition:transform 4s linear;"></div>
    </div>
</div>
<script>
    (function() {
        var bar = document.getElementById('site-toast-bar');
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                bar.style.transform = 'scaleX(0)';
            });
        });
        setTimeout(function() {
            var t = document.getElementById('site-toast');
            if (t) t.remove();
        }, 4200);
    })();
</script>
<?php unset($_SESSION['alert']); ?>
<?php endif; ?>

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