<?php
require_once __DIR__ . '/../../includes/init.php';

// Validasi dan sanitasi parameter
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Bangun kondisi pencarian dengan prepared statement
$search_condition = "";
$params = [];

if (!empty($search)) {
    $search_condition = " AND (p.nama LIKE ? OR p.nomor_wa LIKE ?)";
    $search_param = '%' . $search . '%';
    $params = [$search_param, $search_param];
}

// Hitung total data
$count_sql = "
    SELECT COUNT(*) as total
    FROM transaksi t
    INNER JOIN pelanggan p ON t.pelanggan_id = p.id
    WHERE t.status = 'pending'
    $search_condition
";
$count_result = fetchRow($count_sql, $params);
$total_records = $count_result['total'] ?? 0;
$total_pages = max(1, ceil($total_records / $limit));

// Validasi halaman
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Ambil data transaksi
$data_sql = "
    SELECT 
        t.id,
        t.total_harga,
        t.tanggal_transaksi AS tanggal,
        p.nama AS nama_customer,
        p.nomor_wa
    FROM transaksi t
    INNER JOIN pelanggan p ON t.pelanggan_id = p.id
    WHERE t.status = 'pending'
    $search_condition
    ORDER BY t.tanggal_transaksi DESC
    LIMIT ? OFFSET ?
";

$query_params = $params;
$query_params[] = $limit;
$query_params[] = $offset;

$transaksi_pending = fetchAll($data_sql, $query_params) ?? [];

