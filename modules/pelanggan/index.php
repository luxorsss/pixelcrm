<?php
// ===============================
// LOGIC SECTION
// ===============================
$page_title = "Kelola Pelanggan";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Handle search
$search = get('search', '');
$search = clean(trim($search));

// Set default values for pagination variables
$records_per_page = 10; // Set this according to your preferred number per page
$total_records = getTotalPelanggan($search);
// Debug: tampilkan nilai total_records
error_log("Total Records: " . $total_records);
echo "<!-- Debug: Total Records = " . $total_records . " -->";
$total_pages = max(1, (int)ceil($total_records / $records_per_page));

// Get the current page, default to 1 if it's not set or invalid
// Handle page parameter dengan benar
$current_page = 1;
if (isset($_GET['page'])) {
    $page_param = $_GET['page'];
    // Handle kasus dimana page bisa array atau string kosong
    if (is_array($page_param)) {
        $current_page = max(1, (int)array_values($page_param)[0]);
    } else {
        $current_page = max(1, (int)$page_param);
    }
}

// Pastikan base_url tidak mengandung duplikasi parameter
$base_url = 'index.php';
if (!empty($search)) {
    $base_url .= '?search=' . urlencode($search);
} else {
    $base_url .= '?';
}
$current_page = max(1, min($current_page, $total_pages));

// Calculate the start and end record numbers
$start_record = ($current_page - 1) * $records_per_page + 1;
$end_record = min($current_page * $records_per_page, $total_records);

// Build base_url with search query if necessary
$base_url = 'index.php';
if (!empty($search)) {
    // Ensure base_url ends with '?' or '&' before appending 'page'
    $base_url .= (strpos($base_url, '?') === false ? '?' : '&') . 'search=' . urlencode($search);
}

// Fetch the pelanggan list based on pagination and search
$pelanggan_list = getAllPelanggan($current_page, $records_per_page, $search);

