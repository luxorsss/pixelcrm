<?php
$page_title = 'Rekening';
require_once '../../includes/header.php';
require_once 'functions.php';

// Get parameters
$page = max(1, (int)get('page', 1));
$search = clean(get('search', ''));
$limit = 10;

// Get data
$rekening = getRekening($page, $limit, $search);
$total = countRekening($search);
$totalPages = ceil($total / $limit);

// Build pagination URL
$baseUrl = '?';
if ($search) $baseUrl .= 'search=' . urlencode($search) . '&';
?>

<div class="d-flex">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content flex-grow-1">
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-university me-2"></i>Rekening</h2>
                    <p class="text-muted mb-0">Kelola rekening bank dan QRIS</p>
                </div>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Rekening
                </a>
            </div>

            <!-- Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari nama pemilik, nomor rekening, atau bank..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h3 class="text-primary"><?= $total ?></h3>
                            <small class="text-muted">Total Rekening</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <?php
                            $bankCount = fetchRow("SELECT COUNT(*) as total FROM rekening WHERE nama_bank != 'QRIS' AND nama_bank NOT LIKE '%qris%'")['total'] ?? 0;
                            ?>
                            <h3 class="text-info"><?= $bankCount ?></h3>
                            <small class="text-muted">Rekening Bank</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <?php
                            $qrisCount = fetchRow("SELECT COUNT(*) as total FROM rekening WHERE nama_bank = 'QRIS' OR nama_bank LIKE '%qris%'")['total'] ?? 0;
                            ?>
                            <h3 class="text-success"><?= $qrisCount ?></h3>
                            <small class="text-muted">QRIS</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rekening List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Rekening</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($rekening): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pemilik</th>
                                        <th>Nomor</th>
                                        <th>Bank/Metode</th>
                                        <th>Tipe</th>
                                        <th>Tanggal</th>
                                        <th width="120">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rekening as $r): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($r['nama_pemilik']) ?></strong>
                                            </td>
                                            <td>
                                                <?php if (isQRIS($r)): ?>
                                                    <code class="text-success"><?= htmlspecialchars($r['nomor_rekening']) ?></code>
                                                    <br><small class="text-muted">Merchant ID</small>
                                                <?php else: ?>
                                                    <?php
                                                    $masked = strlen($r['nomor_rekening']) > 8 ? 
                                                        substr($r['nomor_rekening'], 0, 4) . '****' . substr($r['nomor_rekening'], -4) : 
                                                        $r['nomor_rekening'];
                                                    ?>
                                                    <code><?= htmlspecialchars($masked) ?></code>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($r['nama_bank']) ?>
                                            </td>
                                            <td>
                                                <?php if (isQRIS($r)): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-qrcode"></i> QRIS
                                                    </span>
                                                    <?php if (!empty($r['qr_image'])): ?>
                                                        <button class="btn btn-sm btn-outline-success ms-1" 
                                                                onclick="showQRISImage('<?= htmlspecialchars($r['qr_image']) ?>', '<?= htmlspecialchars($r['nama_pemilik']) ?>')"
                                                                title="Lihat QR Code">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark ms-1" title="Belum ada gambar QR">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-university"></i> Bank
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= formatDate($r['created_at'], 'd/m/Y H:i') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit.php?id=<?= $r['id'] ?>" 
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?= $r['id'] ?>" 
                                                       class="btn btn-outline-danger" title="Hapus"
                                                       onclick="return confirm('Yakin hapus rekening ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="p-3 border-top">
                                <?= pagination($page, $totalPages, $baseUrl) ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-university fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada rekening</h5>
                            <p class="text-muted">Tambahkan rekening bank atau QRIS untuk menerima pembayaran</p>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Rekening Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QRIS Modal -->
<div class="modal fade" id="qrisModal" tabindex="-1" aria-labelledby="qrisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                <h5 class="modal-title" id="qrisModalLabel">
                    <i class="fas fa-qrcode me-2"></i>QR Code QRIS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <img id="qris-image-display" src="" alt="QRIS QR Code" class="img-fluid mb-3" style="max-width: 300px; border: 2px solid #28a745; border-radius: 8px;">
                    <h6 class="fw-bold mb-2" id="qris-merchant-display"></h6>
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Scan QR Code dengan aplikasi pembayaran (DANA, OVO, GoPay, dll)
                        </small>
                    </div>
                    <div class="alert alert-success">
                        <small>
                            <i class="fas fa-mobile-alt me-1"></i>
                            Buka aplikasi → Scan QR → Bayar
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                </button>
                <a id="downloadQR" class="btn btn-success" download>
                    <i class="fas fa-download me-1"></i>Download QR
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Show QRIS Image in Modal
function showQRISImage(imagePath, merchantName) {
    console.log('🎯 Showing QRIS Image:', imagePath);
    
    // Check if image path exists
    if (!imagePath || imagePath.trim() === '' || imagePath === 'null') {
        alert('Gambar QRIS belum diupload untuk rekening ini.');
        return;
    }
    
    // Set image and data
    const imageUrl = '../../' + imagePath + '?t=' + Date.now(); // Cache buster
    document.getElementById('qris-image-display').src = imageUrl;
    document.getElementById('qris-merchant-display').textContent = merchantName;
    
    // Set download link
    document.getElementById('downloadQR').href = imageUrl;
    document.getElementById('downloadQR').download = `qris_${merchantName.replace(/\s+/g, '_')}.png`;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('qrisModal'));
    modal.show();
    
    // Handle image load error
    document.getElementById('qris-image-display').onerror = function() {
        alert('Gagal memuat gambar QRIS. File mungkin tidak ditemukan.');
        modal.hide();
    };
}

// Auto refresh page when modal is hidden (optional)
document.getElementById('qrisModal').addEventListener('hidden.bs.modal', function () {
    // Optional: refresh data atau clear cache
});
</script>

<?php require_once '../../includes/footer.php'; ?>