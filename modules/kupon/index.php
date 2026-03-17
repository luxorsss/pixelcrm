<?php
require_once __DIR__ . '/../../includes/init.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'login.php');
}

$page_title = "Kelola Kupon";

// Mengambil data kupon dari database memakai fungsi bawaan sistem (fetchAll)
$query = "SELECT k.*, p.nama as nama_produk 
          FROM kupon k 
          LEFT JOIN produk p ON k.produk_id = p.id 
          ORDER BY k.id DESC";
          
$kupons = fetchAll($query);
$total_records = count($kupons);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Kelola Kupon</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Kupon</li>
                    </ol>
                </nav>
            </div>
            <div class="text-muted">
                <i class="fas fa-calendar me-1"></i><?= date('d F Y') ?>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <?php if ($msg = getMessage()): ?>
            <div class="alert alert-<?= $msg[1] ?> alert-dismissible fade show">
                <?= $msg[0] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-ticket-alt me-2"></i>Daftar Kupon</h2>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tambah Kupon
            </a>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Daftar Kupon (<?= $total_records ?> kupon)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($kupons)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada kupon</h5>
                        <p class="text-muted">Klik tombol "Tambah Kupon" untuk membuat promo pertama Anda.</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Kupon Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode Kupon</th>
                                    <th>Diskon</th>
                                    <th>Produk</th>
                                    <th>Kuota</th>
                                    <th>Masa Berlaku</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kupons as $k): ?>
                                <tr>
                                    <td class="fw-bold text-uppercase"><?= clean($k['kode_kupon']) ?></td>
                                    <td>
                                        <?php if($k['tipe_diskon'] == 'persentase'): ?>
                                            <span class="badge bg-info"><?= $k['nilai_diskon'] ?>%</span>
                                            <?php if($k['max_potongan'] > 0) echo '<br><small class="text-muted">Max: '.formatCurrency($k['max_potongan']).'</small>'; ?>
                                        <?php else: ?>
                                            <span class="text-success fw-bold"><?= formatCurrency($k['nilai_diskon']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $k['nama_produk'] ? '<i class="fas fa-box text-muted"></i> '.clean($k['nama_produk']) : '<span class="badge bg-secondary">Semua Produk</span>' ?>
                                    </td>
                                    <td>
                                        <?= $k['terpakai'] ?> / <strong><?= $k['kuota'] ?></strong>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="fas fa-play text-success"></i> <?= formatDate($k['tgl_mulai'], 'd M Y H:i') ?><br>
                                            <i class="fas fa-stop text-danger"></i> <?= formatDate($k['tgl_selesai'], 'd M Y H:i') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?= $k['is_active'] == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Non-aktif</span>' ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit.php?id=<?= $k['id'] ?>" class="btn btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?= $k['id'] ?>" class="btn btn-outline-danger" title="Hapus" onclick="return confirm('Yakin ingin menghapus kupon <?= $k['kode_kupon'] ?>?')"><i class="fas fa-trash"></i></a>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>