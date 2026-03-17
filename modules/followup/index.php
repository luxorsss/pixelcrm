<?php
// === LOGIC SECTION (No Output) ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$page_title = "Followup Messages";

// Get filter
$produk_id = get('produk_id');
$products = getAllProducts();

// Get followup messages
if ($produk_id) {
    $followups = getFollowupMessages($produk_id);
    $selected_product = fetchRow("SELECT nama FROM produk WHERE id = ?", [$produk_id]);
    $total_records = count($followups);
} else {
    $followups = [];
    $selected_product = null;
    $total_records = 0;
}

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <!-- Top Header -->
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Followup Messages</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Followup Messages</li>
                    </ol>
                </nav>
            </div>
            <div class="text-muted">
                <i class="fas fa-calendar me-1"></i><?= date('d F Y') ?>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- Header Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-comments me-2"></i>Followup Messages</h2>
            <div class="btn-group">
                <a href="<?= BASE_URL ?>monitor_followup.php" class="btn btn-info">
                    <i class="fas fa-satellite-dish me-2"></i>Monitor Followup
                </a>
                <?php if ($produk_id): ?>
                <a href="create.php?produk_id=<?= $produk_id ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Pesan
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Produk -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Filter Produk</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <label class="form-label">Pilih Produk</label>
                        <select name="produk_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>" <?= $produk_id == $product['id'] ? 'selected' : '' ?>>
                                <?= clean($product['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_product): ?>
        <!-- Info Produk -->
        <div class="alert alert-info border-0 shadow-sm">
            <i class="fas fa-info-circle me-2"></i>
            Mengelola followup untuk produk: <strong><?= clean($selected_product['nama']) ?></strong>
        </div>
        <?php endif; ?>
        
        <!-- Main Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <?php if ($selected_product): ?>
                        Daftar Followup Messages (<?= $total_records ?> pesan)
                    <?php else: ?>
                        Followup Messages
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!$produk_id): ?>
                    <!-- Select Product -->
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Pilih Produk</h5>
                        <p class="text-muted">Pilih produk terlebih dahulu untuk melihat followup messages</p>
                    </div>
                
                <?php elseif (empty($followups)): ?>
                    <!-- No Messages -->
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada followup message</h5>
                        <p class="text-muted">Mulai buat pesan followup otomatis untuk produk ini.</p>
                        <a href="create.php?produk_id=<?= $produk_id ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Buat Pesan Pertama
                        </a>
                    </div>
                
                <?php else: ?>
                    <!-- Followup Messages Table -->
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">Urutan</th>
                                    <th>Nama Pesan</th>
                                    <th width="120">Delay</th>
                                    <th width="100">Tipe</th>
                                    <th width="80">Status</th>
                                    <th width="80">Preview</th>
                                    <th width="130">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($followups as $followup): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?= $followup['urutan'] ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= clean($followup['nama_pesan']) ?></div>
                                        <small class="text-muted"><?= truncateText(clean($followup['isi_pesan']), 50) ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= formatDelay($followup['delay_value'], $followup['delay_unit']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($followup['tipe_pesan'] === 'pesan_gambar'): ?>
                                            <span class="badge bg-info">
                                                <i class="fas fa-image"></i> Gambar
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-comment"></i> Teks
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($followup['status'] === 'aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="showPreview('<?= addslashes($followup['isi_pesan']) ?>', '<?= addslashes($followup['link_gambar']) ?>', '<?= $followup['tipe_pesan'] ?>')"
                                                title="Preview">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit.php?id=<?= $followup['id'] ?>" 
                                               class="btn btn-outline-warning" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $followup['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Yakin hapus pesan \'<?= clean($followup['nama_pesan']) ?>\'?')" 
                                               title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
    </div>    
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Pesan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function showPreview(message, image, type) {
    // Replace placeholders with sample data
    const sampleData = {
        '[nama]': 'John Doe',
        '[produk]': '<?= clean($selected_product['nama'] ?? 'Contoh Produk') ?>',
        '[harga]': 'Rp 150.000'
    };
    
    let previewMessage = message;
    Object.keys(sampleData).forEach(placeholder => {
        previewMessage = previewMessage.replace(new RegExp(placeholder.replace(/[\[\]]/g, '\\$&'), 'g'), sampleData[placeholder]);
    });
    
    let content = `<div class="border p-3 rounded" style="background:#f8f9fa;">
                    <strong>📱 WhatsApp Preview:</strong><br><br>
                    ${previewMessage.replace(/\n/g, '<br>')}`;
    
    if (type === 'pesan_gambar' && image) {
        content += `<br><br><img src="${image}" class="img-fluid rounded mt-2" style="max-height:200px;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5HYW1iYXIgVGlkYWsgRGl0ZW11a2FuPC90ZXh0Pjwvc3ZnPg==';">`;
    }
    
    content += '</div>';
    
    document.getElementById('previewContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>