<?php
$page_title = 'Rekening';
require_once '../../includes/header.php';
include '../../includes/sidebar.php';
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

<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 1200px;">
        
        <?php
        // Eksekusi query statistik sebelum merender HTML
        $bankCount = fetchRow("SELECT COUNT(*) as total FROM rekening WHERE nama_bank != 'QRIS' AND nama_bank NOT LIKE '%qris%'")['total'] ?? 0;
        $qrisCount = fetchRow("SELECT COUNT(*) as total FROM rekening WHERE nama_bank = 'QRIS' OR nama_bank LIKE '%qris%'")['total'] ?? 0;
        ?>

        <div class="dash-header mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="dash-title d-flex align-items-center gap-2">
                    <i class="fas fa-wallet text-primary"></i> Rekening Penerima
                </h1>
                <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">
                    Kelola nomor rekening bank dan QRIS untuk pembayaran pelanggan.
                </div>
            </div>
            <div>
                <a href="create.php" class="btn btn-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i> Tambah Rekening
                </a>
            </div>
        </div>

        <div class="panel-editorial d-flex flex-nowrap align-items-center gap-3 mb-4 p-3 px-4 overflow-auto hide-scrollbar" style="background: var(--bg-surface); white-space: nowrap; -webkit-overflow-scrolling: touch;">
            
            <div class="d-flex align-items-center gap-3 pe-4 border-end" style="min-width: fit-content;">
                <div style="width: 44px; height: 44px; background: #F3F4F6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-university text-dark"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Tersimpan</div>
                    <div class="fw-bold text-dark fs-5" style="line-height: 1;"><?= $total ?></div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 pe-4 border-end" style="min-width: fit-content;">
                <div style="width: 44px; height: 44px; background: #EFF6FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-credit-card text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Transfer Bank</div>
                    <div class="fw-bold text-primary fs-5" style="line-height: 1;"><?= $bankCount ?></div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3" style="min-width: fit-content;">
                <div style="width: 44px; height: 44px; background: #ECFDF5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-qrcode text-success"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">QRIS Aktif</div>
                    <div class="fw-bold text-success fs-5" style="line-height: 1;"><?= $qrisCount ?></div>
                </div>
            </div>
            
        </div>

        <div class="panel-editorial p-0 overflow-hidden mb-5">
            
            <div class="p-3 bg-white border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-list text-primary me-2"></i> Daftar Metode Pembayaran</h3>
                
                <form method="GET" class="m-0 position-relative w-100" style="max-width: 350px;">
                    <i class="fas fa-search position-absolute text-muted" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 0.85rem;"></i>
                    <input type="text" name="search" class="form-control bg-light fw-bold text-dark border-0" 
                           placeholder="Cari nama, nomor, atau bank..." value="<?= htmlspecialchars($search) ?>" 
                           style="font-size: 0.85rem; padding-left: 36px; border-radius: 100px; box-shadow: none; outline: none;">
                    <?php if (!empty($search)): ?>
                        <a href="index.php" class="position-absolute text-danger" style="top: 50%; right: 14px; transform: translateY(-50%);"><i class="fas fa-times-circle"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table-editorial mb-0" style="min-width: 800px;">
                    <thead class="bg-light">
                        <tr>
                            <th width="25%">Nama Pemilik</th>
                            <th width="20%">Provider / Bank</th>
                            <th width="25%">No. Rek / Merchant ID</th>
                            <th width="15%" class="text-center">Tipe</th>
                            <th width="15%" class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rekening): ?>
                            <?php foreach ($rekening as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem; max-width: 180px;" title="<?= htmlspecialchars($r['nama_pemilik']) ?>"><?= htmlspecialchars($r['nama_pemilik']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><i class="far fa-calendar-plus me-1"></i><?= formatDate($r['created_at'], 'd/m/Y') ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($r['nama_bank']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-family: 'Consolas', monospace; font-size: 0.95rem; font-weight: 700; color: #111827; letter-spacing: 1px;">
                                            <?php if (isQRIS($r)): ?>
                                                <span class="text-success"><?= htmlspecialchars($r['nomor_rekening']) ?></span>
                                            <?php else: ?>
                                                <?php
                                                $masked = strlen($r['nomor_rekening']) > 8 ? 
                                                    substr($r['nomor_rekening'], 0, 4) . ' •••• ' . substr($r['nomor_rekening'], -4) : 
                                                    $r['nomor_rekening'];
                                                ?>
                                                <?= htmlspecialchars($masked) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if (isQRIS($r)): ?>
                                            <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">
                                                <i class="fas fa-qrcode me-1"></i> QRIS
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-clean" style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;">
                                                <i class="fas fa-university me-1"></i> Bank
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1 flex-nowrap">
                                            <?php if (isQRIS($r)): ?>
                                                <?php if (!empty($r['qr_image'])): ?>
                                                    <button class="btn-action-icon embed" 
                                                            onclick="showQRISImage('<?= htmlspecialchars($r['qr_image']) ?>', '<?= htmlspecialchars($r['nama_pemilik']) ?>')"
                                                            title="Lihat QR Code">
                                                        <i class="fas fa-eye text-success"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-action-icon embed" style="opacity: 0.5; cursor: not-allowed;" title="Gambar QR belum diupload">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <a href="edit.php?id=<?= $r['id'] ?>" class="btn-action-icon edit" title="Edit Data">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button type="button" class="btn-action-icon delete" title="Hapus Permanen"
                                                    onclick="showDeleteModal(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nama_pemilik'])) ?>', '<?= htmlspecialchars(addslashes($r['nama_bank'])) ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 bg-white">
                                    <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                        <i class="fas fa-wallet text-muted fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1"><?= !empty($search) ? 'Pencarian Tidak Ditemukan' : 'Data Rekening Kosong' ?></h5>
                                    <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;"><?= !empty($search) ? 'Coba gunakan kata kunci lain.' : 'Belum ada metode pembayaran yang terdaftar. Tambahkan rekening bank atau QRIS sekarang.' ?></p>
                                    <?php if (empty($search)): ?>
                                        <a href="create.php" class="btn btn-dark rounded-pill fw-bold px-4">
                                            <i class="fas fa-plus me-2"></i>Tambah Rekening
                                        </a>
                                    <?php else: ?>
                                        <a href="index.php" class="btn btn-dark rounded-pill fw-bold px-4">Reset Pencarian</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="p-3 border-top bg-light">
                    <?= pagination($page, $totalPages, $baseUrl) ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div class="modal fade" id="qrisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1); max-width: 380px;">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15); overflow: hidden;">
            
            <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-center" style="background: #10B981; padding: 1.5rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-qrcode text-white fs-4"></i>
                    <h5 class="modal-title text-white fw-bold m-0" style="font-size: 1.1rem; letter-spacing: 0.02em;">Scan QRIS</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body text-center p-4" style="background: #ECFDF5;">
                <div class="bg-white p-3 rounded-4 shadow-sm d-inline-block position-relative w-100">
                    <img id="qris-image-display" src="" alt="QRIS QR Code" class="img-fluid rounded-3" style="width: 100%; aspect-ratio: 1/1; object-fit: contain;">
                </div>
                <h5 class="fw-bold text-dark mt-4 mb-1" id="qris-merchant-display" style="letter-spacing: -0.02em;">Nama Merchant</h5>
                <div class="text-success fw-bold mb-3" style="font-size: 0.85rem;"><i class="fas fa-check-circle me-1"></i> Terverifikasi QRIS Nasional</div>
                
                <div class="bg-white rounded-3 p-3 text-start border shadow-sm mt-3" style="border-color: #A7F3D0 !important;">
                    <div class="text-muted fw-bold text-uppercase mb-2" style="font-size: 0.7rem;"><i class="fas fa-info-circle me-1"></i> Cara Pembayaran</div>
                    <ul class="text-dark mb-0 ps-3" style="font-size: 0.8rem; line-height: 1.6;">
                        <li>Buka aplikasi Bank / E-Wallet.</li>
                        <li>Pilih menu <strong>Scan QR</strong>.</li>
                        <li>Arahkan kamera ke kode di atas.</li>
                    </ul>
                </div>
            </div>
            
            <div class="modal-footer border-0 p-3 bg-white d-flex flex-nowrap gap-2 justify-content-center">
                <button type="button" class="btn btn-light fw-bold w-50 rounded-pill" data-bs-dismiss="modal">Tutup</button>
                <a id="downloadQR" class="btn btn-success fw-bold w-50 rounded-pill" download>
                    <i class="fas fa-download me-1"></i> Simpan
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-trash-alt" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Rekening?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus <strong id="delBankName" class="text-dark"></strong> a.n <strong id="delOwnerName" class="text-dark"></strong>? 
                    Ini akan menghapus pilihan pembayaran ini dari halaman Checkout.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold rounded-pill" style="background: #EF4444; border: none;">Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Utilities for Mobile Scrolling */
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
// Fungsi untuk memunculkan modal hapus
function showDeleteModal(id, owner, bank) {
    document.getElementById('delOwnerName').textContent = owner;
    document.getElementById('delBankName').textContent = bank;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Efek Loading saat tombol hapus di dalam modal ditekan
document.addEventListener('DOMContentLoaded', function() {
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if(confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Proses...';
            this.style.opacity = '0.8';
            this.style.pointerEvents = 'none';
        });
    }
});

