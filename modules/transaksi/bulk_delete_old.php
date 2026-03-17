<?php
/**
 * Bulk Delete Old Pending Transactions
 * Menghapus transaksi pending yang lebih dari 3 bulan
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Pastikan user sudah login
requireAuth();

// Handle POST request untuk hapus
if (isPost()) {
    $confirm = post('confirm');
    
    if ($confirm === 'yes') {
        try {
            // Start transaction untuk memastikan konsistensi
            mysqli_begin_transaction(db());
            
            // 1. Hapus detail transaksi terlebih dulu (foreign key constraint)
            $delete_details_sql = "DELETE dt FROM detail_transaksi dt
                                  JOIN transaksi t ON dt.transaksi_id = t.id
                                  WHERE t.status = 'pending' 
                                  AND t.tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            
            $details_result = mysqli_query(db(), $delete_details_sql);
            if (!$details_result) {
                throw new Exception("Gagal menghapus detail transaksi: " . mysqli_error(db()));
            }
            
            $details_deleted = mysqli_affected_rows(db());
            
            // 2. Hapus transaksi utama
            $delete_transaksi_sql = "DELETE FROM transaksi 
                                     WHERE status = 'pending' 
                                     AND tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            
            $transaksi_result = mysqli_query(db(), $delete_transaksi_sql);
            if (!$transaksi_result) {
                throw new Exception("Gagal menghapus transaksi: " . mysqli_error(db()));
            }
            
            $transaksi_deleted = mysqli_affected_rows(db());
            
            // Commit transaction
            mysqli_commit(db());
            
            if ($transaksi_deleted > 0) {
                setMessage("Berhasil menghapus {$transaksi_deleted} transaksi dan {$details_deleted} detail transaksi.", 'success');
            } else {
                setMessage("Tidak ada transaksi pending yang lebih dari 3 bulan.", 'info');
            }
            
        } catch (Exception $e) {
            // Rollback pada error
            mysqli_rollback(db());
            setMessage("System error: " . $e->getMessage(), 'error');
        }
        
        redirect('index.php');
    } else {
        setMessage("Konfirmasi diperlukan untuk menghapus transaksi.", 'error');
        redirect('bulk_delete_old.php');
    }
}

// Get count transaksi yang akan dihapus
$count_sql = "SELECT COUNT(*) as total 
              FROM transaksi t
              JOIN pelanggan p ON t.pelanggan_id = p.id
              WHERE t.status = 'pending' 
              AND t.tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";

$count_result = mysqli_query(db(), $count_sql);
$count_data = mysqli_fetch_assoc($count_result);
$total_to_delete = $count_data['total'];

// Get sample data untuk ditampilkan
$sample_sql = "SELECT t.id, t.total_harga, t.tanggal_transaksi, p.nama as nama_pelanggan
               FROM transaksi t
               JOIN pelanggan p ON t.pelanggan_id = p.id
               WHERE t.status = 'pending' 
               AND t.tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)
               ORDER BY t.tanggal_transaksi ASC
               LIMIT 10";

$sample_result = mysqli_query(db(), $sample_sql);
$sample_data = [];
while ($row = mysqli_fetch_assoc($sample_result)) {
    $sample_data[] = $row;
}

$page_title = "Hapus Transaksi Pending Lama";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Hapus Transaksi Pending Lama</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="<?= BASE_URL ?>modules/transaksi/" class="breadcrumb-item text-decoration-none">Transaksi</a>
                    <span class="breadcrumb-item active">Hapus Pending Lama</span>
                </nav>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php displaySessionMessage(); ?>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Hapus Transaksi Pending Lama
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($total_to_delete > 0): ?>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-info-circle me-2"></i>Informasi</h6>
                                <p class="mb-0">
                                    Ditemukan <strong><?= number_format($total_to_delete) ?> transaksi pending</strong> 
                                    yang lebih dari 3 bulan dan akan dihapus.
                                </p>
                            </div>
                            
                            <!-- Sample Data -->
                            <?php if (!empty($sample_data)): ?>
                                <h6 class="mb-3">Preview Transaksi yang Akan Dihapus:</h6>
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Pelanggan</th>
                                                <th>Total</th>
                                                <th>Tanggal</th>
                                                <th>Umur</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sample_data as $item): ?>
                                            <tr>
                                                <td>#<?= $item['id'] ?></td>
                                                <td><?= safeHtml($item['nama_pelanggan']) ?></td>
                                                <td><?= formatCurrency($item['total_harga']) ?></td>
                                                <td><?= formatDate($item['tanggal_transaksi']) ?></td>
                                                <td>
                                                    <?php
                                                    $days = (strtotime('now') - strtotime($item['tanggal_transaksi'])) / (60*60*24);
                                                    echo floor($days) . ' hari';
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php if ($total_to_delete > 10): ?>
                                    <p class="text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Menampilkan 10 dari <?= number_format($total_to_delete) ?> transaksi.
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Konfirmasi -->
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Peringatan!</h6>
                                <ul class="mb-0">
                                    <li>Aksi ini <strong>tidak dapat dibatalkan</strong></li>
                                    <li>Semua transaksi pending > 3 bulan akan dihapus permanent</li>
                                    <li>Detail transaksi juga akan ikut terhapus</li>
                                </ul>
                            </div>
                            
                            <form method="POST" class="text-center" id="deleteForm">
                                <div class="form-check d-inline-block mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                                    <label class="form-check-label" for="confirmCheck">
                                        Saya memahami risiko dan ingin melanjutkan
                                    </label>
                                </div>
                                <br>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="submit" name="confirm" value="yes" 
                                            class="btn btn-danger" id="deleteBtn" disabled>
                                        <i class="fas fa-trash me-2"></i>
                                        Hapus <?= number_format($total_to_delete) ?> Transaksi
                                    </button>
                                    <button type="button" class="btn btn-warning" id="deleteAjaxBtn" disabled>
                                        <i class="fas fa-bolt me-2"></i>
                                        Hapus (Cepat)
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">Batal</a>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    "Hapus (Cepat)" menggunakan AJAX tanpa reload halaman
                                </small>
                            </form>
                            
                        <?php else: ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>Tidak Ada Transaksi Pending Lama</h5>
                                <p class="mb-0">
                                    Semua transaksi pending masih dalam periode 3 bulan terakhir.
                                </p>
                                <a href="index.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Transaksi
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enable/disable buttons berdasarkan checkbox
const confirmCheck = document.getElementById('confirmCheck');
const deleteBtn = document.getElementById('deleteBtn');
const deleteAjaxBtn = document.getElementById('deleteAjaxBtn');

if (confirmCheck && deleteBtn) {
    confirmCheck.addEventListener('change', function() {
        const isChecked = this.checked;
        deleteBtn.disabled = !isChecked;
        
        if (deleteAjaxBtn) {
            deleteAjaxBtn.disabled = !isChecked;
        }
        
        // Update button appearance
        if (isChecked) {
            deleteBtn.classList.remove('btn-secondary');
            deleteBtn.classList.add('btn-danger');
            if (deleteAjaxBtn) {
                deleteAjaxBtn.classList.remove('btn-secondary');
                deleteAjaxBtn.classList.add('btn-warning');
            }
        } else {
            deleteBtn.classList.remove('btn-danger');
            deleteBtn.classList.add('btn-secondary');
            if (deleteAjaxBtn) {
                deleteAjaxBtn.classList.remove('btn-warning');
                deleteAjaxBtn.classList.add('btn-secondary');
            }
        }
    });
    
    // Form submit handler (tombol hapus biasa)
    const form = document.getElementById('deleteForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!confirmCheck.checked) {
                e.preventDefault();
                alert('Harap centang checkbox konfirmasi terlebih dulu.');
                return false;
            }
            
            const totalCount = deleteBtn.textContent.match(/\d+/)[0];
            const confirmed = confirm(
                `PERINGATAN!\n\n` +
                `Anda akan menghapus ${totalCount} transaksi pending yang lebih dari 3 bulan.\n\n` +
                `Aksi ini TIDAK DAPAT DIBATALKAN!\n\n` +
                `Apakah Anda yakin ingin melanjutkan?`
            );
            
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
            deleteBtn.disabled = true;
            
            // Disable semua form elements
            const formElements = form.querySelectorAll('input, button');
            formElements.forEach(element => element.disabled = true);
        });
    }
    
    // AJAX delete handler (tombol hapus cepat)
    if (deleteAjaxBtn) {
        deleteAjaxBtn.addEventListener('click', function() {
            if (!confirmCheck.checked) {
                alert('Harap centang checkbox konfirmasi terlebih dulu.');
                return;
            }
            
            const totalCount = deleteBtn.textContent.match(/\d+/)[0];
            const confirmed = confirm(
                `PERINGATAN!\n\n` +
                `Anda akan menghapus ${totalCount} transaksi pending yang lebih dari 3 bulan.\n\n` +
                `Aksi ini TIDAK DAPAT DIBATALKAN!\n\n` +
                `Apakah Anda yakin ingin melanjutkan?`
            );
            
            if (!confirmed) return;
            
            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
            this.disabled = true;
            deleteBtn.disabled = true;
            
            // AJAX request
            fetch('bulk_delete_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'confirm=yes'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    document.querySelector('.content-area').insertBefore(alertDiv, document.querySelector('.row'));
                    
                    // Redirect setelah 2 detik
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                // Show error message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error: ${error.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.querySelector('.content-area').insertBefore(alertDiv, document.querySelector('.row'));
                
                // Restore button state
                this.innerHTML = originalText;
                this.disabled = false;
                deleteBtn.disabled = false;
            });
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>