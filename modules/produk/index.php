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

<div class="main-content">
    <!-- Top Header -->
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Kelola Produk</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Produk</li>
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
            <h2><i class="fas fa-box me-2"></i>Kelola Produk</h2>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tambah Produk
            </a>
        </div>
        
        <!-- Main Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Daftar Produk (<?= $total_records ?> produk)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($produk_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada produk</h5>
                        <p class="text-muted">Klik tombol "Tambah Produk" untuk menambahkan produk pertama.</p>
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Produk Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Tabel dengan scroll vertikal untuk menampilkan semua produk -->
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Nama Produk</th>
									<th>Profit</th>
                                    <th width="120">Harga</th>
                                    <th width="150">Admin WA</th>
                                    <th width="120">OneSender</th>
                                    <th width="130">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produk_list as $produk): ?>
                                <tr>
                                    <td class="fw-bold"><?= $produk['id'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= clean($produk['nama']) ?></div>
                                        <?php if ($produk['deskripsi']): ?>
                                            <small class="text-muted"><?= truncateText($produk['deskripsi'], 50) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= formatCurrency($produk['profit']) ?></td>
									<td class="fw-bold text-primary"><?= formatCurrency($produk['harga']) ?></td>
                                    <td>
                                        <?php if ($produk['admin_wa']): ?>
                                            <a href="<?= whatsappLink($produk['admin_wa']) ?>" target="_blank" class="text-success text-decoration-none">
                                                <i class="fab fa-whatsapp"></i> <?= clean($produk['admin_wa']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= clean($produk['onesender_account']) ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
											<a href="<?= BASE_URL ?>co.php?id=<?= $produk['id'] ?>" class="btn btn-outline-success" title="Checkout" target="_blank">
                                                <i class="fas fa-shopping-cart"></i>
                                            </a>

                                            <?php
                                            // Cari data bundling yang aktif untuk produk ini
                                            $bundling_data = fetchAll("
                                                SELECT b.id, b.deskripsi as deskripsi_bundling, p.nama, p.harga, b.diskon 
                                                FROM bundling b 
                                                JOIN produk p ON b.produk_bundling_id = p.id 
                                                WHERE b.produk_id = ? AND b.is_active = 1
                                            ", [$produk['id']]);

                                            // Ubah data menjadi JSON yang aman
                                            $bundling_json = htmlspecialchars(json_encode($bundling_data), ENT_QUOTES, 'UTF-8');
                                            ?>

                                            <button type="button" class="btn btn-outline-info" 
                                                    title="Copy HTML Embed"
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

                                            <a href="edit.php?id=<?= $produk['id'] ?>" 
                                               class="btn btn-outline-warning" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $produk['id'] ?>" 
                                               class="btn btn-outline-danger btn-delete" 
                                               title="Hapus"
                                               data-name="<?= clean($produk['nama']) ?>"
                                               onclick="return confirm('Yakin ingin menghapus produk \'<?= clean($produk['nama']) ?>\'?')">
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

<style>
/* CSS untuk header tabel yang tetap terlihat saat scroll */
.table-responsive thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}
</style>

<script>
function copyEmbedCode(produkId, namaProduk, hargaRaw, showEmail, showKupon, btnElement) {
    const formatRp = (angka) => 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
    
    // AMBIL DATA DARI ATRIBUT HTML (Jauh lebih aman dari error kutip/enter)
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

    // URUTAN 2: BUNDLING DINAMIS DARI DATABASE
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

    // URUTAN 3: RINGKASAN PESANAN
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
            
            // Logika Toggle Deskripsi Bundling
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
            });
        })();
    \x3C/script>
</div>`;

    navigator.clipboard.writeText(htmlCode).then(() => {
        const originalHTML = btnElement.innerHTML;
        const originalClass = btnElement.className;
        
        btnElement.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
        btnElement.className = 'btn btn-sm btn-success';
        
        setTimeout(() => {
            btnElement.innerHTML = originalHTML;
            btnElement.className = originalClass;
        }, 2000);
    }).catch(err => {
        alert('Gagal menyalin kode HTML: ' + err);
        console.error(err);
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>