// ===============================
// PRESENTATION SECTION
// ===============================
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content dashboard-wrapper">
    <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title">Database Pelanggan</h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Kelola data kontak dan pantau aktivitas belanja.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="bulk.php" class="btn btn-light text-dark fw-bold border" style="border-radius: 12px;">
                <i class="fas fa-upload me-1"></i> Bulk Import
            </a>
            <a href="create.php" class="btn btn-primary fw-bold" style="border-radius: 12px;">
                <i class="fas fa-plus me-1"></i> Tambah Pelanggan
            </a>
        </div>
    </div>

    <div class="w-100">
        <?php displaySessionMessage(); ?>
        
        <div class="list-container p-3 mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <form method="GET" class="d-flex flex-wrap align-items-center gap-2 m-0 w-100">
                <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2 border border-light flex-grow-1" style="max-width: 500px; transition: var(--transition);" id="searchContainer">
                    <i class="fas fa-search text-muted me-2"></i>
                    <input type="text" name="search" class="form-control border-0 bg-transparent p-0 text-dark fw-bold" 
                           placeholder="Cari nama atau nomor WA..." value="<?= safeHtml($search) ?>" autocomplete="off" 
                           style="font-size: 0.95rem; outline: none; box-shadow: none;"
                           onfocus="document.getElementById('searchContainer').style.borderColor='#111827'; document.getElementById('searchContainer').style.background='#ffffff';"
                           onblur="document.getElementById('searchContainer').style.borderColor='transparent'; document.getElementById('searchContainer').style.background='#f8f9fa';">
                </div>
                
                <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">Cari</button>
                
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn btn-light text-danger btn-sm rounded-pill px-3 fw-bold border-0" style="padding-top: 0.5rem; padding-bottom: 0.5rem;">
                        <i class="fas fa-times me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </form>

            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($search)): ?>
                    <div class="badge-clean bg-light text-muted border">
                        Hasil: <strong class="text-dark"><?= $total_records ?></strong>
                    </div>
                <?php else: ?>
                    <div class="badge-clean" style="background: #EFF6FF; color: #2563EB; font-size: 0.85rem; padding: 0.5rem 1rem; white-space: nowrap;">
                        <i class="fas fa-users me-2"></i><?= number_format($total_records) ?> Total Pelanggan
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-list-container shadow-sm mb-4">
            <?php if (empty($pelanggan_list)): ?>
                <div class="text-center py-5">
                    <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <i class="fas fa-<?= !empty($search) ? 'search' : 'user-slash' ?> text-muted fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?= !empty($search) ? 'Tidak Ditemukan' : 'Belum Ada Pelanggan' ?></h5>
                    <p class="text-muted mb-4"><?= !empty($search) ? 'Coba gunakan kata kunci atau nomor WA lain.' : 'Mulai tambahkan pelanggan pertama atau import data secara bulk.' ?></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <?php if (!empty($search)): ?>
                            <a href="index.php" class="btn btn-dark rounded-pill px-4 fw-bold">Reset Pencarian</a>
                        <?php else: ?>
                            <a href="create.php" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="fas fa-plus me-2"></i>Tambah Manual</a>
                            <a href="bulk.php" class="btn btn-success rounded-pill px-4 fw-bold"><i class="fas fa-upload me-2"></i>Import Excel</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-editorial mb-0">
                        <thead>
                            <tr>
                                <th width="60" class="text-center">#ID</th>
                                <th>Info Pelanggan</th>
                                <th>Kontak WhatsApp</th>
                                <th>Tgl Daftar</th>
                                <th width="100" class="text-center">Status</th>
                                <th width="150" class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pelanggan_list as $pelanggan): ?>
                            <?php $stats = getStatistikPelanggan($pelanggan['id']); ?>
                            <tr>
                                <td class="text-center text-muted fw-bold" style="font-size: 0.85rem;"><?= $pelanggan['id'] ?></td>
                                
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= safeHtml($pelanggan['nama']) ?></div>
                                    <?php if ($stats['total_transaksi'] > 0): ?>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge-clean" style="background: #ECFDF5; color: #059669; padding: 0.2rem 0.5rem; font-size: 0.7rem;">
                                                <i class="fas fa-shopping-cart"></i> <?= $stats['total_transaksi'] ?> Trx
                                            </span>
                                            <?php if ($stats['total_pembelian'] > 0): ?>
                                                <span class="text-muted fw-bold" style="font-size: 0.75rem;"><?= formatCurrency($stats['total_pembelian']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.75rem;">Belum ada transaksi</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" target="_blank" class="badge-wa badge-clean">
                                        <i class="fab fa-whatsapp"></i> <?= $pelanggan['nomor_wa'] ?>
                                    </a>
                                </td>
                                
                                <td>
                                    <div class="text-dark fw-bold" style="font-size: 0.85rem;"><?= formatDate($pelanggan['tanggal_daftar'], 'd M Y') ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= formatDate($pelanggan['tanggal_daftar'], 'H:i') ?> WIB</div>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($stats['total_transaksi'] == 0): ?>
                                        <span class="badge-clean" style="background: #F3F4F6; color: #4B5563;">Baru</span>
                                    <?php elseif ($stats['transaksi_terakhir'] && (strtotime($stats['transaksi_terakhir']) > (time() - 2592000))): ?>
                                        <span class="badge-clean" style="background: #EFF6FF; color: #2563EB;">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-clean" style="background: #FFFBEB; color: #D97706;">Lama</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="histori.php?id=<?= $pelanggan['id'] ?>" class="btn-action-icon embed" title="Histori Pembelian">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $pelanggan['id'] ?>" class="btn-action-icon edit" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn-action-icon delete" title="Hapus"
                                                onclick="showDeleteModal(<?= $pelanggan['id'] ?>, '<?= addslashes(safeHtml($pelanggan['nama'])) ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="p-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3" style="background: #F9FAFB;">
                        <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                            Data <?= $start_record ?> - <?= $end_record ?> dari <?= $total_records ?>
                        </div>
                        <div class="d-flex gap-1">
                            <?php echo generatePagination($current_page, $total_pages, $base_url); ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-user-times" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Pelanggan?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus <strong id="deleteCustName" class="text-dark"></strong>? 
                    Semua transaksi dan riwayat yang terkait dengan pelanggan ini akan ikut terhapus.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal" style="border-radius: 12px;">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold" style="border-radius: 12px; background: #EF4444; border: none;">Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteModal(id, nama) {
    document.getElementById('deleteCustName').textContent = nama;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Proses...';
    this.style.opacity = '0.8';
    this.style.pointerEvents = 'none';
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>