// Fungsi Render QRIS (Mendukung Image Upload & Raw Payload Text)
function showQRISImage(imagePath, merchantName) {
    if (!imagePath || imagePath.trim() === '' || imagePath === 'null') {
        alert('Data QRIS belum tersedia untuk rekening ini.');
        return;
    }
    
    let imageUrl = '';
    
    // Cek apakah string mengandung ekstensi file gambar (.png, .jpg, dll)
    if (imagePath.match(/\.(jpeg|jpg|gif|png)$/i)) {
        let cleanPath = imagePath.replace(/^(\.\.\/)+/, ''); 
        cleanPath = cleanPath.replace(/^(\.\/)+/, '');       
        cleanPath = cleanPath.replace(/^\/+/, '');           
        imageUrl = '<?= BASE_URL ?>' + cleanPath + '?t=' + Date.now(); 
    } else {
        // Generate teks mentah jadi gambar QR Barcode instan
        imageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=15&data=' + encodeURIComponent(imagePath.trim());
    }
    
    // Tampilkan ke Modal
    document.getElementById('qris-image-display').src = imageUrl;
    document.getElementById('qris-merchant-display').textContent = merchantName;
    
    document.getElementById('downloadQR').href = imageUrl;
    document.getElementById('downloadQR').download = `QRIS_${merchantName.replace(/\s+/g, '_')}.png`;
    
    const modal = new bootstrap.Modal(document.getElementById('qrisModal'));
    modal.show();
    
    // Fallback error
    document.getElementById('qris-image-display').onerror = function() {
        this.src = 'https://via.placeholder.com/300x300/FFE4E6/EF4444?text=Gagal+Memuat+QR';
    };
}
</script>

<?php require_once '../../includes/footer.php'; ?>