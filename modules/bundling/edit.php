<?php
$page_title = "Kelola Bundling";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/functions.php';

// Helper untuk escape string di JS
function cleanJs($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Handle both old (id) and new (produk_id) parameters
$produk_id = (int)get('produk_id');
$bundling_id = (int)get('id');

if ($bundling_id && !$produk_id) {
    $bundling = getBundlingById($bundling_id);
    if ($bundling) {
        $produk_id = $bundling['produk_id'];
    }
}

if (!$produk_id) {
    setMessage('ID produk tidak valid', 'error');
    redirect('index.php');
}

$mainProduct = fetchRow("SELECT id, nama, harga FROM produk WHERE id = ?", [$produk_id]);
if (!$mainProduct) {
    setMessage('Produk tidak ditemukan', 'error');
    redirect('index.php');
}

// Ambil bundling existing + deskripsi
$existingBundlings = fetchAll(
    "SELECT b.id, b.produk_bundling_id, b.diskon, b.deskripsi, b.is_active, p.nama, p.harga 
     FROM bundling b 
     JOIN produk p ON b.produk_bundling_id = p.id 
     WHERE b.produk_id = ? 
     ORDER BY p.nama", 
    [$produk_id]
);

$errors = [];

if (isPost()) {
    $bundling_data = post('bundling', []);
    
    if (empty($bundling_data)) {
        $errors[] = 'Minimal 1 produk bundling harus dipilih';
    } else {
        execute("DELETE FROM bundling WHERE produk_id = ?", [$produk_id]);
        
        $success_count = 0;
        foreach ($bundling_data as $item) {
            $bundle_id = (int)($item['produk_id'] ?? 0);
            $diskon = (int)($item['diskon'] ?? 0);
            $deskripsi = trim($item['deskripsi'] ?? '');
            $is_active = isset($item['is_active']) ? 1 : 0; // Tangkap status checkbox
            
            if ($bundle_id > 0 && $diskon > 0) {
                if ($bundle_id == $produk_id) {
                    $errors[] = 'Produk tidak bisa dibundle dengan dirinya sendiri';
                    break;
                }
                
                if (createBundling($produk_id, $bundle_id, $diskon, $deskripsi, $is_active)) {
                    $success_count++;
                }
            }
        }
        
        if (empty($errors) && $success_count > 0) {
            setMessage("Berhasil menyimpan {$success_count} bundling produk", 'success');
            redirect('index.php');
        } elseif (empty($errors)) {
            setMessage('Tidak ada bundling yang disimpan', 'warning');
        }
    }
}

$products = getAllProducts();
?>

<div class="main-content dashboard-wrapper">
    <div class="form-container" style="max-width: 1100px;">
        
        <div class="dash-header mb-4">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Bundling
                </a>
                <h1 class="dash-title mt-2">Kelola Bundling Produk</h1>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: var(--danger-color);">
                <ul class="mb-0 text-danger fw-bold" style="font-size: 0.9rem; list-style-type: none; padding-left: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fas fa-exclamation-circle me-2"></i><?= clean($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="bundlingForm">
            <input type="hidden" name="produk_id" value="<?= $produk_id ?>">
            
            <div class="row g-4">
                <div class="col-lg-7">
                    
                    <?php if (!empty($existingBundlings)): ?>
                        <div class="alert alert-editorial mb-4" style="border-left-color: var(--info-color); background: #EFF6FF;">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            <span class="text-dark" style="font-size: 0.9rem;">
                                Saat ini ada <strong><?= count($existingBundlings) ?> varian bundling</strong> yang terpasang. Form di bawah ini akan <strong>menimpa (mereplace)</strong> seluruh data yang ada.
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                        <label class="form-label mb-0" style="font-size: 0.9rem;">Item Bundling Tambahan <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" onclick="addBundlingItem()" id="addBtn" style="transition: var(--transition);">
                            <i class="fas fa-plus me-1"></i> Tambah Produk
                        </button>
                    </div>

                    <div id="bundlingContainer">
                        <small class="text-muted d-block mb-3 ms-2">Maksimal 5 produk. Kosongkan (hapus semua item) jika tidak ingin menggunakan fitur bundling lagi.</small>
                    </div>

                </div>

                <div class="col-lg-5">
                    
                    <div class="panel-editorial mb-4" style="background: linear-gradient(145deg, #111827 0%, #1F2937 100%); color: white; border: none;">
                        <h3 class="panel-title text-white" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem; opacity: 0.8;">
                            <i class="fas fa-star text-warning"></i> Produk Basis
                        </h3>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                                <i class="fas fa-box-open text-white"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size: 1.1rem; line-height: 1.2; margin-bottom: 0.2rem;"><?= clean($mainProduct['nama']) ?></div>
                                <div style="color: #34D399; font-weight: 800; font-size: 1.15rem;"><?= formatCurrency($mainProduct['harga']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div id="totalPreview" class="panel-editorial p-4 mb-4" style="display:none; border-color: var(--success-color); background: #ECFDF5; @starting-style { opacity: 0; transform: scale(0.97); } transition: opacity 200ms var(--ease-out), transform 200ms var(--ease-out);">
                        <h3 class="panel-title text-success" style="font-size: 1rem; margin-bottom: 1.25rem;">
                            <i class="fas fa-calculator me-2"></i> Ringkasan Harga Paket Baru
                        </h3>
                        <div id="totalContent" class="text-dark" style="font-size: 0.9rem;"></div>
                    </div>

                    <?php if (!empty($existingBundlings)): ?>
                    <div class="panel-editorial p-4 mb-4" style="background: #FFFBEB; border-color: #FDE68A;">
                        <h3 class="panel-title text-dark" style="font-size: 1rem;"><i class="fas fa-history text-warning"></i> Status Bundling Lama</h3>
                        
                        <div class="d-flex flex-column gap-3 mt-3">
                            <?php foreach ($existingBundlings as $bundle): ?>
                                <div class="d-flex justify-content-between align-items-start pb-2 border-bottom border-warning" style="border-bottom-style: dashed !important; border-opacity: 0.3;">
                                    <div class="pe-3">
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem; line-height: 1.3;">
                                            <?= clean($bundle['nama']) ?>
                                            <?php if(isset($bundle['is_active']) && $bundle['is_active'] == 0): ?>
                                                <span class="badge bg-secondary ms-1" style="font-size: 0.6rem;">Non-aktif</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($bundle['deskripsi'])): ?>
                                            <div class="text-muted mt-1" style="font-size: 0.75rem; line-height: 1.4;">
                                                <?= clean(implode(' ', array_slice(explode(' ', strip_tags($bundle['deskripsi'])), 0, 10))) ?>...
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-danger fw-bold mt-1" style="font-size: 0.8rem;"><i class="fas fa-tag me-1"></i> Diskon: <?= formatDiscount($bundle['diskon']) ?></div>
                                    </div>
                                    <div class="text-end fw-bold text-dark" style="font-size: 0.9rem;">
                                        <?= formatCurrency($bundle['harga']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 pt-2 text-center text-dark fw-bold" style="font-size: 0.85rem;">
                            Total Diskon Lama: <span class="text-danger"><?= formatCurrency(array_sum(array_column($existingBundlings, 'diskon'))) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex flex-column gap-2 mt-4">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i> Update Perubahan
                        </button>
                        <a href="index.php" class="btn-cancel">Batal Edit</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let bundlingCount = 0;
const maxBundling = 5;
let allProducts = <?= json_encode($products) ?>;
let mainProductId = <?= $produk_id ?>;
let existingBundlings = <?= json_encode($existingBundlings) ?>;
const customEaseOut = 'cubic-bezier(0.23, 1, 0.32, 1)';

document.addEventListener('DOMContentLoaded', function() {
    // Auto-populate based on existing PHP data
    if (existingBundlings.length > 0) {
        existingBundlings.forEach(bundle => {
            addBundlingItem(bundle.produk_bundling_id, bundle.diskon, bundle.deskripsi, bundle.is_active);
        });
    } else {
        addBundlingItem();
    }
    updateTotalPreview();
});

function addBundlingItem(selectedProductId = null, selectedDiskon = null, selectedDeskripsi = null, selectedIsActive = 1) {
    const currentItems = document.querySelectorAll('[id^="bundlingItem"]').length;
    if (currentItems >= maxBundling) return;

    bundlingCount++;
    const container = document.getElementById('bundlingContainer');
    const escapedDeskripsi = selectedDeskripsi ? <?= json_encode('') ?> + selectedDeskripsi : '';

    const itemHtml = `
        <div class="panel-editorial p-4 mb-3 position-relative" id="bundlingItem${bundlingCount}" style="opacity: 1; transform: translateY(0); transition: opacity 200ms \${customEaseOut}, transform 200ms \${customEaseOut};">
            
            <style>
                @starting-style {
                    #bundlingItem${bundlingCount} { opacity: 0 !important; transform: translateY(10px) !important; }
                    #itemPreview${bundlingCount} { opacity: 0 !important; transform: scale(0.97) !important; }
                }
            </style>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted fw-bold" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Varian Paket #${bundlingCount}</span>
                <button type="button" class="btn-action-icon delete" style="width:32px; height:32px; background:#FEF2F2; color:#EF4444;" onclick="removeBundlingItem(${bundlingCount})" title="Hapus Item">
                    <i class="fas fa-trash-alt" style="font-size:0.85rem;"></i>
                </button>
            </div>
            
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Produk Bundling</label>
                    <select name="bundling[${bundlingCount}][produk_id]" class="form-control-editorial bundling-product" data-index="${bundlingCount}" required style="appearance: auto;">
                        <option value="">Pilih Produk Tambahan</option>
                        ${allProducts.map(p => {
                            if (p.id == mainProductId) return '';
                            const selected = (selectedProductId && p.id == selectedProductId) ? 'selected' : '';
                            return `<option value="${p.id}" data-harga="${p.harga}" ${selected}>${p.nama} — ${formatRupiahJS(p.harga)}</option>`;
                        }).join('')}
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Potongan Diskon</label>
                    <div class="input-group-editorial">
                        <span class="addon">Rp</span>
                        <input type="number" name="bundling[${bundlingCount}][diskon]" class="form-control-editorial bundling-diskon" 
                               data-index="${bundlingCount}" min="0" step="1000" placeholder="0" required 
                               value="${selectedDiskon || ''}">
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Deskripsi Item Paket</label>
                <textarea name="bundling[${bundlingCount}][deskripsi]" class="form-control-editorial" style="min-height: 70px;" 
                          placeholder="Contoh: Ebook panduan praktis...">${selectedDeskripsi ? cleanJs(selectedDeskripsi) : ''}</textarea>
            </div>

            <label class="toggle-switch mt-3" style="margin-bottom: 0; padding: 0.75rem 1rem;">
                <div>
                    <div class="toggle-label" style="font-size: 0.85rem;">Status Bundling</div>
                    <div class="toggle-desc" style="font-size: 0.7rem;">Tampilkan di halaman checkout</div>
                </div>
                <input type="checkbox" name="bundling[${bundlingCount}][is_active]" value="1" class="switch-input" 
                       ${selectedIsActive == 1 ? 'checked' : ''}>
                <div class="switch-slider"></div>
            </label>

            <div class="mt-3" id="itemPreview${bundlingCount}" style="display:none; transition: opacity 150ms \${customEaseOut}, transform 150ms \${customEaseOut};">
                <div class="p-3 rounded-3" style="background: #F9FAFB; border: 1px dashed var(--border-light);">
                    <div id="itemPreviewContent${bundlingCount}" style="font-size: 0.85rem;"></div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', itemHtml);

    const productSelect = document.querySelector(`[name="bundling[${bundlingCount}][produk_id]"]`);
    const diskonInput = document.querySelector(`[name="bundling[${bundlingCount}][diskon]"]`);

    productSelect.addEventListener('change', function() {
        updateAvailableProducts();
        updateItemPreview(this.dataset.index);
        updateTotalPreview();
    });

    diskonInput.addEventListener('input', function() {
        updateItemPreview(this.dataset.index);
        updateTotalPreview();
    });

    updateAvailableProducts();
    updateAddButton();

    if (selectedProductId && selectedDiskon) {
        updateItemPreview(bundlingCount);
    }
}

function removeBundlingItem(index) {
    const item = document.getElementById(`bundlingItem${index}`);
    item.style.opacity = '0';
    item.style.transform = 'translateY(-8px)';
    
    setTimeout(() => {
        item.remove();
        updateAvailableProducts();
        updateTotalPreview();
        updateAddButton();
    }, 150);
}

function updateAvailableProducts() {
    const selected = [mainProductId];
    document.querySelectorAll('.bundling-product').forEach(select => {
        if (select.value) selected.push(select.value);
    });

    document.querySelectorAll('.bundling-product').forEach(select => {
        const currentVal = select.value;
        select.querySelectorAll('option').forEach(option => {
            if (option.value === '') return;
            option.disabled = (selected.includes(option.value) && option.value !== currentVal);
        });
    });
}

function updateItemPreview(index) {
    const productSelect = document.querySelector(`[name="bundling[${index}][produk_id]"]`);
    const diskonInput = document.querySelector(`[name="bundling[${index}][diskon]"]`);
    const preview = document.getElementById(`itemPreview${index}`);
    const content = document.getElementById(`itemPreviewContent${index}`);

    if (productSelect.value && diskonInput.value >= 0) {
        const harga = parseInt(productSelect.options[productSelect.selectedIndex].dataset.harga);
        const diskon = parseInt(diskonInput.value) || 0;
        const final = Math.max(0, harga - diskon);

        content.innerHTML = `
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted fw-medium">Harga Normal:</span>
                <span class="text-dark fw-bold">${formatRupiahJS(harga)}</span>
            </div>
            <div class="d-flex justify-content-between mb-1 text-danger">
                <span class="fw-medium">Diskon Paket:</span>
                <span class="fw-bold">-${formatRupiahJS(diskon)}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold text-success border-top pt-1 mt-1">
                <span>Harga Setelah Gabung:</span>
                <span>${formatRupiahJS(final)}</span>
            </div>
        `;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

function updateTotalPreview() {
    const totalPreview = document.getElementById('totalPreview');
    const totalContent = document.getElementById('totalContent');
    const mainPrice = <?= $mainProduct['harga'] ?>;
    
    let totalNormal = mainPrice;
    let totalDiskon = 0;
    let itemsBundled = [];

    document.querySelectorAll('.bundling-product').forEach(select => {
        if (select.value) {
            const idx = select.dataset.index;
            const diskonInput = document.querySelector(`[name="bundling[${idx}][diskon]"]`);
            const price = parseInt(select.options[select.selectedIndex].dataset.harga);
            const diskon = parseInt(diskonInput.value) || 0;

            totalNormal += price;
            totalDiskon += diskon;
            itemsBundled.push({
                name: select.options[select.selectedIndex].text.split(' — ')[0],
                final: Math.max(0, price - diskon)
            });
        }
    });

    if (itemsBundled.length > 0) {
        const totalFinal = totalNormal - totalDiskon;

        let html = `
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom" style="border-bottom-style: dashed !important;">
                <span class="fw-bold">1. <?= clean($mainProduct['nama']) ?></span>
                <span class="fw-bold">${formatRupiahJS(mainPrice)}</span>
            </div>
        `;

        itemsBundled.forEach((item, i) => {
            html += `
                <div class="d-flex justify-content-between mb-1 opacity-85">
                    <span>${i + 2}. ${item.name}</span>
                    <span>${formatRupiahJS(item.final)}</span>
                </div>
            `;
        });

        html += `
            <div class="mt-3 pt-3 border-top d-flex flex-column gap-1" style="font-size: 0.85rem; color: var(--secondary-color);">
                <div class="d-flex justify-content-between">
                    <span>Total Normal Gabungan</span>
                    <span>${formatRupiahJS(totalNormal)}</span>
                </div>
                <div class="d-flex justify-content-between text-danger fw-bold">
                    <span>Total Diskon</span>
                    <span>-${formatRupiahJS(totalDiskon)}</span>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 text-success" style="border-top: 2px solid rgba(16, 185, 129, 0.25);">
                <span class="h6 mb-0 fw-bold">Harga Final Paket</span>
                <span class="h4 mb-0 fw-extrabold" style="font-weight:800;">${formatRupiahJS(totalFinal)}</span>
            </div>
        `;

        totalContent.innerHTML = html;
        totalPreview.style.display = 'block';
    } else {
        totalPreview.style.display = 'none';
    }
}

function updateAddButton() {
    const addBtn = document.getElementById('addBtn');
    const currentItems = document.querySelectorAll('[id^="bundlingItem"]').length;
    
    if (currentItems >= maxBundling) {
        addBtn.disabled = true;
        addBtn.className = 'btn btn-sm btn-light text-muted rounded-pill px-3';
        addBtn.innerHTML = '<i class="fas fa-check-circle text-muted me-1"></i>Maksimal 5';
    } else {
        addBtn.disabled = false;
        addBtn.className = 'btn btn-dark btn-sm rounded-pill px-3';
        addBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Tambah Produk';
    }
}

function formatRupiahJS(amount) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
}

function cleanJs(str) {
    if (!str) return '';
    return str.replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

document.getElementById('bundlingForm').addEventListener('submit', function() {
    const submitBtn = this.querySelector('.btn-submit');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        submitBtn.style.pointerEvents = 'none';
        submitBtn.style.opacity = '0.8';
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>