<?php
$page_title = "Debug Dropdown";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Simulasi filter aktif
$filters = ['search' => 'john', 'status' => '', 'date_from' => '', 'date_to' => ''];
$filters = array_filter($filters);
$current_page = 1;

// Test data
$test_transaksi = [
    ['id' => 1, 'status' => 'pending', 'nama_pelanggan' => 'John Doe', 'nomor_wa' => '08123456789', 'total_harga' => 100000, 'jumlah_item' => 1, 'tanggal_transaksi' => '2024-12-01 10:00:00'],
    ['id' => 2, 'status' => 'diproses', 'nama_pelanggan' => 'Jane Smith', 'nomor_wa' => '08123456788', 'total_harga' => 200000, 'jumlah_item' => 2, 'tanggal_transaksi' => '2024-12-01 11:00:00'],
    ['id' => 3, 'status' => 'selesai', 'nama_pelanggan' => 'Bob Wilson', 'nomor_wa' => '08123456787', 'total_harga' => 150000, 'jumlah_item' => 1, 'tanggal_transaksi' => '2024-12-01 12:00:00']
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header">
        <h1 class="page-title mb-0">Debug Dropdown Status</h1>
    </div>

    <div class="content-area">
        <div class="alert alert-info">
            <strong>Debug Info:</strong><br>
            Filters: <?= print_r($filters, true) ?><br>
            Current Page: <?= $current_page ?><br>
            Bootstrap Version: 5.3.0 (check if loaded)
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Test Dropdown dengan Filter Aktif</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status (Original Logic)</th>
                                <th>Status (Fixed Logic)</th>
                                <th>Debug Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($test_transaksi as $transaksi): ?>
                            <tr>
                                <td>#<?= $transaksi['id'] ?></td>
                                <td><?= $transaksi['nama_pelanggan'] ?></td>
                                <td><?= formatCurrency($transaksi['total_harga']) ?></td>
                                
                                <!-- Original Logic (yang bermasalah) -->
                                <td>
                                    <?php
                                    $dropdown_options = [];
                                    switch ($transaksi['status']) {
                                        case 'pending':
                                            $dropdown_options = ['diproses', 'selesai', 'batal'];
                                            break;
                                        case 'diproses':
                                            $dropdown_options = ['selesai', 'batal'];
                                            break;
                                        case 'selesai':
                                        case 'batal':
                                            $dropdown_options = [];
                                            break;
                                    }
                                    ?>
                                    
                                    <?php if (!empty($dropdown_options)): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-warning dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                Original: <?= ucfirst($transaksi['status']) ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php foreach ($dropdown_options as $status): ?>
                                                    <li><a class="dropdown-item" href="#"><?= ucfirst($status) ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success">Original: <?= ucfirst($transaksi['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Fixed Logic -->
                                <td>
                                    <?php
                                    $current_status = $transaksi['status'];
                                    $show_dropdown = false;
                                    $dropdown_options_fixed = [];
                                    
                                    if ($current_status === 'pending') {
                                        $show_dropdown = true;
                                        $dropdown_options_fixed = ['diproses', 'selesai', 'batal'];
                                    } elseif ($current_status === 'diproses') {
                                        $show_dropdown = true;
                                        $dropdown_options_fixed = ['selesai', 'batal'];
                                    }
                                    ?>
                                    
                                    <?php if ($show_dropdown): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-<?= $current_status === 'pending' ? 'warning' : 'info' ?> dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                Fixed: <?= ucfirst($current_status) ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php foreach ($dropdown_options_fixed as $new_status): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="update_status.php?id=<?= $transaksi['id'] ?>&status=<?= $new_status ?>&search=john">
                                                            <?= ucfirst($new_status) ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-<?= $current_status === 'selesai' ? 'success' : 'danger' ?>">
                                            Fixed: <?= ucfirst($current_status) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="small">
                                    Status: "<?= $transaksi['status'] ?>"<br>
                                    Show dropdown: <?= $show_dropdown ? 'TRUE' : 'FALSE' ?><br>
                                    Options: [<?= implode(', ', $dropdown_options_fixed) ?>]<br>
                                    With filter: <?= !empty($filters) ? 'YES' : 'NO' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Test Bootstrap Dropdown -->
        <div class="card mt-3">
            <div class="card-header">
                <h5>Test Bootstrap Dropdown (Apakah Bootstrap Bekerja?)</h5>
            </div>
            <div class="card-body">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Test Dropdown
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Action 1</a></li>
                        <li><a class="dropdown-item" href="#">Action 2</a></li>
                        <li><a class="dropdown-item" href="#">Action 3</a></li>
                    </ul>
                </div>
                
                <p class="mt-3 text-muted">
                    Jika dropdown di atas tidak berfungsi, berarti ada masalah Bootstrap JS.
                </p>
            </div>
        </div>

        <!-- URL Test -->
        <div class="card mt-3">
            <div class="card-header">
                <h5>Test URL Building</h5>
            </div>
            <div class="card-body">
                <?php
                $test_url_params = $filters;
                $test_url_params['page'] = $current_page;
                $query_string = http_build_query($test_url_params);
                $test_update_url = "update_status.php?id=1&status=selesai";
                if ($query_string) {
                    $test_update_url .= "&{$query_string}";
                }
                ?>
                
                <p><strong>Filters:</strong> <?= print_r($filters, true) ?></p>
                <p><strong>Query String:</strong> <?= $query_string ?></p>
                <p><strong>Update URL:</strong> <code><?= $test_update_url ?></code></p>
                
                <a href="<?= $test_update_url ?>" class="btn btn-primary">Test Update Link</a>
            </div>
        </div>
    </div>
</div>

<script>
// Test JavaScript Console
console.log('Debug Dropdown Page Loaded');
console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Loaded' : 'NOT LOADED');
console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'NOT LOADED');

// Test dropdown click
document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    console.log('Found dropdowns:', dropdowns.length);
    
    dropdowns.forEach((dropdown, index) => {
        dropdown.addEventListener('click', function() {
            console.log(`Dropdown ${index + 1} clicked`);
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>