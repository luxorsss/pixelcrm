<?php
require_once '../../includes/init.php';
require_once 'functions.php';

$page_title = "Cleanup Followup Logs";

if (isPost() && post('action') === 'cleanup') {
    $deleted_count = cleanupObsoleteFollowupLogs();
    setMessage("Deleted $deleted_count obsolete followup logs", 'success');
    redirect('cleanup.php');
}

$stats = getCleanupStats();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-broom me-2 text-warning"></i>Cleanup Followup Logs
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Cleanup Statistics</h6>
                    <ul class="mb-0">
                        <li><strong><?= number_format($stats['total_obsolete']) ?></strong> followup logs yang bisa dihapus</li>
                        <li><strong><?= number_format($stats['affected_transactions']) ?></strong> transaksi yang statusnya sudah bukan pending</li>
                    </ul>
                </div>
                
                <?php if ($stats['total_obsolete'] > 0): ?>
                <form method="POST" onsubmit="return confirm('Hapus <?= $stats['total_obsolete'] ?> followup logs yang obsolete?')">
                    <input type="hidden" name="action" value="cleanup">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-broom me-2"></i>Cleanup <?= number_format($stats['total_obsolete']) ?> Logs
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Database sudah bersih! Tidak ada followup logs yang perlu dihapus.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>