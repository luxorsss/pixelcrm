<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../includes/init.php';

// Pastikan user sudah login (sesuaikan dengan sistem login Anda)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Mengambil data kupon dari database, digabung dengan nama produk (jika ada)
$query = "SELECT k.*, p.nama as nama_produk 
          FROM kupon k 
          LEFT JOIN produk p ON k.produk_id = p.id 
          ORDER BY k.id DESC";
$stmt = $conn->query($query); // Sesuaikan variabel koneksi ($conn atau $pdo) dengan config Anda
$kupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Manajemen Kupon Diskon</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Kupon</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
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
                                    <td><strong>
                                            <?= htmlspecialchars($k['kode_kupon']) ?>
                                        </strong></td>
                                    <td>
                                        <?= $k['tipe_diskon'] == 'persentase' ? $k['nilai_diskon'] . '%' : 'Rp ' . number_format($k['nilai_diskon'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <?= $k['nama_produk'] ? htmlspecialchars($k['nama_produk']) : '<span class="badge badge-info">Semua Produk</span>' ?>
                                    </td>
                                    <td>
                                        <?= $k['terpakai'] ?> /
                                        <?= $k['kuota'] ?>
                                    </td>
                                    <td>
                                        <small>
                                            Mulai:
                                            <?= date('d M Y H:i', strtotime($k['tgl_mulai'])) ?><br>
                                            Akhir:
                                            <?= date('d M Y H:i', strtotime($k['tgl_selesai'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($k['is_active'] == 1): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Non-aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?= $k['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="delete.php?id=<?= $k['id'] ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Yakin ingin menghapus kupon ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($kupons)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Belum ada data kupon.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>