// Jika request via AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    
    $response = [
        'success' => true,
        'html' => '',
        'pagination' => '',
        'total_records' => $total_records
    ];
    
    if (empty($transaksi_pending)) {
        $response['html'] = '
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                <p class="mb-0">' . 
                    (!empty($search) ? 
                    'Tidak ada transaksi pending yang cocok dengan "<strong>' . htmlspecialchars($search) . '</strong>".' : 
                    'Tidak ada transaksi dengan status pending.') . 
                '</p>
            </div>
        ';
    } else {
        // Generate table HTML
        $table_html = '
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Pelanggan</th>
                            <th>No. WA</th>
                            <th>Total Harga</th>
                            <th>Tanggal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($transaksi_pending as $trx) {
            $table_html .= '
                        <tr>
                            <td><span class="badge bg-secondary">#' . htmlspecialchars($trx['id']) . '</span></td>
                            <td>' . htmlspecialchars($trx['nama_customer']) . '</td>
                            <td>' . htmlspecialchars($trx['nomor_wa']) . '</td>
                            <td class="fw-bold text-success">Rp ' . number_format($trx['total_harga'], 0, ',', '.') . '</td>
                            <td>' . date('d M Y H:i', strtotime($trx['tanggal'])) . '</td>
                            <td class="text-center">
                                <a 
                                    href="modules/transaksi/update_status.php?id=' . urlencode($trx['id']) . '&status=selesai" 
                                    class="btn btn-success btn-sm btn-selesai"
                                    onclick="return confirm(\'Yakin ubah status transaksi ini menjadi SELESAI?\');"
                                >
                                    Selesai
                                </a>
                            </td>
                        </tr>';
        }
        
        $table_html .= '
                    </tbody>
                </table>
            </div>';
        
        $response['html'] = $table_html;
        
        // Generate pagination HTML
        if ($total_pages > 1) {
            $pagination_html = '
                <div class="card-footer bg-white mt-3">
                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center mb-0">';
            
            if ($page > 1) {
                $pagination_html .= '
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="' . ($page - 1) . '">« Sebelumnya</a>
                            </li>';
            }
            
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            if ($start > 1) {
                $pagination_html .= '
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="1">1</a>
                            </li>';
                if ($start > 2) {
                    $pagination_html .= '
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>';
                }
            }
            
            for ($i = $start; $i <= $end; $i++) {
                $active_class = $i == $page ? 'active' : '';
                $pagination_html .= '
                            <li class="page-item ' . $active_class . '">
                                <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>
                            </li>';
            }
            
            if ($end < $total_pages) {
                if ($end < $total_pages - 1) {
                    $pagination_html .= '
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>';
                }
                $pagination_html .= '
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="' . $total_pages . '">' . $total_pages . '</a>
                            </li>';
            }
            
            if ($page < $total_pages) {
                $pagination_html .= '
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="' . ($page + 1) . '">Berikutnya »</a>
                            </li>';
            }
            
            $pagination_html .= '
                        </ul>
                    </nav>
                    <div class="text-center mt-2 text-muted small">
                        Menampilkan ' . count($transaksi_pending) . ' dari ' . $total_records . ' transaksi
                    </div>
                </div>';
            
            $response['pagination'] = $pagination_html;
        }
    }
    
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Pending</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
            min-height: 100vh;
            padding-top: 20px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border: none;
        }
        .btn-selesai {
            background: linear-gradient(to right, #28a745, #20c997);
            border: none;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .btn-selesai:hover {
            background: linear-gradient(to right, #218838, #1ba87e);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .search-box {
            max-width: 400px;
        }
        .pagination .page-link {
            color: #495057;
            cursor: pointer;
        }
        .pagination .page-item.active .page-link {
            background: linear-gradient(to right, #0d6efd, #0b5ed7);
            border-color: #0b5ed7;
        }
        .table th {
            background-color: #f8f9ff;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }
        .table-hover tbody tr:hover {
            background-color: #f0f7ff !important;
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header bg-white py-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h3 class="mb-0 text-primary">
                    <i class="bi bi-clock-history me-2"></i>Transaksi Pending
                    <?php if ($total_records > 0): ?>
                        <span class="badge bg-primary ms-2"><?= $total_records ?></span>
                    <?php endif; ?>
                </h3>
                <div class="d-flex search-box">
                    <div class="input-group">
                        <input 
                            type="text" 
                            id="search-input"
                            class="form-control" 
                            placeholder="Cari nama atau no WA..." 
                            value="<?= htmlspecialchars($search) ?>"
                        >
                        <button class="btn btn-outline-primary" type="button" id="search-btn">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div id="loading-spinner" class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Memuat...</span>
                </div>
                <p class="mt-2 text-muted">Memuat data...</p>
            </div>
            
            <div id="transaksi-results">
                <?php if (empty($transaksi_pending)): ?>
                    <div class="empty-state text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                        <p class="mb-0">
                            <?php if (!empty($search)): ?>
                                Tidak ada transaksi pending yang cocok dengan "<strong><?= htmlspecialchars($search) ?></strong>".
                            <?php else: ?>
                                Tidak ada transaksi dengan status pending.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Pelanggan</th>
                                    <th>No. WA</th>
                                    <th>Total Harga</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_pending as $trx): ?>
                                <tr>
                                    <td><span class="badge bg-secondary">#<?= htmlspecialchars($trx['id']) ?></span></td>
                                    <td><?= htmlspecialchars($trx['nama_customer']) ?></td>
                                    <td><?= htmlspecialchars($trx['nomor_wa']) ?></td>
                                    <td class="fw-bold text-success">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></td>
                                    <td><?= date('d M Y H:i', strtotime($trx['tanggal'])) ?></td>
                                    <td class="text-center">
                                        <a 
                                            href="modules/transaksi/update_status.php?id=<?= urlencode($trx['id']) ?>&status=selesai" 
                                            class="btn btn-success btn-sm btn-selesai"
                                            onclick="return confirm('Yakin ubah status transaksi ini menjadi SELESAI?');"
                                        >
                                            Selesai
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white">
                <nav aria-label="Pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">« Sebelumnya</a>
                        </li>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        for ($i = $start; $i <= $end; $i++) {
                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                    <a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '">' . $i . '</a>
                                  </li>';
                        }
                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Berikutnya »</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div class="text-center mt-2 text-muted small">
                    Menampilkan <?= count($transaksi_pending) ?> dari <?= $total_records ?> transaksi
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Transaksi
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const resultsContainer = document.getElementById('transaksi-results');
    const loadingSpinner = document.getElementById('loading-spinner');
    let currentPage = <?= $page ?>;
    let currentSearch = '<?= addslashes($search) ?>';
    let xhr = null;

    const showLoading = () => {
        loadingSpinner.style.display = 'block';
        resultsContainer.style.opacity = '0.5';
    };

    const hideLoading = () => {
        loadingSpinner.style.display = 'none';
        resultsContainer.style.opacity = '1';
    };

    const loadData = (page, search) => {
        // Batal request sebelumnya jika ada
        if (xhr && xhr.readyState !== 4) {
            xhr.abort();
        }

        showLoading();

        const params = new URLSearchParams({
            page: page,
            search: search,
            ajax: 1
        });

        xhr = new XMLHttpRequest();
        xhr.open('GET', `?${params.toString()}`, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            hideLoading();
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        resultsContainer.innerHTML = response.html;
                        
                        // Update pagination jika ada
                        const paginationContainer = document.querySelector('.card-footer');
                        if (paginationContainer && response.pagination) {
                            paginationContainer.innerHTML = response.pagination;
                            attachPaginationEvents();
                        }
                        
                        // Update URL tanpa reload
                        const url = new URL(window.location);
                        url.searchParams.set('page', page);
                        if (search) {
                            url.searchParams.set('search', search);
                        } else {
                            url.searchParams.delete('search');
                        }
                        window.history.replaceState(null, '', url);
                        
                        currentPage = page;
                        currentSearch = search;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    resultsContainer.innerHTML = '<div class="text-center py-4 text-danger">Error memuat data.</div>';
                }
            } else {
                hideLoading();
                resultsContainer.innerHTML = '<div class="text-center py-4 text-danger">Gagal memuat hasil.</div>';
            }
        };

        xhr.onerror = function () {
            hideLoading();
            resultsContainer.innerHTML = '<div class="text-center py-4 text-warning">Koneksi terputus.</div>';
        };

        xhr.send();
    };

    const debounce = (func, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    };

    const performSearch = debounce(function () {
        const query = searchInput.value.trim();
        loadData(1, query);
    }, 300);

    const attachPaginationEvents = () => {
        document.querySelectorAll('.pagination .page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                if (this.hasAttribute('data-page')) {
                    const page = parseInt(this.getAttribute('data-page'));
                    loadData(page, currentSearch);
                }
            });
        });
    };

    // Event listeners
    searchInput.addEventListener('input', performSearch);
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    searchBtn.addEventListener('click', performSearch);

    // Attach initial pagination events
    attachPaginationEvents();
});
</script>	
</body>
</html>