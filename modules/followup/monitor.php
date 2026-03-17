<?php
$page_title = 'Followup Monitor';
require_once '../../includes/header.php';
require_once 'functions.php';
require_once '../../includes/whatsapp_helper.php';

// Get statistics
$stats = [
    'total_messages' => fetchRow("SELECT COUNT(*) as count FROM followup_messages WHERE status = 'aktif'")['count'],
    'total_logs' => fetchRow("SELECT COUNT(*) as count FROM followup_logs")['count'],
    'pending_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs WHERE status = 'pending' AND DATE(jadwal_kirim) = CURDATE()")['count'],
    'sent_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs WHERE status = 'terkirim' AND DATE(waktu_kirim) = CURDATE()")['count'],
    'failed_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs WHERE status = 'gagal' AND DATE(created_at) = CURDATE()")['count']
];

// Get 7 days statistics for chart
$daily_stats = fetchAll("
    SELECT 
        DATE(COALESCE(waktu_kirim, created_at)) as tanggal,
        COUNT(CASE WHEN status = 'terkirim' THEN 1 END) as terkirim,
        COUNT(CASE WHEN status = 'gagal' THEN 1 END) as gagal
    FROM followup_logs 
    WHERE (waktu_kirim >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'terkirim')
    OR (status = 'gagal' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
    GROUP BY DATE(COALESCE(waktu_kirim, created_at))
    ORDER BY tanggal ASC
");

// Get product performance
$product_stats = fetchAll("
    SELECT 
        p.nama as produk_nama,
        COUNT(fl.id) as total_pesan,
        COUNT(CASE WHEN fl.status = 'terkirim' THEN 1 END) as terkirim,
        COUNT(CASE WHEN fl.status = 'gagal' THEN 1 END) as gagal,
        COUNT(CASE WHEN fl.status = 'pending' THEN 1 END) as pending
    FROM produk p
    JOIN followup_messages fm ON p.id = fm.produk_id
    LEFT JOIN followup_logs fl ON fm.id = fl.followup_message_id
    WHERE fm.status = 'aktif'
    GROUP BY p.id, p.nama
    ORDER BY total_pesan DESC
    LIMIT 10
");

// Get WhatsApp stats if available
$wa_stats = getWhatsAppStats(7);

// Calculate success rate
$total_attempts = $stats['sent_today'] + $stats['failed_today'];
$success_rate = $total_attempts > 0 ? round(($stats['sent_today'] / $total_attempts) * 100, 1) : 0;
?>

<div class="main-content">
    <?php require_once '../../includes/sidebar.php'; ?>
    
    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">Followup Monitor</h1>
                <p class="text-muted">Dashboard monitoring & statistik followup messages</p>
            </div>
            <div class="d-flex gap-2">
                <a href="sender.php" class="btn btn-warning">
                    <i class="fas fa-paper-plane"></i> Test Sender
                </a>
                <a href="logs.php" class="btn btn-info">
                    <i class="fas fa-list"></i> View Logs
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?= $stats['total_messages'] ?></h3>
                        <small class="text-muted">Active Messages</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info"><?= $stats['total_logs'] ?></h3>
                        <small class="text-muted">Total Logs</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning"><?= $stats['pending_today'] ?></h3>
                        <small class="text-muted">Pending Today</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?= $stats['sent_today'] ?></h3>
                        <small class="text-muted">Sent Today</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-danger"><?= $stats['failed_today'] ?></h3>
                        <small class="text-muted">Failed Today</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?= $success_rate ?>%</h3>
                        <small class="text-muted">Success Rate</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Daily Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> 7 Days Performance</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="scheduler.php?manual=1" class="btn btn-primary">
                                <i class="fas fa-play"></i> Run Scheduler Manually
                            </a>
                            <a href="sender.php" class="btn btn-warning">
                                <i class="fas fa-vial"></i> Test WhatsApp Send
                            </a>
                            <a href="logs.php?status=gagal" class="btn btn-danger">
                                <i class="fas fa-exclamation-triangle"></i> View Failed Messages
                            </a>
                            <a href="index.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add New Message
                            </a>
                        </div>

                        <!-- Connection Status -->
                        <hr>
                        <h6>Connection Status</h6>
                        <?php 
                        $connection = testOneSenderConnection('default');
                        ?>
                        <div class="alert alert-<?= $connection['success'] ? 'success' : 'danger' ?> alert-sm">
                            <small>
                                <strong>OneSender:</strong> 
                                <?= $connection['success'] ? '✓ Connected' : '✗ Failed' ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Performance -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-box"></i> Performance by Product</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Total</th>
                                    <th>Terkirim</th>
                                    <th>Gagal</th>
                                    <th>Pending</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_stats as $stat): 
                                    $attempts = $stat['terkirim'] + $stat['gagal'];
                                    $rate = $attempts > 0 ? round(($stat['terkirim'] / $attempts) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?= clean($stat['produk_nama']) ?></td>
                                    <td><span class="badge bg-primary"><?= $stat['total_pesan'] ?></span></td>
                                    <td><span class="badge bg-success"><?= $stat['terkirim'] ?></span></td>
                                    <td><span class="badge bg-danger"><?= $stat['gagal'] ?></span></td>
                                    <td><span class="badge bg-warning"><?= $stat['pending'] ?></span></td>
                                    <td>
                                        <?php if ($attempts > 0): ?>
                                            <span class="text-<?= $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger') ?>">
                                                <?= $rate ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Stats -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fab fa-whatsapp"></i> WhatsApp Stats (7 days)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($wa_stats) && !isset($wa_stats['error'])): ?>
                        <div class="row text-center">
                            <div class="col-4">
                                <h4 class="text-primary"><?= $wa_stats['total_sent'] ?></h4>
                                <small>Total</small>
                            </div>
                            <div class="col-4">
                                <h4 class="text-success"><?= $wa_stats['success'] ?></h4>
                                <small>Success</small>
                            </div>
                            <div class="col-4">
                                <h4 class="text-danger"><?= $wa_stats['failed'] ?></h4>
                                <small>Failed</small>
                            </div>
                        </div>

                        <hr>

                        <h6>By Type</h6>
                        <?php foreach ($wa_stats['by_type'] as $type => $count): ?>
                        <div class="d-flex justify-content-between">
                            <span><?= ucfirst($type) ?></span>
                            <span class="badge bg-secondary"><?= $count ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <p class="text-muted text-center">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                            No WhatsApp stats available
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php 
                $recent_activity = fetchAll("
                    SELECT 
                        fl.*,
                        fm.nama_pesan,
                        p.nama as pelanggan_nama, p.nomor_wa,
                        pr.nama as produk_nama
                    FROM followup_logs fl
                    JOIN followup_messages fm ON fl.followup_message_id = fm.id
                    JOIN pelanggan p ON fl.pelanggan_id = p.id
                    JOIN transaksi t ON fl.transaksi_id = t.id
                    JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                    JOIN produk pr ON dt.produk_id = pr.id
                    WHERE fl.status IN ('terkirim', 'gagal')
                    ORDER BY COALESCE(fl.waktu_kirim, fl.created_at) DESC
                    LIMIT 15
                ");
                ?>

                <?php if ($recent_activity): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Message</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td>
                                    <small><?= formatDate($activity['waktu_kirim'] ?: $activity['created_at'], 'd/m H:i') ?></small>
                                </td>
                                <td><?= clean($activity['nama_pesan']) ?></td>
                                <td>
                                    <?= clean($activity['pelanggan_nama']) ?><br>
                                    <small class="text-muted"><?= clean($activity['nomor_wa']) ?></small>
                                </td>
                                <td><small><?= clean($activity['produk_nama']) ?></small></td>
                                <td>
                                    <?php if ($activity['status'] === 'terkirim'): ?>
                                        <span class="badge bg-success">Sent</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Performance Chart
const dailyData = <?= json_encode($daily_stats) ?>;
const labels = dailyData.map(item => {
    const date = new Date(item.tanggal);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit' });
});
const sentData = dailyData.map(item => parseInt(item.terkirim));
const failedData = dailyData.map(item => parseInt(item.gagal));

const ctx = document.getElementById('dailyChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Terkirim',
            data: sentData,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.1
        }, {
            label: 'Gagal',
            data: failedData,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});

// Auto refresh every 5 minutes
setTimeout(() => {
    location.reload();
}, 300000);
</script>

<?php require_once '../../includes/footer.php'; ?>