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
<div class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Kelola Pelanggan</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <span class="breadcrumb-item active">Pelanggan</span>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Pelanggan
                </a>
                <a href="bulk.php" class="btn btn-success">
                    <i class="fas fa-upload me-2"></i>Bulk Import
                </a>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php displaySessionMessage(); ?>
        
        <!-- Search and Stats -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" 
                                           value="<?= safeHtml($search) ?>" 
                                           placeholder="Cari nama atau nomor WA..." autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search me-1"></i>Cari
                                </button>
                            </div>
                            <div class="col-md-2">
                                <?php if (!empty($search)): ?>
                                    <a href="index.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-times me-1"></i>Reset
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary w-100" disabled>
                                        <i class="fas fa-times me-1"></i>Reset
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <?php if (!empty($search)): ?>
                            <div class="mt-3">
                                <span class="text-muted">
                                    Hasil pencarian untuk: <strong>"<?= safeHtml($search) ?>"</strong>
                                    (<?= $total_records ?> hasil)
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="display-6 mb-2 text-primary"><?= number_format($total_records) ?></div>
                        <h6 class="text-muted">
                            <?= !empty($search) ? 'Hasil Pencarian' : 'Total Pelanggan' ?>
                        </h6>
                        <?php if (!empty($search)): ?>
                            <small class="text-<?= $total_records > 0 ? 'success' : 'danger' ?>">
                                <i class="fas fa-<?= $total_records > 0 ? 'check' : 'times' ?>-circle me-1"></i>
                                <?= $total_records > 0 ? 'Ditemukan' : 'Tidak ditemukan' ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pelanggan Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php if (!empty($search)): ?>
                            <i class="fas fa-search me-2"></i>
                            Hasil Pencarian: "<?= safeHtml($search) ?>"
                        <?php else: ?>
                            <i class="fas fa-users me-2"></i>
                            Daftar Pelanggan
                        <?php endif; ?>
                        <span class="badge bg-primary ms-2"><?= $total_records ?></span>
                    </h5>
                    
                    <?php if (!empty($search)): ?>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Lihat Semua
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($pelanggan_list)): ?>
                    <div class="text-center py-5">
                        <?php if (!empty($search)): ?>
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada hasil pencarian</h5>
                            <p class="text-muted mb-3">
                                Tidak ditemukan pelanggan dengan kata kunci: <strong>"<?= safeHtml($search) ?>"</strong>
                            </p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-1"></i>Lihat Semua Pelanggan
                                </a>
                                <a href="create.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Tambah Pelanggan
                                </a>
                            </div>
                        <?php else: ?>
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada pelanggan</h5>
                            <p class="text-muted">Mulai tambahkan pelanggan pertama atau import data secara bulk.</p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="create.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Tambah Pelanggan
                                </a>
                                <a href="bulk.php" class="btn btn-success">
                                    <i class="fas fa-upload me-2"></i>Bulk Import
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Nama</th>
                                    <th width="140">Nomor WA</th>
                                    <th width="120">Tanggal Daftar</th>
                                    <th width="80">Status</th>
                                    <th width="160">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pelanggan_list as $pelanggan): ?>
                                <?php $stats = getStatistikPelanggan($pelanggan['id']); ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= $pelanggan['id'] ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= safeHtml($pelanggan['nama']) ?></strong>
                                            <?php if ($stats['total_transaksi'] > 0): ?>
                                                <br><small class="text-success">
                                                    <i class="fas fa-shopping-cart me-1"></i>
                                                    <?= $stats['total_transaksi'] ?> transaksi
                                                    <?php if ($stats['total_pembelian'] > 0): ?>
                                                        • <?= formatCurrency($stats['total_pembelian']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" 
                                           target="_blank" class="text-success text-decoration-none">
                                            <i class="fab fa-whatsapp me-1"></i>
                                            <?= $pelanggan['nomor_wa'] ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span title="<?= formatDate($pelanggan['tanggal_daftar'], 'd/m/Y H:i') ?>">
                                            <?= formatDate($pelanggan['tanggal_daftar']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($stats['total_transaksi'] == 0): ?>
                                            <span class="badge bg-secondary">Baru</span>
                                        <?php elseif ($stats['transaksi_terakhir'] && (strtotime($stats['transaksi_terakhir']) > (time() - 2592000))): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Lama</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="histori.php?id=<?= $pelanggan['id'] ?>" 
                                               class="btn btn-info" title="Histori Pembelian">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $pelanggan['id'] ?>" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $pelanggan['id'] ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Hapus pelanggan <?= safeHtml($pelanggan['nama']) ?>?\n\nSemua transaksi terkait akan ikut dihapus!')"
                                               title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Menampilkan <?= $start_record ?> - <?= $end_record ?> dari <?= $total_records ?> pelanggan
                            </div>
                            
                            <?php 
                            // Gunakan base_url yang sudah dibersihkan
                            echo generatePagination($current_page, $total_pages, $base_url);
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>