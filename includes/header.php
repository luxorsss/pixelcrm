<?php 
require_once __DIR__ . '/init.php';

// Require authentication for all pages that include header
// Kecuali halaman login dan register
$current_file = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'register.php'];

if (!in_array($current_file, $public_pages)) {
    requireAuth();
}

$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    
    <style>
        /* Mobile Header khusus HP */
        .mobile-topbar {
            display: none;
            background: #FFFFFF;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #E5E7EB;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        @media (max-width: 991.98px) {
            .mobile-topbar { display: flex; }
            /* Memberi jarak atas agar konten tidak tertutup topbar */
            .main-content { margin-top: 0; }
        }
    </style>
</head>
<body>

<?php if (!in_array($current_file, $public_pages)): ?>
    <div class="mobile-topbar">
        <div class="fw-bold text-dark fs-5 d-flex align-items-center gap-2">
            <div style="width:32px; height:32px; background: #111827; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-layer-group text-white" style="font-size: 0.9rem;"></i>
            </div>
            <?= APP_NAME ?>
        </div>
        <button type="button" class="btn btn-light border shadow-sm" onclick="toggleSidebar()" style="width: 42px; height: 42px; border-radius: 12px; padding: 0;">
            <i class="fas fa-bars-staggered text-dark"></i>
        </button>
    </div>
<?php endif; ?>

<?php 
$msg = getMessage();
if ($msg): ?>
    <div class="alert alert-<?= $msg[1] === 'error' ? 'danger' : $msg[1] ?> alert-dismissible fade show m-3" role="alert" style="z-index: 1050; position: relative;">
        <?= clean($msg[0]) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>