<?php
// === LOGIC SECTION ===
require_once __DIR__ . '/../../includes/init.php';

$page_title = "Broadcast Segmentasi";

// Ambil data menggunakan fungsi database bawaan PixelCRM
$produk_list = fetchAll("SELECT id, nama FROM produk ORDER BY nama");
$wa_accounts = fetchAll("SELECT id, account_name FROM onesender_config ORDER BY account_name");
// Tambahkan proteksi ini agar tidak error jika tabel kosong:
if (!$wa_accounts) {
    $wa_accounts = []; 
}

$produk_json = json_encode(array_map(fn($p) => ['id' => (int)$p['id'], 'text' => $p['nama']], $produk_list));

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
    :root {
      --champ:   #fff9c4;  
      --loyal:   #bbdefb;  
      --prime:   #ffecb3;  
      --new:     #c8e6c9;  
      --risk:    #f8bbd0;  
      --cold:    #d7ccc8;  
      --whale:   #e1bee7;  
      --others:  #eeeeee;  
    }
    /* Sisanya disesuaikan agar tidak bertabrakan dengan Bootstrap PixelCRM */
    .segment-checkboxes { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
    .segment-item {
      padding: 8px 18px; border-radius: 50px; color: #212529; font-size: 13px; font-weight: 600;
      cursor: pointer; user-select: none; transition: all 0.2s; border: 2px solid transparent; position: relative;
    }
    .segment-item:hover { transform: scale(1.03); }
    .segment-item.active { border-color: #4361ee; box-shadow: 0 2px 6px rgba(67, 97, 238, 0.2); }
    .segment-item.champ { background-color: var(--champ); }
    .segment-item.loyal { background-color: var(--loyal); }
    .segment-item.prime { background-color: var(--prime); }
    .segment-item.new { background-color: var(--new); }
    .segment-item.risk { background-color: var(--risk); }
    .segment-item.cold { background-color: var(--cold); }
    .segment-item.whale { background-color: var(--whale); }
    .segment-item.others { background-color: var(--others); }
    
    .segment-item[data-tooltip]::after {
      content: attr(data-tooltip); position: absolute; left: 50%; bottom: 130%; transform: translateX(-50%);
      background: rgba(33, 37, 41, 0.95); color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 11px;
      white-space: nowrap; opacity: 0; pointer-events: none; transition: 0.15s ease; z-index: 20;
    }
    .segment-item[data-tooltip]:hover::after { opacity: 1; }    

    .multi-select-container { position: relative; width: 100%; }
    .multi-select-input { width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #ced4da; border-radius: 8px; }
    .multi-select-input::before { content: "🔍"; position: absolute; left: 10px; top: 50%; transform: translateY(-50%); }
    .multi-select-tags { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px; }
    .tag { background: #4361ee; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; display: flex; gap: 6px; }
    .tag-remove { cursor: pointer; font-weight: bold; }
    .dropdown-list { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ced4da; max-height: 200px; overflow-y: auto; z-index: 10; display: none; }
    .dropdown-item { padding: 10px; cursor: pointer; font-size: 14px; }
    .dropdown-item:hover { background: #f8f9fa; }
    .dropdown-item.selected { background: #e9ecef; }
    .preview-container { max-height: 320px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 8px; }
    .radio-option { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; cursor: pointer; margin-right: 15px;}
</style>

<div class="main-content">
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-bullhorn text-primary me-2"></i>Kirim Broadcast WA</h1>
                <p class="text-muted mb-0">Segmentasi berdasarkan RFM dan riwayat transaksi</p>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <form method="POST" action="proses_kirim.php" id="formBroadcast">
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fas fa-edit me-2 text-primary"></i>Konten Pesan</h6></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="radio-option">
                                    <input type="radio" name="tipe_pesan" value="text" checked> <i class="fas fa-comment"></i> Teks
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="tipe_pesan" value="image"> <i class="fas fa-image"></i> Gambar
                                </label>
                            </div>
                            <div id="pesanTeks">
                                <textarea class="form-control" name="pesan" rows="5" placeholder="Hai [nama], terima kasih sudah berlangganan! 💙" required></textarea>
                            </div>
                            <div id="pesanGambar" style="display:none;">
                                <input type="url" class="form-control mb-2" name="link_gambar" placeholder="https://example.com/gambar-promo.jpg">
                                <textarea class="form-control" name="caption" rows="4" placeholder="Hai [nama], ini hadiah spesial buat kamu! 🎁"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-success"></i>Segmentasi RFM</h6></div>
                        <div class="card-body">
                            <div class="segment-checkboxes" id="segmentContainer">
                                <?php 
                                $segments = [
                                  'Champ'  => 'Sering beli, nilai tinggi, aktif.',
                                  'Prime'  => 'Baru beli tapi langsung spend lumayan.',                
                                  'Loyal'  => 'Sudah repeat 2x, masih lumayan baru.',
                                  'Whale'  => 'Total belanja sangat besar.',
                                  'Risk'   => 'Dulu sering beli, sekarang mulai jarang.',
                                  'New'    => 'Baru sekali beli, nilai kecil.',
                                  'Cold'   => 'Sekali beli, sudah lama, kecil nilainya.',
                                  'Others' => 'Di luar pola utama.'
                                ];
                                foreach (array_keys($segments) as $seg):
                                    $class = strtolower($seg);
                                    $tooltip = $segments[$seg];
                                ?>
                                  <div class="segment-item <?= $class ?>" data-seg="<?= $seg ?>" data-tooltip="<?= $tooltip ?>"><?= $seg ?></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="segments" id="segmentsInput">
                            
                            <hr class="my-4">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold mb-2"><i class="fas fa-box text-info"></i> Include Produk</label>
                                    <small class="d-block text-muted mb-2">Pernah beli produk ini:</small>
                                    <div class="multi-select-container" id="includeContainer">
                                        <input type="text" class="multi-select-input" placeholder="Cari produk...">
                                        <div class="dropdown-list"></div>
                                        <div class="multi-select-tags" id="includeTags"></div>
                                    </div>
                                    <input type="hidden" name="include_produk_json" id="includeProdukJson">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold mb-2"><i class="fas fa-ban text-danger"></i> Exclude Produk</label>
                                    <small class="d-block text-muted mb-2">JANGAN kirim ke pembeli ini:</small>
                                    <div class="multi-select-container" id="excludeContainer">
                                        <input type="text" class="multi-select-input" placeholder="Cari produk...">
                                        <div class="dropdown-list"></div>
                                        <div class="multi-select-tags" id="excludeTags"></div>
                                    </div>
                                    <input type="hidden" name="exclude_produk_json" id="excludeProdukJson">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fas fa-cogs me-2 text-warning"></i>Pengaturan Pengiriman</h6></div>
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <label class="fw-bold">Akun WhatsApp Gateway <span class="text-danger">*</span></label>
                                <select class="form-select mt-2" name="wa_account_id" required>
                                    <option value="">-- Pilih Akun --</option>
                                    <?php foreach($wa_accounts as $acc): ?>
                                        <option value="<?= (int)$acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3 border rounded p-3 bg-light">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="tglCheckbox">
                                    <label class="form-check-label fw-bold">Filter Tanggal Transaksi</label>
                                </div>
                                <div id="tanggalFields" style="display:none;" class="mt-2">
                                    <input type="date" class="form-control mb-2" name="tanggal_mulai" value="<?= date('Y-m-01') ?>">
                                    <input type="date" class="form-control" name="tanggal_akhir" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <div class="mb-3 border rounded p-3 bg-light">
                                <label class="fw-bold mb-2">Limit / Batch Pengiriman</label>
                                <div class="d-flex gap-2">
                                    <div>
                                        <small class="text-muted">Dari Urutan</small>
                                        <input type="number" class="form-control" id="urut_awal" name="urut_awal" min="1" value="1">
                                    </div>
                                    <div>
                                        <small class="text-muted">Sampai Urutan</small>
                                        <input type="number" class="form-control" id="urut_akhir" name="urut_akhir" min="1" value="100">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="fas fa-paper-plane me-2"></i> Kirim Broadcast
                            </button>
                        </div>
                    </div>
                    
                    <button type="button" id="btnUpdateSegment" class="btn btn-outline-success w-100 mb-4">
                        <i class="fas fa-sync-alt me-2"></i> Update Data Segmentasi
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-5">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-eye me-2 text-info"></i>Preview Penerima</h6>
                    <span class="badge bg-secondary">Menampilkan <span id="totalBatch">0</span> dari <span id="totalSemua">0</span></span>
                </div>
                <div class="card-body p-0">
                    <div class="preview-container">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>No</th>
                                    <th>Pelanggan</th>
                                    <th>Terakhir Beli</th>
                                    <th>Recency</th>
                                    <th>Freq</th>
                                    <th>Monetary</th>
                                    <th>Segment</th>
                                </tr>
                            </thead>
                            <tbody id="previewTbody">
                                <tr><td colspan="7" class="text-center py-4 text-muted">Pilih filter untuk melihat preview...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </form>
    </div>
</div>

<script>
    const produkList = <?= $produk_json ?>;

    // Toggle Teks / Gambar
    document.querySelectorAll('input[name="tipe_pesan"]').forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.value === 'text') {
          document.getElementById('pesanTeks').style.display = 'block';
          document.getElementById('pesanGambar').style.display = 'none';
          document.querySelector('textarea[name="pesan"]').required = true;
        } else {
          document.getElementById('pesanTeks').style.display = 'none';
          document.getElementById('pesanGambar').style.display = 'block';
          document.querySelector('textarea[name="pesan"]').required = false;
        }
      });
    });

    // Segmentasi Toggle
    let selectedSegments = [];
    document.querySelectorAll('.segment-item').forEach(el => {
      el.addEventListener('click', () => {
        el.classList.toggle('active');
        const seg = el.dataset.seg;
        if (el.classList.contains('active')) {
          if (!selectedSegments.includes(seg)) selectedSegments.push(seg);
        } else {
          selectedSegments = selectedSegments.filter(s => s !== seg);
        }
        document.getElementById('segmentsInput').value = selectedSegments.join(',');
        triggerPreview();
      });
    });

    // Tanggal Toggle
    document.getElementById('tglCheckbox').addEventListener('change', function() {
      document.getElementById('tanggalFields').style.display = this.checked ? 'block' : 'none';
      triggerPreview();
    });

    // Multi-select Logic (Include/Exclude Produk)
    function initMultiSelect(containerId, tagsId, hiddenInputId) {
      const container = document.getElementById(containerId);
      const input = container.querySelector('.multi-select-input');
      const dropdown = container.querySelector('.dropdown-list');
      const tagsContainer = document.getElementById(tagsId);
      const hiddenInput = document.getElementById(hiddenInputId);
      let selected = [];

      input.addEventListener('click', () => showDropdown());
      input.addEventListener('input', () => showDropdown());

      function showDropdown() {
        const term = input.value.toLowerCase();
        dropdown.innerHTML = '';
        const filtered = produkList.filter(p => p.text.toLowerCase().includes(term));
        filtered.forEach(p => {
          const item = document.createElement('div');
          item.className = 'dropdown-item';
          if (selected.some(s => s.id === p.id)) item.classList.add('selected');
          item.textContent = p.text;
          item.addEventListener('click', () => {
            if (!selected.some(s => s.id === p.id)) {
              selected.push(p); renderTags();
            }
            input.value = ''; hideDropdown(); triggerPreview();
          });
          dropdown.appendChild(item);
        });
        dropdown.style.display = filtered.length ? 'block' : 'none';
      }
      function hideDropdown() { dropdown.style.display = 'none'; }
      function renderTags() {
        tagsContainer.innerHTML = '';
        selected.forEach(p => {
          const tag = document.createElement('div'); tag.className = 'tag';
          tag.innerHTML = `${p.text} <span class="tag-remove" data-id="${p.id}">×</span>`;
          tag.querySelector('.tag-remove').addEventListener('click', (e) => {
            e.stopPropagation(); selected = selected.filter(s => s.id !== p.id); renderTags(); triggerPreview();
          });
          tagsContainer.appendChild(tag);
        });
        hiddenInput.value = JSON.stringify(selected.map(p => p.id));
      }
      document.addEventListener('click', (e) => { if (!container.contains(e.target)) hideDropdown(); });
    }

    initMultiSelect('includeContainer', 'includeTags', 'includeProdukJson');
    initMultiSelect('excludeContainer', 'excludeTags', 'excludeProdukJson');

    // === LIVE PREVIEW FETCHING ===
    let previewTimeout;
    function triggerPreview() { clearTimeout(previewTimeout); previewTimeout = setTimeout(fetchPreview, 500); }

    function fetchPreview() {
      const urutAwal = parseInt(document.getElementById('urut_awal').value) || 1;
      const urutAkhir = parseInt(document.getElementById('urut_akhir').value) || 100;

      const formData = {
        segments: selectedSegments,
        include_produk: JSON.parse(document.getElementById('includeProdukJson').value || '[]'),
        exclude_produk: JSON.parse(document.getElementById('excludeProdukJson').value || '[]'),
        tanggal_mulai: document.getElementById('tglCheckbox').checked ? document.querySelector('input[name="tanggal_mulai"]').value : null,
        tanggal_akhir: document.getElementById('tglCheckbox').checked ? document.querySelector('input[name="tanggal_akhir"]').value : null,
        urut_awal: urutAwal, urut_akhir: urutAkhir
      };

      if (selectedSegments.length === 0 && formData.include_produk.length === 0) {
        clearPreview("Pilih minimal 1 segment atau 1 produk include."); return;
      }

      // --- TAMBAHAN: Efek Loading sebelum memanggil API ---
      document.getElementById('previewTbody').innerHTML = `<tr><td colspan="7" class="text-center py-4 text-primary"><i class="fas fa-spinner fa-spin me-2"></i>Sedang memuat pratinjau penerima...</td></tr>`;

      fetch('backend/get_penerima.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(formData)
      })
      .then(r => r.json())
      .then(data => {
        document.getElementById('totalSemua').textContent = data.total || '0';
        document.getElementById('totalBatch').textContent = data.total_batch || '0';
        
        const tbody = document.getElementById('previewTbody');
        if (data.success && data.total_batch > 0) {
          let rows = '';
          data.penerima.forEach((p, index) => {
              const segClass = (p.segment || 'others').toLowerCase();
              // --- PERBAIKAN: Nomor baris mengikuti Urutan Awal ---
              const nomorBaris = urutAwal + index; 
              
              rows += `
                <tr style="background-color: var(--${segClass}) !important; opacity: 0.9;">
                  <td>${nomorBaris}</td>
                  <td><strong>${p.nama}</strong><br><small class="text-muted">${p.nomor_wa}</small></td>
                  <td><small>${p.pembelian_terakhir}</small></td>
                  <td>${p.recency_hari} hr</td>
                  <td>${p.frekuensi}x</td>
                  <td class="text-success fw-bold">Rp ${p.monetary.toLocaleString('id-ID')}</td>
                  <td><span class="badge bg-dark">${p.segment}</span></td>
                </tr>
              `;
            });
          tbody.innerHTML = rows;
        } else {
          tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${data.error || 'Tidak ada data penerima yang cocok dengan filter ini.'}</td></tr>`;
        }
      }).catch(err => clearPreview("Gagal memuat data preview. Cek koneksi Anda."));
    }

    function clearPreview(msg) {
      document.getElementById('totalSemua').textContent = '0'; document.getElementById('totalBatch').textContent = '0';
      document.getElementById('previewTbody').innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">${msg}</td></tr>`;
    }

    // Input Batas urutan berubah -> Update preview
    document.getElementById('urut_awal').addEventListener('input', triggerPreview);
    document.getElementById('urut_akhir').addEventListener('input', triggerPreview);

    // === UPDATE SEGMENTASI BUTTON ===
    document.getElementById('btnUpdateSegment').addEventListener('click', async () => {
      const btn = document.getElementById('btnUpdateSegment');
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';
      btn.disabled = true;

      try {
        const res = await fetch('backend/update_segmentasi.php');
        const data = await res.json();
        if (data.success) {
          alert(`✅ ${data.message}`); triggerPreview();
        } else {
          alert('❌ ' + (data.error || 'Terjadi kesalahan.'));
        }
      } catch (e) { alert('❌ Gagal terhubung ke server.'); } finally {
        btn.innerHTML = originalHTML; btn.disabled = false;
      }
    });

    triggerPreview();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>