<?php
// ===============================
// LOGIC SECTION
// ===============================
$page_title = "Edit Pelanggan";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Validasi ID
$pelanggan_id = (int)get('id', 0);
if ($pelanggan_id <= 0) {
    setMessage('ID pelanggan tidak valid!', 'error');
    redirect('index.php');
}

$pelanggan = getPelangganById($pelanggan_id);
if (!$pelanggan) {
    setMessage('Pelanggan tidak ditemukan!', 'error');
    redirect('index.php');
}

$stats = getStatistikPelanggan($pelanggan_id);
$errors = [];

if (isPost()) {
    // Validate input
    $nama = clean(post('nama'));
    $nomor_wa = clean(post('nomor_wa'));
    
    if (empty($nama)) {
        $errors[] = 'Nama pelanggan harus diisi';
    }
    
    if (empty($nomor_wa)) {
        $errors[] = 'Nomor WA harus diisi';
    }
    
    if (empty($errors)) {
        $data = [
            'nama' => $nama,
            'nomor_wa' => $nomor_wa
        ];
        
        if (updatePelanggan($pelanggan_id, $data)) {
            setMessage('Data pelanggan berhasil diupdate!', 'success');
            redirect('index.php');
        } else {
            // Cek apakah nomor WA sudah digunakan pelanggan lain
            $existing = getPelangganByWA(normalizePhoneNumber($nomor_wa));
            if ($existing && $existing['id'] != $pelanggan_id) {
                $errors[] = 'Nomor WA sudah digunakan oleh pelanggan lain';
            } else {
                $errors[] = 'Gagal mengupdate data pelanggan. Silakan coba lagi.';
            }
        }
    }
} else {
    // Set default values from database
    $_POST = $pelanggan;
}

// ===============================
// PRESENTATION SECTION
// ===============================
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content dashboard-wrapper">
    <div class="form-container">
        
        <div class="dash-header mb-4">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Database Pelanggan
                </a>
                <h1 class="dash-title mt-2">Edit Pelanggan</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="histori.php?id=<?= $pelanggan_id ?>" class="btn btn-light text-dark fw-bold border" style="border-radius: 12px;">
                    <i class="fas fa-history me-1"></i> Histori Belanja
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: #EF4444;">
                <ul class="mb-0 text-danger fw-bold" style="font-size: 0.9rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel-editorial">
                    <h3 class="panel-title"><i class="fas fa-user-edit"></i> Form Update Data</h3>
                    
                    <form method="POST" novalidate>
                        <div class="mb-4">
                            <label for="nama" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-editorial" id="nama" name="nama" 
                                   value="<?= safeHtml(post('nama', '')) ?>" required maxlength="100">
                        </div>
                        
                        <div class="mb-4">
                            <label for="nomor_wa" class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                            <div class="input-group-editorial">
                                <span class="addon"><i class="fab fa-whatsapp text-success"></i></span>
                                <input type="text" class="form-control-editorial" id="nomor_wa" name="nomor_wa" 
                                       value="<?= safeHtml(post('nomor_wa', '')) ?>" 
                                       placeholder="628xxxxxxxxxx" required autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column flex-sm-row gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn-submit flex-grow-1 order-1 order-sm-2">
                                <i class="fas fa-save me-2"></i> Update Pelanggan
                            </button>
                            <a href="index.php" class="btn-cancel flex-grow-1 order-2 order-sm-1">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                
                <div class="panel-editorial p-4 mb-4">
                    <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-chart-pie text-primary"></i> Statistik Belanja</h3>
                    
                    <div class="row text-center g-2 mb-3">
                        <div class="col-5">
                            <div class="p-2 bg-light rounded-3 border">
                                <div class="fw-bold text-dark fs-5"><?= $stats['total_transaksi'] ?></div>
                                <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Transaksi</div>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="p-2 bg-light rounded-3 border" style="border-color: #A7F3D0 !important; background: #ECFDF5 !important;">
                                <div class="fw-bold text-success fs-5"><?= formatCurrency($stats['total_pembelian']) ?></div>
                                <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Total Nilai</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-column gap-2 mt-3 text-muted" style="font-size: 0.85rem;">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span>Terdaftar:</span>
                            <span class="fw-bold text-dark"><?= formatDate($pelanggan['tanggal_daftar'], 'd/m/Y') ?></span>
                        </div>
                        <div class="d-flex justify-content-between pt-1">
                            <span>Trx Terakhir:</span>
                            <span class="fw-bold text-dark"><?= $stats['transaksi_terakhir'] ? formatDate($stats['transaksi_terakhir'], 'd/m/Y') : '-' ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="panel-editorial p-4" style="background: #F9FAFB;">
                    <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-bolt text-warning"></i> Aksi Cepat</h3>
                    
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" target="_blank" class="btn btn-success fw-bold w-100 text-start" style="border-radius: 12px; padding: 0.85rem;">
                            <i class="fab fa-whatsapp me-2 fs-5 align-middle"></i> Chat WhatsApp
                        </a>
                        
                        <?php if ($stats['total_transaksi'] == 0): ?>
                            <a href="delete.php?id=<?= $pelanggan_id ?>" 
                               class="btn btn-outline-danger fw-bold w-100 text-start bg-white" 
                               style="border-radius: 12px; padding: 0.85rem;"
                               onclick="return confirm('Hapus pelanggan <?= safeHtml($pelanggan['nama']) ?> secara permanen?')">
                                <i class="fas fa-trash-alt me-2 fs-5 align-middle"></i> Hapus Pelanggan
                            </a>
                        <?php else: ?>
                            <div class="bg-white border rounded-3 p-3 text-center mt-2">
                                <i class="fas fa-lock text-muted mb-2 fs-4"></i>
                                <div class="text-muted fw-bold" style="font-size: 0.8rem; line-height: 1.4;">Tidak dapat dihapus karena memiliki riwayat transaksi.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>