<?php
// === LOGIC SECTION (No Output) ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$page_title = "Kelola Produk";

// Mengambil semua produk tanpa pagination
$produk_list = getAllProduk(); // Sekarang akan mengambil semua data
$total_records = count($produk_list);

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <!-- Top Header -->
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Kelola Produk</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Produk</li>
                    </ol>
                </nav>
            </div>
            <div class="text-muted">
                <i class="fas fa-calendar me-1"></i><?= date('d F Y') ?>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- Header Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box me-2"></i>Kelola Produk</h2>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tambah Produk
            </a>
        </div>
        
        <!-- Main Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Daftar Produk (<?= $total_records ?> produk)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($produk_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada produk</h5>
                        <p class="text-muted">Klik tombol "Tambah Produk" untuk menambahkan produk pertama.</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Produk Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Tabel dengan scroll vertikal untuk menampilkan semua produk -->
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Nama Produk</th>
									<th>Profit</th>
                                    <th width="120">Harga</th>
                                    <th width="150">Admin WA</th>
                                    <th width="120">OneSender</th>
                                    <th width="130">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produk_list as $produk): ?>
                                <tr>
                                    <td class="fw-bold"><?= $produk['id'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= clean($produk['nama']) ?></div>
                                        <?php if ($produk['deskripsi']): ?>
                                            <small class="text-muted"><?= truncateText($produk['deskripsi'], 50) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= formatCurrency($produk['profit']) ?></td>
									<td class="fw-bold text-primary"><?= formatCurrency($produk['harga']) ?></td>
                                    <td>
                                        <?php if ($produk['admin_wa']): ?>
                                            <a href="<?= whatsappLink($produk['admin_wa']) ?>" target="_blank" class="text-success text-decoration-none">
                                                <i class="fab fa-whatsapp"></i> <?= clean($produk['admin_wa']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= clean($produk['onesender_account']) ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
											<a href="<?= BASE_URL ?>co.php?id=<?= $produk['id'] ?>" 
                                               class="btn btn-outline-success" 
                                               title="Checkout"
                                               target="_blank">
                                                <i class="fas fa-shopping-cart"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $produk['id'] ?>" 
                                               class="btn btn-outline-warning" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $produk['id'] ?>" 
                                               class="btn btn-outline-danger btn-delete" 
                                               title="Hapus"
                                               data-name="<?= clean($produk['nama']) ?>"
                                               onclick="return confirm('Yakin ingin menghapus produk \'<?= clean($produk['nama']) ?>\'?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>    
</div>

<style>
/* CSS untuk header tabel yang tetap terlihat saat scroll */
.table-responsive thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>