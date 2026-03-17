<?php
$page_title = "Kelola OneSender";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/whatsapp_helper.php';

// Ambil semua config OneSender
$configs = fetchAll("SELECT * FROM onesender_config ORDER BY account_name");

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Kelola OneSender</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <span class="breadcrumb-item active">OneSender</span>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Account
                </a>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php 
        $msg = getMessage();
        if ($msg): 
        ?>
            <div class="alert alert-<?= $msg[1] === 'error' ? 'danger' : $msg[1] ?> alert-dismissible fade show" role="alert">
                <?= clean($msg[0]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h4 text-primary"><?= count($configs) ?></div>
                        <small class="text-muted">Total Accounts</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <?php 
                        $active_count = 0;
                        foreach ($configs as $config) {
                            if (!empty($config['api_key']) && !empty($config['api_url'])) {
                                $active_count++;
                            }
                        }
                        ?>
                        <div class="h4 text-success"><?= $active_count ?></div>
                        <small class="text-muted">Active Accounts</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <?php 
                        $stats = getWhatsAppStats(7);
                        ?>
                        <div class="h4 text-info"><?= $stats['total_sent'] ?></div>
                        <small class="text-muted">Pesan 7 Hari</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h4 text-success"><?= $stats['success'] ?></div>
                        <small class="text-muted">Berhasil Terkirim</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- OneSender Accounts Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-server me-2"></i>
                    Daftar OneSender Accounts
                    <span class="badge bg-primary ms-2"><?= count($configs) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($configs)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-server fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada OneSender Account</h5>
                        <p class="text-muted">Mulai dengan menambahkan account OneSender pertama.</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Account
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Account Name</th>
                                    <th>API URL</th>
                                    <th>API Key Status</th>
                                    <th width="180">Status Koneksi</th>
                                    <th width="200">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($configs as $config): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2 bg-primary rounded-circle text-white">
                                                <?= strtoupper(substr($config['account_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= clean($config['account_name']) ?></strong>
                                                <?php if ($config['account_name'] === 'default'): ?>
                                                    <br><small class="text-muted">Default Account</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code class="small"><?= clean($config['api_url']) ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($config['api_key'])): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>
                                                SET (<?= strlen($config['api_key']) ?> chars)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>
                                                NOT SET
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div id="status-<?= $config['id'] ?>">
                                            <button class="btn btn-outline-secondary btn-sm test-connection" 
                                                    data-account="<?= $config['account_name'] ?>" 
                                                    data-id="<?= $config['id'] ?>">
                                                <i class="fas fa-wifi me-1"></i>Test Koneksi
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit.php?id=<?= $config['id'] ?>" 
                                               class="btn btn-outline-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="test.php?account=<?= urlencode($config['account_name']) ?>" 
                                               class="btn btn-outline-info" title="Test Manual">
                                                <i class="fas fa-vial"></i>
                                            </a>
                                            <?php if ($config['account_name'] !== 'default'): ?>
                                                <a href="delete.php?id=<?= $config['id'] ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Hapus account <?= $config['account_name'] ?>?')"
                                                   title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
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

        <!-- Recent Activity -->
        <?php if (!empty($stats['recent_activity'])): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Aktivitas Terbaru (<?= count($stats['recent_activity']) ?> terakhir)
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Account</th>
                                <th>Tujuan</th>
                                <th>Pesan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_activity'] as $activity): ?>
                            <tr>
                                <td><small><?= date('d/m/Y H:i', strtotime($activity['timestamp'])) ?></small></td>
                                <td><span class="badge bg-secondary"><?= $activity['account'] ?></span></td>
                                <td><code class="small"><?= $activity['to'] ?></code></td>
                                <td><small><?= truncateText($activity['message'], 50) ?></small></td>
                                <td>
                                    <?php if ($activity['status'] === 'success'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger" title="<?= $activity['error'] ?>"><i class="fas fa-times"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test connection buttons
    document.querySelectorAll('.test-connection').forEach(function(button) {
        button.addEventListener('click', function() {
            const account = this.getAttribute('data-account');
            const id = this.getAttribute('data-id');
            const statusDiv = document.getElementById('status-' + id);
            
            // Show loading
            statusDiv.innerHTML = '<span class="badge bg-warning"><i class="fas fa-spinner fa-spin me-1"></i>Testing...</span>';
            
            // Test connection via AJAX
            fetch('test_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'account=' + encodeURIComponent(account)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.innerHTML = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Online</span>';
                } else {
                    statusDiv.innerHTML = '<span class="badge bg-danger" title="' + data.error + '"><i class="fas fa-times me-1"></i>Error</span>';
                }
                
                // Reset ke tombol test setelah 5 detik
                setTimeout(() => {
                    statusDiv.innerHTML = '<button class="btn btn-outline-secondary btn-sm test-connection" data-account="' + account + '" data-id="' + id + '"><i class="fas fa-wifi me-1"></i>Test Koneksi</button>';
                    // Re-attach event listener
                    statusDiv.querySelector('.test-connection').addEventListener('click', arguments.callee);
                }, 5000);
            })
            .catch(error => {
                statusDiv.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Error</span>';
                console.error('Test error:', error);
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>