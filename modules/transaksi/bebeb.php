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
    <title>Transaksi Pending - Fast Action</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #111827;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --bg-body: #F7F7F9;
            --bg-surface: #FFFFFF;
            --border-light: #E5E7EB;
            --font-family: 'Plus Jakarta Sans', sans-serif;
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            background-color: var(--bg-body);
            font-family: var(--font-family);
            color: var(--primary-color);
            min-height: 100vh;
            padding-top: 3rem;
            padding-bottom: 3rem;
            -webkit-font-smoothing: antialiased;
        }

        .panel-editorial {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .header-section {
            padding: 2rem;
            border-bottom: 1px solid var(--border-light);
            background: #FFFFFF;
        }

        /* Sleek Search Box */
        .search-pill {
            background: #F3F4F6;
            border-radius: 100px;
            padding: 0.5rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 200ms var(--ease-out);
            border: 1px solid transparent;
            max-width: 400px;
            width: 100%;
        }
        .search-pill:focus-within {
            background: #FFFFFF;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(17, 24, 39, 0.05);
        }
        .search-pill input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-weight: 600;
            color: var(--primary-color);
            font-family: var(--font-family);
        }
        .search-pill input::placeholder { color: #9CA3AF; font-weight: 500; }

        /* Table Editorial Style */
        .table-editorial { width: 100%; margin-bottom: 0; }
        .table-editorial th {
            background-color: #F9FAFB;
            color: #6B7280;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid var(--border-light);
            white-space: nowrap;
        }
        .table-editorial td {
            padding: 1.25rem 2rem;
            vertical-align: middle;
            border-bottom: 1px solid #F3F4F6;
            transition: background-color 150ms var(--ease-out);
        }
        .table-editorial tbody tr:hover td { background-color: #F9FAFB; }

        /* Action Button */
        .btn-selesai {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
            padding: 0.5rem 1.25rem;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 150ms var(--ease-out);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-selesai:hover {
            background: #10B981;
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        /* Pagination Override */
        .pagination { margin: 0; }
        .page-link {
            border: none;
            color: #6B7280;
            font-weight: 600;
            border-radius: 8px !important;
            margin: 0 2px;
            transition: all 150ms var(--ease-out);
        }
        .page-link:hover { background: #F3F4F6; color: var(--primary-color); }
        .page-item.active .page-link { background: var(--primary-color); color: white; }

        /* States */
        .loading-spinner { display: none; text-align: center; padding: 3rem; }
        .empty-state { text-align: center; padding: 4rem 2rem; }
    </style>
</head>
<body>

<div class="container" style="max-width: 1200px;">
    
    <div class="mb-4 d-flex align-items-center justify-content-between">
        <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.9rem;">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard Utama
        </a>
    </div>

    <div class="panel-editorial">
        <div class="header-section d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
            <div>
                <h2 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2" style="letter-spacing: -0.02em;">
                    <i class="fas fa-clock text-warning"></i> Validasi Pembayaran
                </h2>
                <div class="text-muted fw-medium" style="font-size: 0.9rem;">
                    Terdapat <strong class="text-warning"><?= $total_records ?></strong> transaksi menunggu konfirmasi.
                </div>
            </div>
            
            <div class="search-pill">
                <i class="fas fa-search text-muted"></i>
                <input type="text" id="search-input" placeholder="Cari nama atau nomor WA..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                <div class="spinner-border spinner-border-sm text-primary ms-2" id="mini-spinner" style="display: none; width: 1rem; height: 1rem;" role="status"></div>
            </div>
        </div>

        <div class="card-body p-0">
            <div id="loading-spinner" class="loading-spinner">
                <div class="spinner-border text-dark mb-3" style="width: 2.5rem; height: 2.5rem;" role="status"></div>
                <div class="text-muted fw-bold">Sinkronisasi Data...</div>
            </div>
            
            <div id="transaksi-results">
                <?php if (empty($transaksi_pending)): ?>
                    <div class="empty-state">
                        <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class="fas fa-check-double text-muted fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Semua Selesai!</h4>
                        <p class="text-muted mx-auto" style="max-width: 400px;">
                            <?php if (!empty($search)): ?>
                                Tidak ada transaksi pending yang cocok dengan kata kunci "<strong><?= htmlspecialchars($search) ?></strong>".
                            <?php else: ?>
                                Luar biasa! Saat ini tidak ada transaksi yang menunggu untuk divalidasi.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table-editorial">
                            <thead>
                                <tr>
                                    <th width="80">Order ID</th>
                                    <th>Info Pelanggan</th>
                                    <th class="text-end">Nominal Transfer</th>
                                    <th width="200">Waktu Order</th>
                                    <th width="150" class="text-center pe-4">Aksi Eksekusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_pending as $trx): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-muted" style="font-size: 0.85rem;">#<?= htmlspecialchars($trx['id']) ?></span>
                                    </td>
                                    
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 1rem; line-height: 1.2;"><?= htmlspecialchars($trx['nama_customer']) ?></div>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $trx['nomor_wa']) ?>" target="_blank" class="text-muted text-decoration-none mt-1 d-inline-block" style="font-size: 0.8rem;">
                                            <i class="fab fa-whatsapp text-success me-1"></i><?= htmlspecialchars($trx['nomor_wa']) ?>
                                        </a>
                                    </td>
                                    
                                    <td class="text-end">
                                        <div class="fw-bold text-success" style="font-size: 1.1rem;">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></div>
                                    </td>
                                    
                                    <td>
                                        <div class="text-dark fw-bold" style="font-size: 0.9rem;"><?= date('d M Y', strtotime($trx['tanggal'])) ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;"><i class="fas fa-clock me-1"></i><?= date('H:i', strtotime($trx['tanggal'])) ?> WIB</div>
                                    </td>
                                    
                                    <td class="text-center pe-4">
                                        <a href="modules/transaksi/update_status.php?id=<?= urlencode($trx['id']) ?>&status=selesai" 
                                           class="btn-selesai"
                                           onclick="return confirm('Verifikasi pembayaran senilai Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?> dari <?= htmlspecialchars($trx['nama_customer']) ?>?');">
                                            <i class="fas fa-check"></i> Selesai
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($total_pages > 1): ?>
                <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                    <div class="text-muted fw-bold" style="font-size: 0.8rem; text-transform: uppercase;">
                        Halaman <?= $page ?> dari <?= $total_pages ?>
                    </div>
                    <nav aria-label="Pagination">
                        <ul class="pagination pagination-sm">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            if ($start > 1) {
                                echo '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
                                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            for ($i = $start; $i <= $end; $i++) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                        <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>
                                      </li>';
                            }
                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="#" data-page="' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="<?= $page + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-input');
    const miniSpinner = document.getElementById('mini-spinner');
    const resultsContainer = document.getElementById('transaksi-results');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    let currentPage = <?= $page ?>;
    let currentSearch = '<?= addslashes($search) ?>';
    let xhr = null;

    const showLoading = () => {
        miniSpinner.style.display = 'inline-block';
        resultsContainer.style.opacity = '0.4';
    };

    const hideLoading = () => {
        miniSpinner.style.display = 'none';
        resultsContainer.style.opacity = '1';
    };

    const loadData = (page, search) => {
        if (xhr && xhr.readyState !== 4) xhr.abort();

        showLoading();
        const params = new URLSearchParams({ page: page, search: search, ajax: 1 });

        xhr = new XMLHttpRequest();
        xhr.open('GET', `?${params.toString()}`, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            hideLoading();
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Response success expects JSON { html: '...', pagination: '...' }
                        // Ensure your backend outputs this structure when ajax=1
                        resultsContainer.innerHTML = response.html;
                        
                        const paginationContainer = document.querySelector('.pagination');
                        if (paginationContainer && response.pagination) {
                            document.querySelector('.border-top').innerHTML = response.pagination;
                        }
                        attachPaginationEvents();
                        
                        const url = new URL(window.location);
                        url.searchParams.set('page', page);
                        if (search) url.searchParams.set('search', search);
                        else url.searchParams.delete('search');
                        
                        window.history.replaceState(null, '', url);
                        currentPage = page;
                        currentSearch = search;
                    }
                } catch (e) {
                    // Fallback if backend returns plain HTML instead of JSON
                    if(xhr.responseText.indexOf('table') !== -1 || xhr.responseText.indexOf('empty-state') !== -1) {
                         const tempDiv = document.createElement('div');
                         tempDiv.innerHTML = xhr.responseText;
                         const newResults = tempDiv.querySelector('#transaksi-results');
                         if(newResults) {
                             resultsContainer.innerHTML = newResults.innerHTML;
                             attachPaginationEvents();
                         } else {
                             // Replace directly
                             resultsContainer.innerHTML = xhr.responseText;
                             attachPaginationEvents();
                         }
                    }
                }
            }
        };
        xhr.onerror = hideLoading;
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
        loadData(1, searchInput.value.trim());
    }, 400);

    const attachPaginationEvents = () => {
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                if (this.hasAttribute('data-page')) {
                    loadData(parseInt(this.getAttribute('data-page')), currentSearch);
                }
            });
        });
    };

    searchInput.addEventListener('input', performSearch);
    attachPaginationEvents();
});
</script>   
</body>
</html>