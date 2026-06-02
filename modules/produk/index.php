<?php
// === LOGIC SECTION (No Output) ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$page_title = "Kelola Produk";

// Mengambil semua produk tanpa pagination
$produk_list = getAllProduk(); // Sekarang akan mengambil semua data
$total_records = count($produk_list);

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper flex-grow-1">
    
    <div class="dash-header mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-box-open text-primary"></i> Katalog Produk
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">
                Kelola daftar produk, harga, dan *embed code* kasir untuk websitemu.
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="dash-date d-none d-sm-flex align-items-center gap-2 px-3 py-2 bg-white border rounded-pill shadow-sm">
                <div style="width: 8px; height: 8px; background: #3B82F6; border-radius: 50%; box-shadow: 0 0 0 3px #DBEAFE;"></div>
                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><?= $total_records ?> Produk Aktif</span>
            </div>
            <a href="create.php" class="btn btn-dark fw-bold rounded-pill px-4" style="box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);">
                <i class="fas fa-plus me-2"></i> Tambah Produk
            </a>
        </div>
    </div>

    <div class="panel-editorial p-0 overflow-hidden mb-5">
        <?php if (empty($produk_list)): ?>
            <div class="text-center py-5">
                <div style="width: 100px; height: 100px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; position: relative;">
                    <i class="fas fa-box-open text-muted" style="font-size: 2.5rem;"></i>
                    <i class="fas fa-sparkles text-warning position-absolute" style="top: -5px; right: -5px; font-size: 1.5rem;"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Katalog Masih Kosong</h4>
                <p class="text-muted mb-4 mx-auto" style="max-width: 400px; line-height: 1.6;">Kamu belum menambahkan produk apa pun. Tambahkan produk pertamamu untuk mulai menerima pesanan dan cuan!</p>
                <a href="create.php" class="btn btn-primary rounded-pill fw-bold px-5 py-3" style="box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);">
                    <i class="fas fa-plus me-2"></i>Buat Produk Pertama
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-editorial mb-0">
                    <thead>
                        <tr>
                            <th width="8%" class="text-center">ID</th>
                            <th width="35%">Nama & Info Produk</th>
                            <th width="15%">Harga Jual</th>
                            <th width="15%">Profit Bersih</th>
                            <th width="15%">Routing System</th>
                            <th width="12%" class="text-end pe-4">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produk_list as $produk): ?>
                        <tr>
                            <td class="text-center">
                                <span class="badge-clean bg-light text-muted border fw-bold" style="font-family: monospace;">#<?= $produk['id'] ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width: 48px; height: 48px; border-radius: 12px; background: #F3F4F6; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: #4B5563; flex-shrink: 0;">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem; line-height: 1.2;"><?= clean($produk['nama']) ?></div>
                                        <?php if ($produk['deskripsi']): ?>
                                            <div class="text-muted text-truncate" style="font-size: 0.75rem; max-width: 250px;" title="<?= htmlspecialchars($produk['deskripsi']) ?>">
                                                <?= clean($produk['deskripsi']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 1rem;"><?= formatCurrency($produk['harga']) ?></div>
                            </td>
                            <td>
                                <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">
                                    <i class="fas fa-coins me-1"></i> <?= formatCurrency($produk['profit']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-2 align-items-start">
                                    <?php if ($produk['admin_wa']): ?>
                                        <a href="<?= whatsappLink($produk['admin_wa']) ?>" target="_blank" class="badge-clean bg-light border text-decoration-none" style="color: #059669; transition: all 0.2s;" onmouseover="this.style.background='#ECFDF5'" onmouseout="this.style.background='#F8FAFC'">
                                            <i class="fab fa-whatsapp"></i> <?= clean($produk['admin_wa']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge-clean bg-light text-muted border opacity-50"><i class="fas fa-phone-slash me-1"></i> WA Kosong</span>
                                    <?php endif; ?>
                                    
                                    <span class="badge-clean bg-light border text-primary" title="Gateway Pengirim">
                                        <i class="fas fa-satellite-dish me-1"></i> <?= clean($produk['onesender_account']) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= BASE_URL ?>co.php?id=<?= $produk['id'] ?>" class="btn-action-icon checkout" title="Buka Halaman Checkout" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>

                                    <?php
                                    $bundling_data = fetchAll("
                                        SELECT b.id, b.deskripsi as deskripsi_bundling, p.nama, p.harga, b.diskon 
                                        FROM bundling b 
                                        JOIN produk p ON b.produk_bundling_id = p.id 
                                        WHERE b.produk_id = ? AND b.is_active = 1
                                    ", [$produk['id']]);
                                    $bundling_json = htmlspecialchars(json_encode($bundling_data), ENT_QUOTES, 'UTF-8');
                                    ?>

                                    <button type="button" class="btn-action-icon embed" 
                                            title="Salin HTML Embed Kasir"
                                            data-bundling="<?= $bundling_json ?>"
                                            onclick="copyEmbedCode(
                                                <?= $produk['id'] ?>, 
                                                '<?= addslashes(clean($produk['nama'])) ?>', 
                                                <?= $produk['harga'] ?>, 
                                                <?= isset($produk['show_email']) && $produk['show_email'] == 1 ? 1 : 0 ?>, 
                                                <?= isset($produk['show_kupon']) && $produk['show_kupon'] == 1 ? 1 : 0 ?>, 
                                                this
                                            )">
                                        <i class="fas fa-code"></i>
                                    </button>

                                    <a href="edit.php?id=<?= $produk['id'] ?>" class="btn-action-icon edit" title="Edit Produk">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    
                                    <button type="button" class="btn-action-icon delete" title="Hapus Permanen"
                                            onclick="showDeleteModal(<?= $produk['id'] ?>, '<?= addslashes(clean($produk['nama'])) ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
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

<script>
// Fungsi copy embed JS
function copyEmbedCode(produkId, namaProduk, hargaRaw, showEmail, showKupon, btnElement) {
    const formatRp = (angka) => 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
    const bundlingJsonStr = btnElement.getAttribute('data-bundling');
    
    let dataBundling = [];
    try {
        dataBundling = JSON.parse(bundlingJsonStr || "[]");
    } catch(e) { console.error("Gagal parse JSON Bundling"); }

    let htmlCode = `<div id="edu-embed-container-${produkId}" style="background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e0e0e0; max-width: 450px; margin: 0 auto; box-shadow: 0 8px 25px rgba(0,0,0,0.05); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    
    <h5 style="color: #333; margin-bottom: 5px; font-weight: bold; font-size: 18px;">Lengkapi Data:</h5>
    <hr style="border: 0; border-top: 1px dashed #ccc; margin-bottom: 20px;">
    
    <form action="https://edumuslim.my.id/proses_embed.php" method="POST" id="form-embed-${produkId}">
        <input type="hidden" name="produk_id" value="${produkId}">
        <input type="hidden" name="kupon_id" id="kupon_id_${produkId}" value="">
        <input type="hidden" name="total_diskon" id="total_diskon_${produkId}" value="0">
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; color: #444;">Nama Lengkap *</label>
            <input type="text" name="nama" required placeholder="Ketik nama Anda" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; box-sizing: border-box; outline: none;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; color: #444;">No. WhatsApp *</label>
            <input type="tel" name="nomor_wa" required placeholder="08123456789" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; box-sizing: border-box; outline: none;">
        </div>`;

    if (showEmail === 1) {
        htmlCode += `
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; color: #444;">Email Lengkap *</label>
            <input type="email" name="email" required placeholder="alamat@email.com" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; box-sizing: border-box; outline: none;">
        </div>`;
    }

    if (dataBundling.length > 0) {
        htmlCode += `
        <div style="margin-bottom: 25px; margin-top: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #444;">Penawaran Spesial (Opsional)</label>`;
        
        dataBundling.forEach(bndl => {
            let hargaDiskon = parseInt(bndl.harga) - parseInt(bndl.diskon);
            let namaAman = bndl.nama.replace(/"/g, '&quot;');
            let descRaw = bndl.deskripsi_bundling || '';
            let descShort = descRaw.length > 80 ? descRaw.substring(0, 80) + '...' : descRaw;
            let descFull = descRaw.replace(/\\n/g, '<br>');
            
            htmlCode += `
            <div style="border: 2px dashed #dc3545; background: #fffaf0; padding: 15px; border-radius: 8px; transition: all 0.2s; margin-bottom: 10px;">
                <label style="display: flex; align-items: flex-start; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="bundling_ids[]" value="${bndl.id}" class="bundle-chk-${produkId}" data-harga="${hargaDiskon}" data-nama="${namaAman}" style="margin-top: 4px; margin-right: 12px; width: 18px; height: 18px; accent-color: #FFA200;">
                    <div style="flex: 1;">
                        <div style="font-weight: bold; font-size: 15px; color: #333;">${bndl.nama}</div>`;
            
            if (descRaw.trim() !== '') {
                htmlCode += `<div style="font-size: 12px; color: #666; margin-top: 5px; line-height: 1.5;">`;
                if (descRaw.length > 80) {
                    htmlCode += `<span class="desc-short-${produkId}-${bndl.id}">${descShort}</span>
                    <span class="desc-full-${produkId}-${bndl.id}" style="display:none;">${descFull}</span>
                    <a href="javascript:void(0)" class="btn-toggle-desc-${produkId}" data-target="${bndl.id}" style="color: #0d6efd; text-decoration: none; font-weight: bold; margin-left: 3px;">Selengkapnya</a>`;
                } else {
                    htmlCode += `${descFull}`;
                }
                htmlCode += `</div>`;
            }

            htmlCode += `
                        <div style="font-size: 14px; color: #dc3545; font-weight: bold; margin-top: 6px;">+ Rp ${new Intl.NumberFormat('id-ID').format(hargaDiskon)}</div>
                    </div>
                </label>
            </div>`;
        });
        htmlCode += `</div>`;
    }

    htmlCode += `
        <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 18px; margin-bottom: 20px;">
            <div style="font-weight: bold; font-size: 15px; margin-bottom: 12px; color: #333;">Ringkasan Pesanan</div>
            
            <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: #555;">
                <span>${namaProduk}</span>
                <span>${formatRp(hargaRaw)}</span>
            </div>
            
            <div id="summary-bundle-${produkId}"></div>
            <div id="summary-kupon-${produkId}"></div>
            
            <hr style="border-top: 1px dashed #ccc; margin: 12px 0;">
            
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px; color: #FFA200;">
                <span>Total Bayar</span>
                <span id="total-price-${produkId}">${formatRp(hargaRaw)}</span>
            </div>
        </div>`;

    if (showKupon === 1) {
        htmlCode += `
        <div style="margin-bottom: 25px;">
            <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; color: #444;">Punya Kode Diskon?</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" id="input_kode_${produkId}" placeholder="Ketik kode di sini" style="flex: 1; padding: 12px; border: 1px dashed #FFA200; border-radius: 8px; font-size: 15px; text-transform: uppercase; outline: none;">
                <button type="button" id="btn_kupon_${produkId}" style="background: #333; color: #fff; border: none; padding: 0 20px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold;">Terapkan</button>
            </div>
            <div id="msg_kupon_${produkId}" style="font-size: 13px; margin-top: 8px;"></div>
        </div>`;
    }

    htmlCode += `
        <button type="submit" id="btn_submit_${produkId}" style="width: 100%; background: linear-gradient(135deg, #ffbc3b, #FFA200); color: white; padding: 16px; font-size: 16px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 6px 20px rgba(255,162,0,0.3); transition: transform 0.2s;">
            Proses Pesanan Sekarang <span style="font-size:18px; margin-left:5px;">&rarr;</span>
        </button>
        
        <div style="text-align: center; font-size: 12px; color: #888; margin-top: 15px;">
            <span style="color:#28a745;">&#128274;</span> Data Anda dilindungi oleh enkripsi 256-bit.
        </div>
    </form>
    
    \x3Cscript>
        (function(){
            const hargaUtama = ${hargaRaw};
            let diskonAktif = 0;
            
            const form = document.getElementById('form-embed-${produkId}');
            const checkboxes = form.querySelectorAll('.bundle-chk-${produkId}');
            const summaryBundle = document.getElementById('summary-bundle-${produkId}');
            const summaryKupon = document.getElementById('summary-kupon-${produkId}');
            const totalEl = document.getElementById('total-price-${produkId}');
            
            const toggles = document.querySelectorAll('.btn-toggle-desc-${produkId}');
            toggles.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('data-target');
                    const shortDesc = document.querySelector('.desc-short-${produkId}-' + targetId);
                    const fullDesc = document.querySelector('.desc-full-${produkId}-' + targetId);
                    
                    if (fullDesc.style.display === 'none') {
                        fullDesc.style.display = 'inline';
                        shortDesc.style.display = 'none';
                        this.innerText = ' Sembunyikan';
                    } else {
                        fullDesc.style.display = 'none';
                        shortDesc.style.display = 'inline';
                        this.innerText = ' Selengkapnya';
                    }
                });
            });
            
            function formatUang(angka) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
            }
            
            function hitungTotal() {
                let total = hargaUtama;
                summaryBundle.innerHTML = ''; 
                
                checkboxes.forEach(chk => {
                    if(chk.checked) {
                        let hrg = parseInt(chk.getAttribute('data-harga'));
                        let nm = chk.getAttribute('data-nama');
                        total += hrg;
                        summaryBundle.innerHTML += '<div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: #198754;"><span>+ ' + nm + '</span><span>' + formatUang(hrg) + '</span></div>';
                        chk.closest('div[style*="border"]').style.borderColor = '#198754';
                        chk.closest('div[style*="border"]').style.backgroundColor = '#f3f8ff';
                    } else {
                        chk.closest('div[style*="border"]').style.borderColor = '#dc3545';
                        chk.closest('div[style*="border"]').style.backgroundColor = '#fffaf0';
                    }
                });
                
                if(diskonAktif > 0) {
                    total -= diskonAktif;
                    if(total < 0) total = 0;
                    summaryKupon.innerHTML = '<div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: #dc3545; font-weight: bold;"><span>- Diskon Promo</span><span>- ' + formatUang(diskonAktif) + '</span></div>';
                } else {
                    summaryKupon.innerHTML = '';
                }
                
                totalEl.innerText = formatUang(total);
            }
            
            checkboxes.forEach(chk => chk.addEventListener('change', hitungTotal));
            
            ${showKupon === 1 ? `
            const btnKupon = document.getElementById('btn_kupon_${produkId}');
            btnKupon.addEventListener('click', function() {
                const kode = document.getElementById('input_kode_${produkId}').value.trim();
                const msg = document.getElementById('msg_kupon_${produkId}');
                
                if(kode === '') {
                    msg.innerHTML = '<span style="color:#dc3545;">Mohon masukkan kode diskon.</span>';
                    return;
                }
                
                msg.innerHTML = '<span style="color:#666;">Mengecek kode...</span>';
                
                const fd = new FormData();
                fd.append('kode_kupon', kode);
                fd.append('produk_id', '${produkId}');
                fd.append('total_harga', hargaUtama);
                
                fetch('https://edumuslim.my.id/api/cek_kupon.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        msg.innerHTML = '<span style="color:#198754; font-weight:bold;">&#10003; Kode berhasil diterapkan!</span>';
                        diskonAktif = parseFloat(data.potongan);
                        document.getElementById('kupon_id_${produkId}').value = data.kupon_id;
                        document.getElementById('total_diskon_${produkId}').value = diskonAktif;
                    } else {
                        msg.innerHTML = '<span style="color:#dc3545;">' + data.message + '</span>';
                        diskonAktif = 0;
                        document.getElementById('kupon_id_${produkId}').value = '';
                        document.getElementById('total_diskon_${produkId}').value = '0';
                    }
                    hitungTotal(); 
                }).catch(err => {
                    msg.innerHTML = '<span style="color:#dc3545;">Terjadi kesalahan jaringan.</span>';
                });
            });
            ` : ''}
            
            form.addEventListener('submit', function() {
                const btn = document.getElementById('btn_submit_${produkId}');
                btn.innerHTML = 'Memproses Pesanan...';
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none'; // Cegah dobel klik form embed
            });
        })();
    \x3C/script>
</div>`;

    navigator.clipboard.writeText(htmlCode).then(() => {
        const originalHTML = btnElement.innerHTML;
        const originalColor = btnElement.style.color;
        
        btnElement.innerHTML = '<i class="fas fa-check"></i>';
        btnElement.style.color = '#10B981';
        btnElement.style.borderColor = '#10B981';
        btnElement.style.background = '#ECFDF5';
        
        setTimeout(() => {
            btnElement.innerHTML = originalHTML;
            btnElement.style.color = originalColor;
            btnElement.style.borderColor = 'transparent';
            btnElement.style.background = 'transparent';
        }, 1500);
    }).catch(err => {
        alert('Gagal menyalin kode HTML: ' + err);
    });
}
</script>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-trash-alt" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Produk?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus produk <strong id="deleteProductName" class="text-dark"></strong> dari katalog? 
                    Ini mungkin merusak tautan Checkout yang sudah kamu bagikan.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal" style="border-radius: 12px;">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold" style="border-radius: 12px; background: #EF4444; border: none;">Ya, Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Logic Micro-interaction untuk Modal Delete
function showDeleteModal(id, namaProduk) {
    document.getElementById('deleteProductName').textContent = namaProduk;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
    this.style.opacity = '0.8';
    this.style.pointerEvents = 'none'; 
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>