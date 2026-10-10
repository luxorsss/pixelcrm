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
<script src="<?= BASE_URL ?>assets/js/toast.js?v=<?= time() ?>"></script>
<?php 
$flash_msg = getMessage();
if ($flash_msg): 
    $f_text = addslashes(clean($flash_msg[0]));
    $f_type = $flash_msg[1] === 'danger' ? 'error' : $flash_msg[1];
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.PixelToast) {
        window.PixelToast.show("<?= $f_text ?>", "<?= $f_type ?>");
    }
});
</script>
<?php endif; ?>
</body>
</html>