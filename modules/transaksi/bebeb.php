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
            <div class="empty-state">
                <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-check-double text-muted fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Semua Selesai!</h4>
                <p class="text-muted mx-auto px-3" style="max-width: 400px; font-size: 0.95rem;">' . 
                    (!empty($search) ? 
                    'Tidak ada transaksi pending yang cocok dengan "<strong>' . htmlspecialchars($search) . '</strong>".' : 
                    'Luar biasa! Saat ini tidak ada pesanan yang menunggu untuk divalidasi.') . 
                '</p>
            </div>
        ';
    } else {
        // Trik Jitu: Hapus min-width dan biarkan tabelnya 100% responsif tanpa scroll
        $table_html = '
            <div class="table-responsive" style="overflow-x: hidden;">
                <table class="table-editorial mb-0" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="d-none d-md-table-cell" width="10%">ID</th>
                            <th>Pelanggan</th>
                            <th>Nominal</th>
                            <th class="d-none d-md-table-cell" width="20%">Waktu Order</th>
                            <th class="text-end pe-3 pe-md-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($transaksi_pending as $trx) {
            $table_html .= '
                        <tr>
                            <td class="d-none d-md-table-cell"><span class="badge-clean bg-light text-muted border fw-bold" style="font-family: monospace;">#' . htmlspecialchars($trx['id']) . '</span></td>
                            <td>
                                <div class="fw-bold text-dark text-truncate custom-max-width" style="font-size: 0.95rem;">' . htmlspecialchars($trx['nama_customer']) . '</div>
                                <a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $trx['nomor_wa']) . '" target="_blank" class="badge-clean bg-light text-muted mt-1 border text-decoration-none d-inline-flex" style="font-size: 0.7rem; padding: 2px 6px;">
                                    <i class="fab fa-whatsapp text-success"></i><span class="ms-1">' . htmlspecialchars($trx['nomor_wa']) . '</span>
                                </a>
                                <div class="text-muted mt-1 d-md-none" style="font-size: 0.7rem;"><i class="fas fa-clock me-1"></i>' . date('d M, H:i', strtotime($trx['tanggal'])) . '</div>
                            </td>
                            <td>
                                <div class="fw-bold text-success mobile-nominal">Rp ' . number_format($trx['total_harga'], 0, ',', '.') . '</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-dark fw-bold" style="font-size: 0.85rem;">' . date('d M Y', strtotime($trx['tanggal'])) . '</div>
                                <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-clock me-1"></i>' . date('H:i', strtotime($trx['tanggal'])) . ' WIB</div>
                            </td>
                            <td class="text-end pe-2 pe-md-4">
                                <button 
                                    type="button"
                                    class="btn-selesai hover-lift d-inline-flex justify-content-center border-0 btn-verifikasi"
                                    data-id="' . htmlspecialchars($trx['id']) . '"
                                    data-nominal="Rp ' . number_format($trx['total_harga'], 0, ',', '.') . '"
                                    data-nama="' . addslashes(htmlspecialchars($trx['nama_customer'])) . '"
                                >
                                    <i class="fas fa-check-circle"></i> <span class="d-none d-sm-inline">Selesai</span>
                                </button>
                            </td>
                        </tr>';
        }
        
        $table_html .= '
                    </tbody>
                </table>
            </div>';
        
        $response['html'] = $table_html;
        
        if ($total_pages > 1) {
            $pagination_html = '<div class="d-flex gap-1 overflow-auto hide-scrollbar" style="max-width: 100%;">';
            
            if ($page > 1) {
                $pagination_html .= '<a class="btn btn-sm btn-light text-dark fw-bold border-0 flex-shrink-0 page-link-custom" href="#" data-page="' . ($page - 1) . '"><i class="fas fa-chevron-left"></i></a>';
            }
            
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            if ($start > 1) {
                $pagination_html .= '<a class="btn btn-sm btn-light text-muted fw-bold border-0 flex-shrink-0 page-link-custom" style="min-width: 32px; border-radius: 8px;" href="#" data-page="1">1</a>';
            }
            
            for ($i = $start; $i <= $end; $i++) {
                $active_class = $i == $page ? 'btn-dark' : 'btn-light text-muted';
                $pagination_html .= '<a class="btn btn-sm ' . $active_class . ' fw-bold border-0 flex-shrink-0 page-link-custom" style="min-width: 32px; border-radius: 8px;" href="#" data-page="' . $i . '">' . $i . '</a>';
            }
            
            if ($end < $total_pages) {
                $pagination_html .= '<a class="btn btn-sm btn-light text-muted fw-bold border-0 flex-shrink-0 page-link-custom" style="min-width: 32px; border-radius: 8px;" href="#" data-page="' . $total_pages . '">' . $total_pages . '</a>';
            }
            
            if ($page < $total_pages) {
                $pagination_html .= '<a class="btn btn-sm btn-light text-dark fw-bold border-0 flex-shrink-0 page-link-custom" href="#" data-page="' . ($page + 1) . '"><i class="fas fa-chevron-right"></i></a>';
            }
            
            $pagination_html .= '</div>';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Transaksi Pending - Fast Action</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #111827;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --bg-body: #F3F4F6;
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
            padding: 2rem 1rem;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .panel-editorial {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            overflow: hidden;
            margin: 0 auto;
            max-width: 1000px;
            width: 100%;
        }

        .header-section {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-light);
            background: #FFFFFF;
        }

        /* Sleek Search Box */
        .search-pill {
            background: #F9FAFB;
            border-radius: 100px;
            padding: 0.6rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 200ms var(--ease-out);
            border: 1px solid var(--border-light);
            width: 100%;
        }
        .search-pill:focus-within {
            background: #FFFFFF;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .search-pill input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-weight: 600;
            color: var(--primary-color);
            font-family: var(--font-family);
            font-size: 0.9rem;
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
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-light);
            white-space: nowrap;
        }
        .table-editorial td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid #F3F4F6;
            transition: background-color 150ms var(--ease-out);
        }

        /* Badges */
        .badge-clean {
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

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
            align-items: center;
            gap: 0.5rem;
        }
        
        .hover-lift { transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
        @media (hover: hover) and (pointer: fine) {
            .btn-selesai:hover {
                background: #10B981; color: #FFFFFF; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            }
        }

        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .loading-spinner { display: none; text-align: center; padding: 3rem; }
        .empty-state { text-align: center; padding: 4rem 1rem; }

        /* MAGICAL CSS UNTUK MOBILE PERFECT FIT */
        @media (max-width: 768px) {
            body { padding: 0.5rem; background-color: #FFFFFF; }
            .panel-editorial { border: none; border-radius: 16px; box-shadow: none; }
            .header-section { padding: 1rem; border-bottom: none; }
            
            /* Pangkas Padding Tabel di HP agar lega */
            .table-editorial th { padding: 0.75rem 0.5rem; font-size: 0.65rem; white-space: normal; }
            .table-editorial td { padding: 1rem 0.5rem; }
            
            /* Squeeze elemen agar tidak melebar */
            .custom-max-width { max-width: 130px !important; white-space: normal; line-height: 1.3; }
            .mobile-nominal { font-size: 0.85rem !important; }
            
            /* Kecilkan tombol selesai, cuma jadi Icon di layar super sempit */
            .btn-selesai { padding: 0.4rem 0.75rem; font-size: 0.8rem; }
        }
        @media (min-width: 768px) {
            .search-pill { max-width: 350px; }
            .header-section { padding: 2rem; }
            .custom-max-width { max-width: 250px !important; }
        }
    </style>
</head>
<body>

<div class="mx-auto" style="max-width: 1000px;">
    
    <div class="mb-4 d-none d-md-flex align-items-center justify-content-between">
        <a href="index.php" class="text-muted text-decoration-none fw-bold hover-lift d-inline-block" style="font-size: 0.9rem;">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard Utama
        </a>
    </div>

    <div class="panel-editorial">
        <a href="index.php" class="d-md-none text-muted text-decoration-none fw-bold d-inline-block px-3 pt-3" style="font-size: 0.85rem;">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>

        <div class="header-section d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h2 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2" style="font-size: 1.25rem;">
                    <i class="fas fa-clock text-warning"></i> Validasi Pembayaran
                </h2>
                <div class="text-muted fw-medium" style="font-size: 0.85rem;">
                    Terdapat <strong class="text-warning"><?= $total_records ?></strong> transaksi menunggu konfirmasi.
                </div>
            </div>
            
            <div class="search-pill">
                <i class="fas fa-search text-muted"></i>
                <input type="text" id="search-input" placeholder="Cari pelanggan..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                <div class="spinner-border spinner-border-sm text-primary ms-2" id="mini-spinner" style="display: none; width: 1rem; height: 1rem;" role="status"></div>
            </div>
        </div>

        <div class="p-0 border-top">
            <div id="loading-spinner" class="loading-spinner">
                <div class="spinner-border text-primary mb-3" style="width: 2.5rem; height: 2.5rem;" role="status"></div>
                <div class="text-muted fw-bold">Sinkronisasi Data...</div>
            </div>
            
            <div id="transaksi-results">
                <div class="text-center p-5 text-muted"><i class="fas fa-circle-notch fa-spin fs-2"></i></div>
            </div>

            <div class="p-3 bg-white d-flex flex-column flex-md-row justify-content-between align-items-center gap-3" id="pagination-wrapper" style="display: none !important;">
                <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                    Halaman <span id="current-page-display"><?= $page ?></span>
                </div>
                <div id="pagination-controls" class="overflow-auto hide-scrollbar" style="max-width: 100%;">
                    </div>
            </div>
        </div>
    </div>
</div>

<div id="custom-confirm-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; opacity: 0; pointer-events: none; transition: opacity 250ms cubic-bezier(0.16, 1, 0.3, 1);">
    <div class="modal-box" style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 24px; padding: 2rem; max-width: 400px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: scale(0.95); transition: transform 250ms cubic-bezier(0.16, 1, 0.3, 1);">
        <div style="width: 48px; height: 48px; background: #ECFDF5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
            <i class="fas fa-wallet text-success" style="font-size: 1.25rem;"></i>
        </div>
        <h4 style="font-weight: 700; color: #111827; margin-bottom: 0.5rem; font-size: 1.15rem;">Verifikasi Pembayaran</h4>
        <p id="modal-message" style="color: #6B7280; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">Apakah kamu yakin ingin memverifikasi pesanan ini?</p>
        
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button id="modal-cancel-btn" style="background: #F3F4F6; color: #4B5563; border: none; padding: 0.6rem 1.25rem; border-radius: 100px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: background 150ms ease;">Batal</button>
            <a id="modal-confirm-btn" href="#" style="background: #111827; color: #FFFFFF; border: none; padding: 0.6rem 1.25rem; border-radius: 100px; font-weight: 600; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: background 150ms ease;">
                <span>Ya, Validasi</span>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-input');
    const miniSpinner = document.getElementById('mini-spinner');
    const resultsContainer = document.getElementById('transaksi-results');
    const paginationWrapper = document.getElementById('pagination-wrapper');
    const paginationControls = document.getElementById('pagination-controls');
    
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
                        resultsContainer.innerHTML = response.html;
                        
                        if (response.pagination) {
                            paginationControls.innerHTML = response.pagination;
                            paginationWrapper.style.setProperty('display', 'flex', 'important');
                            document.getElementById('current-page-display').textContent = page;
                        } else {
                            paginationWrapper.style.setProperty('display', 'none', 'important');
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
                    console.error("AJAX parsing error", e);
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
    }, 500);

    const attachPaginationEvents = () => {
        document.querySelectorAll('.page-link-custom').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                if (this.hasAttribute('data-page') && !this.classList.contains('disabled')) {
                    loadData(parseInt(this.getAttribute('data-page')), currentSearch);
                }
            });
        });
    };

    searchInput.addEventListener('input', performSearch);
    
    // Initial Load
    loadData(currentPage, currentSearch);
});
</script>   
</body>
</html>