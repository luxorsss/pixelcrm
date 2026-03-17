<?php
$page_title = 'Template Pesan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/functions.php';

// Get all products with template count
$products = fetchAll("
    SELECT p.id, p.nama,
           COUNT(t.id) as template_count,
           MAX(CASE WHEN t.jenis_pesan = 'invoice' THEN 1 ELSE 0 END) as has_invoice,
           MAX(CASE WHEN t.jenis_pesan = 'akses_produk' THEN 1 ELSE 0 END) as has_akses
    FROM produk p 
    LEFT JOIN template_pesan_produk t ON p.id = t.produk_id 
    GROUP BY p.id, p.nama 
    ORDER BY p.nama
");
?>

<div class="d-flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><?= $page_title ?></h2>
                    <p class="text-muted mb-0">Kelola template pesan untuk setiap produk</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Template Pesan</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada produk</h5>
                            <p class="text-muted">Silakan buat produk terlebih dahulu di menu Produk</p>
                            <a href="<?= BASE_URL ?>modules/produk/create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Buat Produk
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-center">Template Invoice</th>
                                        <th class="text-center">Template Akses</th>
                                        <th class="text-center">Total Template</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?= clean($product['nama']) ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($product['has_invoice']): ?>
                                                    <span class="badge bg-success">✓</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($product['has_akses']): ?>
                                                    <span class="badge bg-success">✓</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= $product['template_count'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="edit.php?id=<?= $product['id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit me-1"></i>Kelola Template
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle me-2"></i>Informasi Template</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Template Invoice:</strong>
                            <p class="text-muted mb-2">Template pesan yang dikirim saat customer melakukan pemesanan</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Template Akses Produk:</strong>
                            <p class="text-muted mb-2">Template pesan yang dikirim saat memberikan akses produk digital</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>