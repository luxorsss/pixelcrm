<?php
$page_title = "Tambah Bundling";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/functions.php';

$errors = [];
$produk_id = (int)get('produk_id', '');
$bundling_items = [];

// Handle form submission
if (isPost()) {
    $produk_id = (int)post('produk_id');
    $bundling_data = post('bundling', []);
    $errors = [];

    if (empty($produk_id)) {
        $errors[] = 'Produk utama harus dipilih';
    }

    if (empty($bundling_data)) {
        $errors[] = 'Minimal 1 produk bundling harus dipilih';
    }

    $valid_items = [];
    if (!empty($bundling_data)) {
        foreach ($bundling_data as $item) {
            $bundle_id = (int)($item['produk_id'] ?? 0);
            $diskon = (int)($item['diskon'] ?? 0);
            $deskripsi = trim($item['deskripsi'] ?? '');

            if ($bundle_id > 0 && $diskon > 0) {
                if ($bundle_id == $produk_id) {
                    $errors[] = 'Produk tidak bisa dibundle dengan dirinya sendiri';
                    break;
                }

                if (bundlingExists($produk_id, $bundle_id)) {
                    $product = fetchRow("SELECT nama FROM produk WHERE id = ?", [$bundle_id]);
                    $errors[] = 'Bundling dengan produk "' . clean($product['nama'] ?? 'Unknown') . '" sudah ada';
                    break;
                }

                $valid_items[] = [
                    'produk_id' => $bundle_id,
                    'diskon' => $diskon,
                    'deskripsi' => $deskripsi
                ];
            }
        }
    }

    if (empty($errors) && !empty($valid_items)) {
        $success_count = 0;
        foreach ($valid_items as $item) {
            if (createBundling($produk_id, $item['produk_id'], $item['diskon'], $item['deskripsi'])) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            setMessage("Berhasil menambahkan {$success_count} bundling produk", 'success');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal menyimpan semua data bundling';
        }
    }

    $bundling_items = $bundling_data;
}

$products = getAllProducts();
?>

