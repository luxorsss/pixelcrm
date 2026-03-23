<?php
$page_title = "Detail Transaksi";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$id = (int)get('id');
if (!$id) {
    setMessage('ID transaksi tidak valid', 'error');
    redirect('index.php');
}

$transaksi = getTransaksiById($id);
if (!$transaksi) {
    setMessage('Transaksi tidak ditemukan', 'error');
    redirect('index.php');
}

$detail_items = getDetailTransaksi($id);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Detail Transaksi #<?= $transaksi['id'] ?></h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="<?= BASE_URL ?>modules/transaksi/" class="breadcrumb-item text-decoration-none">Transaksi</a>
                    <span class="breadcrumb-item active">Detail #<?= $transaksi['id'] ?></span>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
                <?php if ($transaksi['status'] === 'pending'): ?>
                    <a href="edit.php?id=<?= $transaksi['id'] ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content-area">
        <?php displaySessionMessage(); ?>
        
        <div class="row">
            <!-- Informasi Transaksi -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="120"><strong>ID Transaksi:</strong></td>
                                        <td>#<?= $transaksi['id'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pelanggan:</strong></td>
                                        <td><?= safeHtml($transaksi['nama_pelanggan']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>No. WhatsApp:</strong></td>
                                        <td>
                                            <a href="<?= whatsappLink($transaksi['nomor_wa']) ?>" target="_blank" class="text-success">
                                                <i class="fab fa-whatsapp me-1"></i><?= $transaksi['nomor_wa'] ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>
                                            <?php if (!empty($transaksi['email'])): ?>
                                                <a href="mailto:<?= safeHtml($transaksi['email']) ?>" class="text-primary text-decoration-none">
                                                    <i class="fas fa-envelope me-1"></i><?= safeHtml($transaksi['email']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fas fa-minus"></i> Belum diisi</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Harga:</strong></td>
                                        <td><h5 class="text-success mb-0"><?= formatCurrency($transaksi['total_harga']) ?></h5></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="120"><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge bg-<?= $transaksi['status'] === 'selesai' ? 'success' : ($transaksi['status'] === 'pending' ? 'warning' : ($transaksi['status'] === 'diproses' ? 'info' : 'danger')) ?> fs-6">
                                                <?= ucfirst($transaksi['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal:</strong></td>
                                        <td><?= formatDate($transaksi['tanggal_transaksi'], 'd M Y') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Waktu:</strong></td>
                                        <td><?= formatDate($transaksi['tanggal_transaksi'], 'H:i') ?> WIB</td>
                                    </tr>
                                    <?php if ($transaksi['status'] === 'selesai' && !empty($transaksi['waktu_selesai'])): ?>
                                    <tr>
                                        <td><strong>Waktu Selesai:</strong></td>
                                        <td class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?= formatDate($transaksi['waktu_selesai'], 'd M Y H:i') ?> WIB
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Jumlah Item:</strong></td>
                                        <td><span class="badge bg-info"><?= count($detail_items) ?> item</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Items -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Detail Item</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($detail_items)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada item dalam transaksi ini</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Produk</th>
                                            <th>Deskripsi</th>
                                            <th width="120">Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detail_items as $index => $item): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><strong><?= safeHtml($item['nama_produk']) ?></strong></td>
                                            <td><?= safeHtml(truncateText($item['deskripsi'], 100)) ?></td>
                                            <td><strong class="text-success"><?= formatCurrency($item['harga']) ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="3" class="text-end">Total:</th>
                                            <th class="text-success"><?= formatCurrency($transaksi['total_harga']) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Aksi Cepat</h5>
                    </div>
                    <div class="card-body">
                        <!-- Status Actions -->
                        <?php if ($transaksi['status'] === 'pending'): ?>
                            <div class="d-grid gap-2 mb-3">
                                <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=diproses&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   class="btn btn-info">
                                    <i class="fas fa-clock me-2"></i>Proses Transaksi
                                </a>
                                <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=selesai&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Selesaikan Transaksi
                                </a>
                                <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=batal&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Yakin ingin membatalkan transaksi ini?')">
                                    <i class="fas fa-times me-2"></i>Batalkan Transaksi
                                </a>
                            </div>
                        <?php elseif ($transaksi['status'] === 'diproses'): ?>
                            <div class="d-grid gap-2 mb-3">
                                <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=selesai&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Selesaikan Transaksi
                                </a>
                                <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=batal&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Yakin ingin membatalkan transaksi ini?')">
                                    <i class="fas fa-times me-2"></i>Batalkan Transaksi
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Other Actions -->
                        <div class="d-grid gap-2">
                            <a href="<?= whatsappLink($transaksi['nomor_wa'], 'Halo ' . $transaksi['nama_pelanggan'] . ', terima kasih atas transaksi Anda #' . $transaksi['id']) ?>" 
                               target="_blank" class="btn btn-success">
                                <i class="fab fa-whatsapp me-2"></i>Hubungi via WhatsApp
                            </a>
                            
                            <?php if ($transaksi['status'] === 'pending'): ?>
                                <a href="edit.php?id=<?= $transaksi['id'] ?>" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Edit Transaksi
                                </a>
                            <?php endif; ?>
                            
                            <a href="delete.php?id=<?= $transaksi['id'] ?>" 
                               class="btn btn-outline-danger"
                               onclick="return confirm('Yakin ingin menghapus transaksi #<?= $transaksi['id'] ?>?\n\nData yang dihapus tidak dapat dikembalikan.')">
                                <i class="fas fa-trash me-2"></i>Hapus Transaksi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>