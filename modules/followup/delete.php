<?php
require_once '../../includes/init.php';
require_once 'functions.php';

// Auth check
requireAuth();

$id = get('id');
if (!$id) {
    setMessage('ID followup message tidak ditemukan', 'error');
    redirect('index.php');
}

// Get followup data
$followup = getFollowupMessage($id);
if (!$followup) {
    setMessage('Followup message tidak ditemukan', 'error');
    redirect('index.php');
}

$produk_id = $followup['produk_id'];

// Process delete
if (isPost()) {
    $confirm = post('confirm');
    if ($confirm === 'ya') {
        if (deleteFollowupMessage($id)) {
            setMessage('Followup message berhasil dihapus', 'success');
        } else {
            setMessage('Gagal menghapus followup message', 'error');
        }
    }
    redirect("index.php?produk_id=$produk_id");
}

// If not POST, show confirmation page
$page_title = 'Hapus Followup Message';
require_once '../../includes/header.php';

// Get product info
$product = fetchRow("SELECT nama FROM produk WHERE id = ?", [$produk_id]);
?>

<div class="main-content">
    <?php require_once '../../includes/sidebar.php'; ?>
    
    <div class="content-area">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-4">
                    <a href="index.php?produk_id=<?= $produk_id ?>" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="page-title mb-0">Hapus Followup Message</h1>
                        <small class="text-muted">Konfirmasi penghapusan</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Penghapusan</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-warning"></i>
                            <strong>Perhatian!</strong> Tindakan ini tidak dapat dibatalkan.
                        </div>

                        <h6>Detail Followup Message yang akan dihapus:</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Produk:</strong></td>
                                <td><?= clean($product['nama']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nama Pesan:</strong></td>
                                <td><?= clean($followup['nama_pesan']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Urutan:</strong></td>
                                <td><?= $followup['urutan'] ?></td>
                            </tr>
                            <tr>
                                <td><strong>Delay:</strong></td>
                                <td><?= formatDelay($followup['delay_value'], $followup['delay_unit']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <?php if ($followup['status'] === 'aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Isi Pesan:</strong></td>
                                <td>
                                    <div class="border rounded p-2" style="background: #f8f9fa; max-height: 100px; overflow-y: auto;">
                                        <?= nl2br(clean(truncateText($followup['isi_pesan'], 200))) ?>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <hr>

                        <p class="mb-4">
                            <strong>Yakin ingin menghapus followup message ini?</strong><br>
                            <small class="text-muted">
                                Data yang sudah dihapus tidak bisa dikembalikan lagi.
                            </small>
                        </p>

                        <form method="POST" class="d-flex gap-2">
                            <button type="submit" name="confirm" value="ya" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Ya, Hapus
                            </button>
                            <a href="index.php?produk_id=<?= $produk_id ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?><?php
require_once '../../includes/init.php';
require_once 'functions.php';

// Auth check
requireAuth();

$id = get('id');
if (!$id) {
    setMessage('ID followup message tidak ditemukan', 'error');
    redirect('index.php');
}

// Get followup data
$followup = getFollowupMessage($id);
if (!$followup) {
    setMessage('Followup message tidak ditemukan', 'error');
    redirect('index.php');
}

$produk_id = $followup['produk_id'];

// Process delete
if (isPost()) {
    $confirm = post('confirm');
    if ($confirm === 'ya') {
        if (deleteFollowupMessage($id)) {
            setMessage('Followup message berhasil dihapus', 'success');
        } else {
            setMessage('Gagal menghapus followup message', 'error');
        }
    }
    redirect("index.php?produk_id=$produk_id");
}

// If not POST, show confirmation page
$page_title = 'Hapus Followup Message';
require_once '../../includes/header.php';

// Get product info
$product = fetchRow("SELECT nama FROM produk WHERE id = ?", [$produk_id]);
?>

<div class="main-content">
    <?php require_once '../../includes/sidebar.php'; ?>
    
    <div class="content-area">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-4">
                    <a href="index.php?produk_id=<?= $produk_id ?>" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="page-title mb-0">Hapus Followup Message</h1>
                        <small class="text-muted">Konfirmasi penghapusan</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Penghapusan</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-warning"></i>
                            <strong>Perhatian!</strong> Tindakan ini tidak dapat dibatalkan.
                        </div>

                        <h6>Detail Followup Message yang akan dihapus:</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Produk:</strong></td>
                                <td><?= clean($product['nama']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nama Pesan:</strong></td>
                                <td><?= clean($followup['nama_pesan']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Urutan:</strong></td>
                                <td><?= $followup['urutan'] ?></td>
                            </tr>
                            <tr>
                                <td><strong>Delay:</strong></td>
                                <td><?= formatDelay($followup['delay_value'], $followup['delay_unit']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <?php if ($followup['status'] === 'aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Isi Pesan:</strong></td>
                                <td>
                                    <div class="border rounded p-2" style="background: #f8f9fa; max-height: 100px; overflow-y: auto;">
                                        <?= nl2br(clean(truncateText($followup['isi_pesan'], 200))) ?>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <hr>

                        <p class="mb-4">
                            <strong>Yakin ingin menghapus followup message ini?</strong><br>
                            <small class="text-muted">
                                Data yang sudah dihapus tidak bisa dikembalikan lagi.
                            </small>
                        </p>

                        <form method="POST" class="d-flex gap-2">
                            <button type="submit" name="confirm" value="ya" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Ya, Hapus
                            </button>
                            <a href="index.php?produk_id=<?= $produk_id ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>