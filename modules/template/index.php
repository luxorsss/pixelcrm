<?php
$page_title = 'Template Pesan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
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

<div class="main-content dashboard-wrapper flex-grow-1">
    
    <div class="dash-header flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-envelope-open-text text-primary"></i> Automasi Template
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Kelola format pesan otomatis (Invoice & Akses) untuk setiap produk.</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>modules/produk/create.php" class="btn btn-dark fw-bold rounded-pill px-4" style="box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);">
                <i class="fas fa-plus me-2"></i> Tambah Produk
            </a>
        </div>
    </div>

    <div class="alert alert-editorial mb-4 p-3 p-md-4 d-flex flex-column flex-sm-row align-items-start gap-3" style="background: #EFF6FF; border-left-color: #3B82F6; border-radius: 16px;">
        <div style="width: 48px; height: 48px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 4px rgba(59,130,246,0.1);">
            <i class="fas fa-lightbulb text-primary fs-4"></i>
        </div>
        <div>
            <h6 class="fw-bold text-dark mb-2">Cara Kerja Automasi Template</h6>
            <div class="text-muted" style="font-size: 0.85rem; line-height: 1.6;">
                <ul class="mb-0 ps-3">
                    <li class="mb-1"><strong class="text-dark">Template Invoice:</strong> Akan dikirimkan secara otomatis ke WhatsApp pembeli saat pesanan baru masuk (Status <em>Menunggu</em>).</li>
                    <li><strong class="text-dark">Template Akses:</strong> Akan dikirimkan otomatis beserta link produk/file akses saat transaksi ditandai sebagai <em>Selesai / Lunas</em>.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="panel-editorial p-0 overflow-hidden mb-5">
        <div class="p-3 p-md-4 border-bottom bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold list-header m-0 p-0 text-dark" style="font-size: 1.1rem;">
                <i class="fas fa-list text-primary me-2"></i>Daftar Template per Produk
            </h5>
        </div>
        
        <div class="bg-light">
            <?php if (empty($products)): ?>
                <div class="text-center py-5 bg-white">
                    <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem; position: relative;">
                        <i class="fas fa-box-open text-muted fs-2"></i>
                        <i class="fas fa-sparkles text-warning position-absolute" style="top: -5px; right: -5px; font-size: 1.25rem;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Belum Ada Produk</h5>
                    <p class="text-muted mb-4 mx-auto" style="max-width: 400px;">Kamu harus membuat katalog produk terlebih dahulu sebelum dapat mengatur automasi pesannya.</p>
                    <a href="<?= BASE_URL ?>modules/produk/create.php" class="btn btn-primary rounded-pill fw-bold px-4 shadow-sm">
                        <i class="fas fa-plus me-2"></i>Buat Produk Sekarang
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-editorial mb-0" style="min-width: 700px;">
                        <thead class="bg-white">
                            <tr>
                                <th width="35%">Katalog Produk</th>
                                <th class="text-center" width="20%">Template Invoice</th>
                                <th class="text-center" width="20%">Template Akses</th>
                                <th class="text-end pe-4" width="25%">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 44px; height: 44px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary-color); flex-shrink: 0;">
                                                <i class="fas fa-box"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem; line-height: 1.2; max-width: 200px;" title="<?= clean($product['nama']) ?>"><?= clean($product['nama']) ?></div>
                                                <div class="text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-file-alt text-warning me-1"></i> <?= $product['template_count'] ?> Konfigurasi</div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if ($product['has_invoice']): ?>
                                            <span class="badge-clean bg-white" style="color: #059669; border: 1px solid #A7F3D0; box-shadow: 0 2px 4px rgba(16,185,129,0.05);">
                                                <i class="fas fa-check-circle me-1"></i>Tersedia
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-clean bg-light text-muted opacity-75" style="border: 1px dashed #D1D5DB;">
                                                <i class="fas fa-minus me-1"></i>Kosong
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if ($product['has_akses']): ?>
                                            <span class="badge-clean bg-white" style="color: #059669; border: 1px solid #A7F3D0; box-shadow: 0 2px 4px rgba(16,185,129,0.05);">
                                                <i class="fas fa-check-circle me-1"></i>Tersedia
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-clean bg-light text-muted opacity-75" style="border: 1px dashed #D1D5DB;">
                                                <i class="fas fa-minus me-1"></i>Kosong
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-end pe-4">
                                        <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-dark btn-sm rounded-pill fw-bold px-4 transition-all hover-lift">
                                            <i class="fas fa-cog me-1"></i> Konfig
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

<style>
/* Micro-interaction untuk tombol aksi */
.hover-lift { transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
@media (hover: hover) and (pointer: fine) {
    .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(17, 24, 39, 0.15); }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>