<?php if (!in_array($current_file, $public_pages)): ?>
    <nav class="mobile-bottom-nav d-lg-none" aria-label="Navigasi Utama Ponsel">
        <a href="<?= BASE_URL ?>" class="bottom-nav-item <?= ($current_page ?? '') === 'index.php' && empty($is_in_module) ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i>
            <span>Home</span>
        </a>
        <a href="<?= BASE_URL ?>modules/transaksi/" class="bottom-nav-item <?= ($current_module ?? '') === 'transaksi' ? 'active' : '' ?>">
            <i class="fas fa-receipt"></i>
            <span>Order</span>
        </a>
        <a href="<?= BASE_URL ?>modules/produk/" class="bottom-nav-item <?= ($current_module ?? '') === 'produk' ? 'active' : '' ?>">
            <i class="fas fa-box-open"></i>
            <span>Produk</span>
        </a>
        <a href="<?= BASE_URL ?>modules/pelanggan/" class="bottom-nav-item <?= ($current_module ?? '') === 'pelanggan' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Klien</span>
        </a>
        <button type="button" class="bottom-nav-item" onclick="toggleSidebar()" aria-label="Buka Menu Lengkap">
            <i class="fas fa-bars"></i>
            <span>Menu</span>
        </button>
    </nav>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        if (alert) new bootstrap.Alert(alert).close();
    });
}, 3000);
</script>
</body>
</html>