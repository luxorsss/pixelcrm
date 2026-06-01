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
    
    <div class="main-content dashboard-wrapper flex-grow-1">
        <div class="content-area">
            
            <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="dash-title">Template Pesan WA</h1>
                    <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Kelola format pesan otomatis (Invoice & Akses) untuk setiap produk.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>modules/produk/create.php" class="btn btn-light text-dark fw-bold border" style="border-radius: 12px;">
                        <i class="fas fa-plus me-1"></i> Buat Produk Baru
                    </a>
                </div>
            </div>

            <div class="panel-editorial mb-4" style="background: linear-gradient(to right, #EFF6FF, #FFFFFF); border-left: 4px solid #3B82F6;">
                <div class="d-flex align-items-start gap-3 p-2">
                    <i class="fas fa-lightbulb text-primary fs-4 mt-1"></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Cara Kerja Template</h6>
                        <p class="text-muted mb-0" style="font-size: 0.85rem; line-height: 1.6;">
                            <strong>Template Invoice:</strong> Akan dikirim otomatis saat customer melakukan pemesanan (Order Pending).<br>
                            <strong>Template Akses:</strong> Akan dikirim otomatis saat transaksi ditandai sebagai Selesai / Lunas.
                        </p>
                    </div>
                </div>
            </div>

            <div class="product-list-container shadow-sm mb-4">
                <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold list-header m-0 p-0 text-dark">
                        <i class="fas fa-list-alt text-primary me-2"></i>Daftar Template per Produk
                    </h5>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                <i class="fas fa-box-open text-muted fs-2"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Belum Ada Produk</h5>
                            <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Kamu harus membuat produk terlebih dahulu sebelum dapat mengatur template pesan.</p>
                            <a href="<?= BASE_URL ?>modules/produk/create.php" class="btn btn-dark rounded-pill fw-bold px-4">
                                <i class="fas fa-plus me-2"></i>Buat Produk Sekarang
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-editorial mb-0">
                                <thead>
                                    <tr>
                                        <th width="35%">Nama Produk</th>
                                        <th class="text-center" width="20%">Template Invoice</th>
                                        <th class="text-center" width="20%">Template Akses</th>
                                        <th class="text-center" width="10%">Total</th>
                                        <th class="text-end pe-4" width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div style="width: 40px; height: 40px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                    <strong class="text-dark" style="font-size: 0.95rem;"><?= clean($product['nama']) ?></strong>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($product['has_invoice']): ?>
                                                    <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">
                                                        <i class="fas fa-check-circle me-1"></i>Tersedia
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-clean" style="background: #F9FAFB; color: #9CA3AF; border: 1px dashed #D1D5DB;">
                                                        <i class="fas fa-minus me-1"></i>Kosong
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($product['has_akses']): ?>
                                                    <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">
                                                        <i class="fas fa-check-circle me-1"></i>Tersedia
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-clean" style="background: #F9FAFB; color: #9CA3AF; border: 1px dashed #D1D5DB;">
                                                        <i class="fas fa-minus me-1"></i>Kosong
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold <?= $product['template_count'] > 0 ? 'text-primary' : 'text-muted' ?>" style="font-size: 1.1rem;">
                                                    <?= $product['template_count'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-dark btn-sm rounded-pill fw-bold px-3 transition-all hover-lift">
                                                    <i class="fas fa-cog me-1"></i> Kelola
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

        </div>    
    </div>
</div>

<style>
/* Micro-interaction hover */
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.hover-lift:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15); }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>