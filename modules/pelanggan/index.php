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
$records_per_page = 10;
$total_records = getTotalPelanggan($search);
$total_pages = max(1, (int)ceil($total_records / $records_per_page));

// Get the current page
$current_page = 1;
if (isset($_GET['page'])) {
    $page_param = $_GET['page'];
    if (is_array($page_param)) {
        $current_page = max(1, (int)array_values($page_param)[0]);
    } else {
        $current_page = max(1, (int)$page_param);
    }
}
$current_page = max(1, min($current_page, $total_pages));

// Calculate the start and end record numbers
$start_record = ($current_page - 1) * $records_per_page + 1;
$end_record = min($current_page * $records_per_page, $total_records);

// Build base_url
$base_url = 'index.php';
if (!empty($search)) {
    $base_url .= '?search=' . urlencode($search);
} else {
    $base_url .= '?';
}

// Fetch the pelanggan list
$pelanggan_list = getAllPelanggan($current_page, $records_per_page, $search);

// ===============================
// PRESENTATION SECTION
// ===============================
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper flex-grow-1">
    
    <div class="dash-header flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-users text-primary"></i> Database Pelanggan
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Kelola data kontak dan pantau aktivitas belanja.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="bulk.php" class="btn btn-light text-dark fw-bold border rounded-pill" style="box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                <i class="fas fa-file-import me-1"></i> Import Bulk
            </a>
            <a href="create.php" class="btn btn-dark fw-bold rounded-pill" style="box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);">
                <i class="fas fa-plus me-1"></i> Tambah Manual
            </a>
        </div>
    </div>

    <?php displaySessionMessage(); ?>
    
    <div class="panel-editorial p-3 p-md-4 mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <form method="GET" class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 m-0 flex-grow-1">
            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2 border border-light flex-grow-1" style="max-width: 450px; transition: var(--transition);" id="searchContainer">
                <i class="fas fa-search text-muted me-2"></i>
                <input type="text" name="search" class="form-control border-0 bg-transparent p-0 text-dark fw-bold" 
                        placeholder="Cari nama atau no. WhatsApp..." value="<?= safeHtml($search) ?>" autocomplete="off" 
                        style="font-size: 0.95rem; outline: none; box-shadow: none;"
                        onfocus="document.getElementById('searchContainer').style.borderColor='#3B82F6'; document.getElementById('searchContainer').style.background='#ffffff'; document.getElementById('searchContainer').style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)';"
                        onblur="document.getElementById('searchContainer').style.borderColor='transparent'; document.getElementById('searchContainer').style.background='#f8f9fa'; document.getElementById('searchContainer').style.boxShadow='none';">
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold flex-grow-1 flex-sm-grow-0 py-2">Cari Data</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn btn-light text-danger rounded-pill px-3 fw-bold border-0 py-2" title="Reset Pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
            <?php if (!empty($search)): ?>
                <div class="badge-clean bg-light text-dark border">
                    Ditemukan: <strong class="text-primary"><?= number_format($total_records) ?></strong>
                </div>
            <?php else: ?>
                <div class="badge-clean" style="background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; font-size: 0.85rem; padding: 0.5rem 1rem;">
                    <i class="fas fa-users me-2"></i><?= number_format($total_records) ?> Terdaftar
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="panel-editorial p-0 overflow-hidden mb-5">
        <?php if (empty($pelanggan_list)): ?>
            <div class="text-center py-5">
                <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                    <i class="fas fa-<?= !empty($search) ? 'search' : 'user-slash' ?> text-muted fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2"><?= !empty($search) ? 'Data Tidak Ditemukan' : 'Belum Ada Pelanggan' ?></h5>
                <p class="text-muted mb-4 mx-auto" style="max-width: 400px;"><?= !empty($search) ? 'Coba gunakan kata kunci pencarian yang berbeda.' : 'Mulai bangun databasemu dengan menambahkan pelanggan pertama atau import via Excel.' ?></p>
                
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <?php if (!empty($search)): ?>
                        <a href="index.php" class="btn btn-dark rounded-pill px-4 fw-bold">Hapus Filter Pencarian</a>
                    <?php else: ?>
                        <a href="bulk.php" class="btn btn-light border text-dark rounded-pill px-4 fw-bold"><i class="fas fa-file-excel text-success me-2"></i>Import Excel</a>
                        <a href="create.php" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="fas fa-user-plus me-2"></i>Tambah Manual</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-editorial mb-0">
                    <thead>
                        <tr>
                            <th width="8%" class="text-center">ID</th>
                            <th width="30%">Info Pelanggan & Analitik</th>
                            <th width="20%">Kontak WhatsApp</th>
                            <th width="15%">Tanggal Bergabung</th>
                            <th width="12%" class="text-center">Status</th>
                            <th width="15%" class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pelanggan_list as $pelanggan): ?>
                        <?php $stats = getStatistikPelanggan($pelanggan['id']); ?>
                        <tr>
                            <td class="text-center">
                                <span class="badge-clean bg-light text-muted border fw-bold" style="font-family: monospace;">#<?= $pelanggan['id'] ?></span>
                            </td>
                            
                            <td>
                                <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?= safeHtml($pelanggan['nama']) ?></div>
                                <?php if ($stats['total_transaksi'] > 0): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; font-size: 0.65rem;">
                                            <i class="fas fa-shopping-cart"></i> <?= $stats['total_transaksi'] ?> TRX
                                        </span>
                                        <?php if ($stats['total_pembelian'] > 0): ?>
                                            <span class="text-muted fw-bold" style="font-size: 0.75rem;"><i class="fas fa-coins text-warning me-1"></i><?= formatCurrency($stats['total_pembelian']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge-clean" style="background: #F3F4F6; color: #6B7280; font-size: 0.65rem;"><i class="fas fa-ghost me-1"></i> Belum ada Trx</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" target="_blank" class="badge-wa badge-clean text-decoration-none">
                                    <i class="fab fa-whatsapp"></i> <?= $pelanggan['nomor_wa'] ?>
                                </a>
                            </td>
                            
                            <td>
                                <div class="text-dark fw-bold" style="font-size: 0.85rem;"><i class="far fa-calendar-alt text-muted me-1"></i><?= formatDate($pelanggan['tanggal_daftar'], 'd M Y') ?></div>
                            </td>
                            
                            <td class="text-center">
                                <?php if ($stats['total_transaksi'] == 0): ?>
                                    <span class="badge-clean" style="background: #F3F4F6; color: #4B5563; border: 1px solid #E5E7EB;"><i class="fas fa-seedling me-1"></i> Baru</span>
                                <?php elseif ($stats['transaksi_terakhir'] && (strtotime($stats['transaksi_terakhir']) > (time() - 2592000))): ?>
                                    <span class="badge-clean" style="background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE;"><i class="fas fa-fire me-1"></i> Aktif</span>
                                <?php else: ?>
                                    <span class="badge-clean" style="background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A;"><i class="fas fa-snowflake me-1"></i> Pasif</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1 flex-nowrap">
                                    <a href="histori.php?id=<?= $pelanggan['id'] ?>" class="btn-action-icon embed" title="Histori & Notes">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $pelanggan['id'] ?>" class="btn-action-icon edit" title="Edit Profil">
                                        <i class="fas fa-user-edit"></i>
                                    </a>
                                    <button type="button" class="btn-action-icon delete" title="Hapus Data"
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
                <div class="p-3 border-top bg-light d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                        Menampilkan <?= $start_record ?> - <?= $end_record ?> dari <?= $total_records ?>
                    </div>
                    <div class="d-flex gap-1">
                        <?php echo generatePagination($current_page, $total_pages, $base_url); ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-user-times" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Pelanggan?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus data <strong id="deleteCustName" class="text-dark"></strong>? 
                    Ini akan menghapus riwayat transaksi secara permanen.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold rounded-pill" style="background: #EF4444; border: none;">Hapus</a>
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