<div class="main-content dashboard-wrapper">
    <div class="form-container" style="max-width: 1100px;">
        
        <!-- Header -->
        <div class="dash-header mb-4">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Bundling
                </a>
                <h1 class="dash-title mt-2">Tambah Multiple Bundling</h1>
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
            <div class="row g-4">
                <!-- Kolom Kiri: Form & Item Dinamis -->
                <div class="col-lg-7">
                    <div class="panel-editorial">
                        <h3 class="panel-title"><i class="fas fa-star text-warning"></i> Produk Basis</h3>
                        
                        <div class="mb-2">
                            <label class="form-label">Produk Utama <span class="text-danger">*</span></label>
                            <select name="produk_id" class="form-control-editorial" required id="produkUtama" style="appearance: auto;">
                                <option value="">Pilih Produk Utama</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $produk_id == $p['id'] ? 'selected' : '' ?> data-harga="<?= $p['harga'] ?>">
                                        <?= clean($p['nama']) ?> — <?= formatCurrency($p['harga']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="text-muted mt-2" style="font-size: 0.75rem;">Pilih produk dasar yang akan digabungkan dengan paket bundling.</div>
                        </div>
                    </div>

                    <!-- Bundling Section Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3 mt-4 px-2">
                        <label class="form-label mb-0" style="font-size: 0.9rem;">Item Bundling Tambahan <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" onclick="addBundlingItem()" id="addBtn" style="transition: var(--transition);">
                            <i class="fas fa-plus me-1"></i> Tambah Produk
                        </button>
                    </div>

                    <!-- Container Elemen Dinamis -->
                    <div id="bundlingContainer">
                        <!-- Item JavaScript diinjeksi ke sini -->
                    </div>
                </div>

                <!-- Kolom Kanan: Live Preview & Tips -->
                <div class="col-lg-5">
                    <!-- Realtime Total Preview (Fintech Style) -->
                    <div id="totalPreview" class="panel-editorial p-4 mb-4" style="display:none; border-color: var(--success-color); background: #ECFDF5; @starting-style { opacity: 0; transform: scale(0.97); } transition: opacity 200ms var(--ease-out), transform 200ms var(--ease-out);">
                        <h3 class="panel-title text-success" style="font-size: 1rem; margin-bottom: 1.25rem;">
                            <i class="fas fa-calculator me-2"></i> Ringkasan Harga Paket
                        </h3>
                        <div id="totalContent" class="text-dark" style="font-size: 0.9rem;"></div>
                    </div>

                    <!-- Editorial Tips Panel -->
                    <div class="panel-editorial">
                        <h3 class="panel-title" style="font-size: 1rem; color: var(--primary-color);"><i class="fas fa-lightbulb text-warning"></i> Aturan Multiple Bundling</h3>
                        <ul class="list-unstyled mb-0" style="font-size: 0.85rem; color: var(--secondary-color); line-height: 1.6;">
                            <li class="mb-3 d-flex gap-2">
                                <i class="fas fa-check text-success mt-1"></i>
                                <span>Kamu bisa menambahkan maksimal <strong>5 produk</strong> pendukung ke dalam satu produk utama.</span>
                            </li>
                            <li class="mb-3 d-flex gap-2">
                                <i class="fas fa-check text-success mt-1"></i>
                                <span>Tentukan nominal potongan harga (diskon) dan tulis deskripsi benefit untuk memikat pembeli.</span>
                            </li>
                            <li class="mb-3 d-flex gap-2">
                                <i class="fas fa-check text-success mt-1"></i>
                                <span>Teks deskripsi akan muncul secara otomatis pada halaman checkout pelanggan.</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Global Form Actions -->
                    <div class="d-flex flex-column gap-2 mt-4">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i> Simpan Semua Bundling
                        </button>
                        <a href="index.php" class="btn-cancel">Batal</a>
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
const customEaseOut = 'cubic-bezier(0.23, 1, 0.32, 1)'; // Custom sharp ease-out curve

document.addEventListener('DOMContentLoaded', function() {
    addBundlingItem();
    document.getElementById('produkUtama').addEventListener('change', updateAllPreviews);
});

function addBundlingItem() {
    const currentItems = document.querySelectorAll('[id^="bundlingItem"]').length;
    if (currentItems >= maxBundling) return;

    bundlingCount++;
    const container = document.getElementById('bundlingContainer');

    // Desain HTML Item Dinamis bergaya Editorial Row dengan transisi Native CSS modern (@starting-style)
    const itemHtml = `
        <div class="panel-editorial p-4 mb-3 position-relative" id="bundlingItem${bundlingCount}" style="opacity: 1; transform: translateY(0); transition: opacity 200ms \${customEaseOut}, transform 200ms \${customEaseOut};">
            
            <!-- Tambahkan @starting-style inline untuk browser modern agar smooth fade-in -->
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
                        ${allProducts.map(p => `<option value="${p.id}" data-harga="${p.harga}">${p.nama} — ${formatRupiahJS(p.harga)}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Potongan Harga / Diskon</label>
                    <div class="input-group-editorial">
                        <span class="addon">Rp</span>
                        <input type="number" name="bundling[${bundlingCount}][diskon]" class="form-control-editorial bundling-diskon" 
                               data-index="${bundlingCount}" min="0" step="1000" placeholder="0" required>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Deskripsi Item Paket</label>
                <textarea name="bundling[${bundlingCount}][deskripsi]" class="form-control-editorial" style="min-height: 70px;" 
                          placeholder="Contoh: Akses eksklusif grup koordinasi dan file pelengkap..." maxlength="255"></textarea>
            </div>

            <!-- Single Item Preview Dynamic Card -->
            <div class="mt-3" id="itemPreview${bundlingCount}" style="display:none; transition: opacity 150ms \${customEaseOut}, transform 150ms \${customEaseOut};">
                <div class="p-3 rounded-3" style="background: #F9FAFB; border: 1px dashed var(--border-light);">
                    <div id="itemPreviewContent${bundlingCount}" style="font-size: 0.85rem;"></div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', itemHtml);

    // Bind Event Listeners instan tanpa delay jQuery
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
}

function removeBundlingItem(index) {
    const item = document.getElementById(`bundlingItem${index}`);
    // Animasikan keluar (fade out + subtle scale down) sebelum dihapus dari DOM
    item.style.opacity = '0';
    item.style.transform = 'translateY(-8px)';
    
    setTimeout(() => {
        item.remove();
        updateAvailableProducts();
        updateTotalPreview();
        updateAddButton();
    }, 150); // Eksekusi secepat 150ms
}

function updateAvailableProducts() {
    const mainProductVal = document.getElementById('produkUtama').value;
    const selected = [mainProductVal];

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
                <span class="text-muted fw-medium">Harga Normal Varian:</span>
                <span class="text-dark fw-bold">${formatRupiahJS(harga)}</span>
            </div>
            <div class="d-flex justify-content-between mb-1 text-danger">
                <span class="fw-medium">Potongan Diskon Paket:</span>
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
    const mainProduct = document.getElementById('produkUtama');
    const totalPreview = document.getElementById('totalPreview');
    const totalContent = document.getElementById('totalContent');

    if (!mainProduct.value) {
        totalPreview.style.display = 'none';
        return;
    }

    const mainPrice = parseInt(mainProduct.options[mainProduct.selectedIndex].dataset.harga);
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
                <span class="fw-bold">1. ${mainProduct.options[mainProduct.selectedIndex].text.split(' — ')[0]}</span>
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
                    <span>Total Hemat Pembeli</span>
                    <span>-${formatRupiahJS(totalDiskon)}</span>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 text-success" style="border-top: 2px solid rgba(16, 185, 129, 0.25);">
                <span class="h6 mb-0 fw-bold">Harga Paket Finis</span>
                <span class="h4 mb-0 fw-extrabold" style="font-weight:800;">${formatRupiahJS(totalFinal)}</span>
            </div>
        `;

        totalContent.innerHTML = html;
        totalPreview.style.display = 'block';
    } else {
        totalPreview.style.display = 'none';
    }
}

function updateAllPreviews() {
    updateAvailableProducts();
    document.querySelectorAll('.bundling-product').forEach(select => {
        if (select.dataset.index) {
            updateItemPreview(parseInt(select.dataset.index));
        }
    });
    updateTotalPreview();
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
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Efek feedback active state saat tombol kirim ditekan
document.getElementById('bundlingForm').addEventListener('submit', function() {
    const submitBtn = this.querySelector('.btn-submit');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan Paket...';
        submitBtn.style.pointerEvents = 'none';
        submitBtn.style.opacity = '0.8';
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>