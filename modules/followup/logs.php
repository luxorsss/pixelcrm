<?php
$page_title = 'Followup Logs';
require_once '../../includes/header.php';
require_once 'functions.php';

// Filters
$status_filter = get('status', '');
$produk_filter = get('produk_id', '');
$date_filter = get('date', '');
$page = max(1, (int) get('page', 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build WHERE conditions
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "fl.status = ?";
    $params[] = $status_filter;
}

if ($produk_filter) {
    $where_conditions[] = "pr.id = ?";
    $params[] = $produk_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(COALESCE(fl.waktu_kirim, fl.created_at)) = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count (ENHANCED)
$total_query = "
    SELECT COUNT(fl.id) as total
    FROM followup_logs fl
    JOIN followup_messages fm ON fl.followup_message_id = fm.id
    JOIN pelanggan p ON fl.pelanggan_id = p.id
    JOIN transaksi t ON fl.transaksi_id = t.id
    $where_clause
";

$total_result = fetchRow($total_query, $params);
$total_records = $total_result['total'];
$total_pages = ceil($total_records / $per_page);

// Get logs with pagination (ENHANCED SCHEMA)
$logs_query = "
    SELECT 
        fl.*,
        fm.nama_pesan, fm.urutan, fm.tipe_pesan,
        p.nama as pelanggan_nama, p.nomor_wa,
        t.total_harga, t.status as transaksi_status,
        (SELECT GROUP_CONCAT(pr.nama SEPARATOR ', ') 
         FROM detail_transaksi dt2 
         JOIN produk pr ON dt2.produk_id = pr.id 
         WHERE dt2.transaksi_id = t.id) as produk_list
    FROM followup_logs fl
    JOIN followup_messages fm ON fl.followup_message_id = fm.id
    JOIN pelanggan p ON fl.pelanggan_id = p.id
    JOIN transaksi t ON fl.transaksi_id = t.id
    $where_clause
    ORDER BY COALESCE(fl.waktu_kirim, fl.jadwal_kirim, fl.created_at) DESC
    LIMIT $per_page OFFSET $offset
";

$logs = fetchAll($logs_query, $params);

// Get products for filter
$products = fetchAll("SELECT id, nama FROM produk ORDER BY nama ASC");
?>

<div class="main-content">
    <?php require_once '../../includes/sidebar.php'; ?>
    
    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">Followup Logs</h1>
                <p class="text-muted">Riwayat pengiriman followup messages dengan step tracking</p>
            </div>
            <div class="d-flex gap-2">
                <a href="monitor.php" class="btn btn-info">
                    <i class="fas fa-chart-line"></i> Monitor
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="waiting" <?= $status_filter === 'waiting' ? 'selected' : '' ?>>Waiting</option>
                            <option value="terkirim" <?= $status_filter === 'terkirim' ? 'selected' : '' ?>>Terkirim</option>
                            <option value="gagal" <?= $status_filter === 'gagal' ? 'selected' : '' ?>>Gagal</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="skipped" <?= $status_filter === 'skipped' ? 'selected' : '' ?>>Skipped</option>
                            <option value="skip" <?= $status_filter === 'skip' ? 'selected' : '' ?>>Skip</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Produk</label>
                        <select name="produk_id" class="form-select">
                            <option value="">-- Semua Produk --</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>" <?= $produk_filter == $product['id'] ? 'selected' : '' ?>>
                                <?= clean($product['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date" class="form-control" value="<?= clean($date_filter) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics (ENHANCED) -->
        <div class="row mb-4">
            <?php 
            $status_counts = fetchAll("
                SELECT 
                    fl.status,
                    COUNT(*) as count
                FROM followup_logs fl
                JOIN followup_messages fm ON fl.followup_message_id = fm.id
                JOIN pelanggan p ON fl.pelanggan_id = p.id
                JOIN transaksi t ON fl.transaksi_id = t.id
                $where_clause
                GROUP BY fl.status
            ", $params);
            
            $counts = [
                'pending' => 0,
                'waiting' => 0,
                'terkirim' => 0,
                'gagal' => 0,
                'completed' => 0,
                'skipped' => 0,
                'skip' => 0
            ];
            
            foreach ($status_counts as $count) {
                $counts[$count['status']] = $count['count'];
            }
            ?>
            
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-warning"><?= $counts['pending'] ?></h4>
                        <small>Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-info"><?= $counts['waiting'] ?></h4>
                        <small>Waiting</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-success"><?= $counts['terkirim'] ?></h4>
                        <small>Terkirim</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-danger"><?= $counts['gagal'] ?></h4>
                        <small>Gagal</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-primary"><?= $counts['completed'] ?></h4>
                        <small>Completed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-secondary"><?= $counts['skipped'] ?></h4>
                        <small>Skipped</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-muted"><?= $counts['skip'] ?></h4>
                        <small>Skip</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-primary"><?= $total_records ?></h4>
                        <small>Total</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Followup Logs 
                    <span class="badge bg-secondary"><?= $total_records ?> total</span>
                </h5>
            </div>
            
            <?php if ($logs): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pesan & Step</th>
                            <th>Pelanggan</th>
                            <th>Produk</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <?php if ($log['waktu_kirim']): ?>
                                    <strong class="text-success"><?= formatDate($log['waktu_kirim'], 'd/m/Y H:i') ?></strong><br>
                                    <small class="badge bg-success">Terkirim</small>
                                <?php elseif ($log['status'] === 'gagal'): ?>
                                    <span class="text-danger"><?= formatDate($log['created_at'], 'd/m/Y H:i') ?></span><br>
                                    <small class="badge bg-danger">Gagal</small>
                                <?php elseif ($log['jadwal_kirim']): ?>
                                    <?php 
                                    $is_overdue = strtotime($log['jadwal_kirim']) < time();
                                    $time_class = $is_overdue ? 'text-warning' : 'text-info';
                                    ?>
                                    <span class="<?= $time_class ?>"><?= formatDate($log['jadwal_kirim'], 'd/m/Y H:i') ?></span><br>
                                    <small class="badge bg-<?= $is_overdue ? 'warning' : 'info' ?>">
                                        <?= $is_overdue ? 'Terlambat' : 'Dijadwalkan' ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted"><?= formatDate($log['created_at'], 'd/m/Y H:i') ?></span><br>
                                    <small class="badge bg-secondary">Dibuat</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= clean($log['nama_pesan']) ?></strong>
                                <br>
                                <small class="text-muted">
                                    Step <?= $log['current_step'] ?: $log['urutan'] ?> | 
                                    <?= $log['tipe_pesan'] === 'pesan_gambar' ? 'Image' : 'Text' ?>
                                </small>
                                <?php if ($log['last_message_sent']): ?>
                                    <br><small class="text-info">Last: <?= formatDate($log['last_message_sent'], 'd/m H:i') ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= clean($log['pelanggan_nama']) ?><br>
                                <small class="text-muted"><?= clean($log['nomor_wa']) ?></small>
                            </td>
                            <td>
                                <?= clean($log['produk_list'] ?: 'Unknown Product') ?><br>
                                <small class="text-muted"><?= formatCurrency($log['total_harga']) ?></small>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => 'warning',
                                    'waiting' => 'info', 
                                    'terkirim' => 'success',
                                    'gagal' => 'danger',
                                    'completed' => 'primary',
                                    'skipped' => 'secondary',
                                    'skip' => 'secondary'
                                ];
                                $badge_class = $status_badges[$log['status']] ?? 'secondary';
                                $status_labels = [
                                    'pending' => 'Pending',
                                    'waiting' => 'Waiting',
                                    'terkirim' => 'Terkirim', 
                                    'gagal' => 'Gagal',
                                    'completed' => 'Completed',
                                    'skipped' => 'Skipped',
                                    'skip' => 'Skip'
                                ];
                                $status_label = $status_labels[$log['status']] ?? ucfirst($log['status']);
                                ?>
                                <span class="badge bg-<?= $badge_class ?>"><?= $status_label ?></span>
                                
                                <?php if ($log['transaksi_status'] !== 'pending'): ?>
                                    <br><small class="text-warning">Transaksi: <?= $log['transaksi_status'] ?></small>
                                <?php endif; ?>
                                
                                <?php if ($log['status'] === 'completed'): ?>
                                    <br><small class="text-success">✓ All messages sent</small>
                                <?php elseif ($log['status'] === 'skipped'): ?>
                                    <br><small class="text-muted">↷ Auto-skipped</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" 
                                            onclick="showLogDetail(<?= htmlspecialchars(json_encode($log)) ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($log['status'] === 'pending' || $log['status'] === 'gagal'): ?>
                                    <form method="POST" action="sender.php" class="d-inline">
                                        <input type="hidden" name="action" value="send_manual">
                                        <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                                        <button type="submit" class="btn btn-outline-primary" 
                                                onclick="return confirm('Kirim pesan ini sekarang?')" 
                                                title="Kirim Manual">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                    <?php elseif ($log['status'] === 'completed'): ?>
                                    <small class="text-muted">Complete</small>
                                    <?php elseif ($log['status'] === 'skipped'): ?>
                                    <small class="text-muted">Skipped</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Page navigation">
                    <?php
                    $url_params = http_build_query([
                        'status' => $status_filter,
                        'produk_id' => $produk_filter,
                        'date' => $date_filter
                    ]);
                    $base_url = 'logs.php?' . $url_params;
                    ?>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= $base_url ?>&page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $base_url ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= $base_url ?>&page=<?= $page + 1 ?>">Next</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center mt-2">
                    <small class="text-muted">
                        Showing <?= $offset + 1 ?>-<?= min($offset + $per_page, $total_records) ?> of <?= $total_records ?> records
                    </small>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>No logs found</h5>
                <p class="text-muted">Tidak ada logs yang sesuai dengan filter yang dipilih</p>
                <a href="logs.php" class="btn btn-outline-primary">Reset Filter</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logDetailContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function showLogDetail(logData) {
    const formatDate = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleString('id-ID');
    };
    
    const getStatusBadge = (status) => {
        const badges = {
            'pending': 'warning',
            'waiting': 'info',
            'terkirim': 'success',
            'gagal': 'danger',
            'completed': 'primary',
            'skipped': 'secondary',
            'skip': 'secondary'
        };
        const badgeClass = badges[status] || 'secondary';
        const labels = {
            'pending': 'Pending',
            'waiting': 'Waiting',
            'terkirim': 'Terkirim',
            'gagal': 'Gagal',
            'completed': 'Completed',
            'skipped': 'Skipped',
            'skip': 'Skip'
        };
        const label = labels[status] || status.charAt(0).toUpperCase() + status.slice(1);
        return `<span class="badge bg-${badgeClass}">${label}</span>`;
    };
    
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Informasi Pesan</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Nama Pesan:</strong></td>
                        <td>${logData.nama_pesan}</td>
                    </tr>
                    <tr>
                        <td><strong>Current Step:</strong></td>
                        <td>Step ${logData.current_step || logData.urutan || 1}</td>
                    </tr>
                    <tr>
                        <td><strong>Tipe:</strong></td>
                        <td>${logData.tipe_pesan === 'pesan_gambar' ? 'Text + Image' : 'Text Only'}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>${getStatusBadge(logData.status)}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Informasi Pelanggan</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Nama:</strong></td>
                        <td>${logData.pelanggan_nama}</td>
                    </tr>
                    <tr>
                        <td><strong>WhatsApp:</strong></td>
                        <td>${logData.nomor_wa}</td>
                    </tr>
                    <tr>
                        <td><strong>Produk:</strong></td>
                        <td>${logData.produk_list || 'Unknown'}</td>
                    </tr>
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td>${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(logData.total_harga)}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <h6>Jadwal & Waktu</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Dibuat:</strong></td>
                        <td>${formatDate(logData.created_at)}</td>
                    </tr>
                    <tr>
                        <td><strong>Jadwal Kirim:</strong></td>
                        <td>${formatDate(logData.jadwal_kirim)}</td>
                    </tr>
                    <tr>
                        <td><strong>Waktu Kirim:</strong></td>
                        <td>${formatDate(logData.waktu_kirim)}</td>
                    </tr>
                    <tr>
                        <td><strong>Last Message:</strong></td>
                        <td>${formatDate(logData.last_message_sent)}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Transaksi</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>ID Transaksi:</strong></td>
                        <td>#${logData.transaksi_id}</td>
                    </tr>
                    <tr>
                        <td><strong>Status Transaksi:</strong></td>
                        <td>${logData.transaksi_status}</td>
                    </tr>
                    <tr>
                        <td><strong>Current Step:</strong></td>
                        <td>${logData.current_step || 1}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        ${logData.pesan_final ? `
        <hr>
        <h6>Pesan Final</h6>
        <div class="border rounded p-3" style="background: #f8f9fa;">
            ${logData.pesan_final.replace(/\n/g, '<br>')}
        </div>
        ` : ''}
    `;
    
    document.getElementById('logDetailContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('logDetailModal')).show();
}

// Auto refresh every 2 minutes for pending status
if (new URLSearchParams(window.location.search).get('status') === 'pending') {
    setTimeout(() => {
        location.reload();
    }, 120000);
}
</script>

<?php require_once '../../includes/footer.php'; ?>