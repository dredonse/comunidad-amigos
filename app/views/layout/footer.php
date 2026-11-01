    </div><!-- /container -->
</main>

<footer class="text-center text-muted py-3 mt-4 border-top">
    <small>&copy; <?= date('Y') ?> Comunidad de Amigos</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= BASE_URL ?>/js/app.js"></script>
<?php if (isLoggedIn()): ?>
<script>
    const VAPID_PUBLIC = <?= json_encode(
        env('VAPID_PUBLIC_KEY', ''),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;
    const PUSH_SUBSCRIBE_URL = <?= json_encode(url('push/subscribe')) ?>;
    const PUSH_UNSUBSCRIBE_URL = <?= json_encode(url('push/unsubscribe')) ?>;
</script>
<script src="<?= BASE_URL ?>/js/push.js"></script>
<?php endif; ?>
</body>
</html>
