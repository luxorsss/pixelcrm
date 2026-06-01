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
    /* Color Palette RFM Premium */
    :root {
      --champ:   #FEF9C3;  
      --loyal:   #DBEAFE;  
      --prime:   #FEF08A;  
      --new:     #D1FAE5;  
      --risk:    #FCE7F3;  
      --cold:    #E5E7EB;  
      --whale:   #F3E8FF;  
      --others:  #F3F4F6;  
    }
    
    /* Segment Selector UI */
    .segment-checkboxes { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .segment-item {
      padding: 6px 14px; border-radius: 8px; color: #374151; font-size: 0.8rem; font-weight: 600;
      cursor: pointer; user-select: none; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); 
      border: 2px solid transparent; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .segment-item:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .segment-item.active { border-color: #3B82F6; color: #1E3A8A; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
    
    .segment-item.champ { background-color: var(--champ); }
    .segment-item.loyal { background-color: var(--loyal); }
    .segment-item.prime { background-color: var(--prime); }
    .segment-item.new { background-color: var(--new); }
    .segment-item.risk { background-color: var(--risk); color: #BE185D; }
    .segment-item.cold { background-color: var(--cold); }
    .segment-item.whale { background-color: var(--whale); color: #6B21A8; }
    .segment-item.others { background-color: var(--others); }
    
    /* Tooltip Custom */
    .segment-item[data-tooltip]::after {
      content: attr(data-tooltip); position: absolute; left: 50%; bottom: 120%; transform: translateX(-50%);
      background: #111827; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 500;
      white-space: nowrap; opacity: 0; pointer-events: none; transition: 0.2s ease; z-index: 20;
    }
    .segment-item[data-tooltip]:hover::after { opacity: 1; bottom: 130%; }    

    /* Multi-select Tags UI */
    .multi-select-container { position: relative; width: 100%; }
    .multi-select-input { width: 100%; padding: 10px 10px 10px 36px; border: 1px solid #D1D5DB; border-radius: 12px; font-size: 0.85rem; font-weight: 500; outline: none; transition: border-color 0.2s; }
    .multi-select-input:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    .multi-select-input::before { content: "\f002"; font-family: "Font Awesome 5 Free"; font-weight: 900; position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9CA3AF; }
    .multi-select-tags { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; }
    .tag { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .tag-remove { cursor: pointer; color: #93C5FD; transition: color 0.2s; font-size: 1rem; line-height: 1; }
    .tag-remove:hover { color: #DC2626; }
    .dropdown-list { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #D1D5DB; border-radius: 8px; margin-top: 4px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; z-index: 100; display: none; }
    .dropdown-item { padding: 10px 14px; cursor: pointer; font-size: 0.85rem; font-weight: 500; border-bottom: 1px solid #F3F4F6; }
    .dropdown-item:last-child { border-bottom: none; }
    .dropdown-item:hover { background: #F9FAFB; color: #2563EB; }
    .dropdown-item.selected { background: #EFF6FF; color: #1D4ED8; font-weight: 700; }
    
    /* Custom Radio Toggle */
    .radio-option { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; padding: 8px 16px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 0.85rem; transition: all 0.2s; background: white; margin-right: 10px; }
    .radio-option:has(input:checked) { border-color: #3B82F6; background: #EFF6FF; color: #1D4ED8; }
    .radio-option input { display: none; }
</style>

<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 1400px;">
        
        <div class="dash-header mb-4">
            <h1 class="dash-title"><i class="fas fa-bullhorn text-primary me-2"></i> Broadcast Campaign</h1>
            <p class="text-muted mt-1 fw-medium" style="font-size: 0.95rem;">Kirim pesan massal dengan menargetkan segmentasi RFM dan riwayat transaksi pelanggan.</p>
        </div>

        <form method="POST" action="proses_kirim.php" id="formBroadcast">
            <div class="row g-4">
                
                <div class="col-xl-8 col-lg-7 d-flex flex-column gap-4">
                    
                    <div class="panel-editorial p-0 overflow-hidden">
                        <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h3 class="panel-title m-0"><i class="fas fa-users text-success me-2"></i> Filter Audiens (RFM)</h3>
                            <button type="button" id="btnUpdateSegment" class="btn btn-light btn-sm border fw-bold text-dark rounded-pill px-3">
                                <i class="fas fa-sync-alt text-primary me-1"></i> Update Data RFM
                            </button>
                        </div>
                        <div class="p-4" style="background: #FAFAFA;">
                            
                            <div class="mb-4">
                                <label class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">Pilih Segmentasi Target</label>
                                <div class="text-muted mb-2" style="font-size: 0.75rem;">Klik untuk memilih satu atau beberapa kelompok RFM sekaligus.</div>
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
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="fw-bold text-dark mb-1" style="font-size: 0.85rem;"><i class="fas fa-filter text-info me-1"></i> Wajib Beli Produk Ini (Include)</label>
                                    <div class="multi-select-container mt-1" id="includeContainer">
                                        <div class="position-relative">
                                            <i class="fas fa-search position-absolute text-muted" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 0.85rem; pointer-events: none;"></i>
                                            <input type="text" class="multi-select-input" placeholder="Cari nama produk...">
                                        </div>
                                        <div class="dropdown-list"></div>
                                        <div class="multi-select-tags" id="includeTags"></div>
                                    </div>
                                    <input type="hidden" name="include_produk_json" id="includeProdukJson">
                                </div>

                                <div class="col-md-6">
                                    <label class="fw-bold text-dark mb-1" style="font-size: 0.85rem;"><i class="fas fa-ban text-danger me-1"></i> Jangan Kirim Jika Pernah Beli (Exclude)</label>
                                    <div class="multi-select-container mt-1" id="excludeContainer">
                                        <div class="position-relative">
                                            <i class="fas fa-search position-absolute text-muted" style="top: 50%; left: 14px; transform: translateY(-50%); font-size: 0.85rem; pointer-events: none;"></i>
                                            <input type="text" class="multi-select-input" placeholder="Cari nama produk...">
                                        </div>
                                        <div class="dropdown-list"></div>
                                        <div class="multi-select-tags" id="excludeTags"></div>
                                    </div>
                                    <input type="hidden" name="exclude_produk_json" id="excludeProdukJson">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="panel-editorial">
                        <h3 class="panel-title"><i class="fas fa-pen-nib text-warning me-2"></i> Editor Pesan</h3>
                        
                        <div class="mb-3 d-flex flex-wrap gap-2">
                            <label class="radio-option">
                                <input type="radio" name="tipe_pesan" value="text" checked> 
                                <i class="fas fa-align-left"></i> Teks Saja
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipe_pesan" value="image"> 
                                <i class="fas fa-image"></i> Media Gambar
                            </label>
                        </div>

                        <div id="pesanTeks">
                            <textarea class="form-control-editorial fw-medium" name="pesan" rows="6" 
                                      placeholder="Hai [nama], terima kasih sudah berlangganan! 💙" required 
                                      style="font-family: monospace; resize: vertical; font-size: 0.85rem; line-height: 1.6;"></textarea>
                        </div>

                        <div id="pesanGambar" style="display:none;">
                            <div class="mb-3 p-3 bg-light border rounded-3">
                                <label class="fw-bold text-dark mb-2" style="font-size: 0.8rem;"><i class="fas fa-link text-info me-1"></i> URL Gambar (JPG/PNG)</label>
                                <input type="url" class="form-control-editorial bg-white" name="link_gambar" placeholder="https://example.com/gambar-promo.jpg">
                            </div>
                            <textarea class="form-control-editorial fw-medium" name="caption" rows="5" 
                                      placeholder="Tulis caption promomu di sini... (Hai [nama], ini hadiah spesial buat kamu! 🎁)"
                                      style="font-family: monospace; resize: vertical; font-size: 0.85rem; line-height: 1.6;"></textarea>
                        </div>
                        
                        <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-info-circle me-1"></i> Gunakan variabel <code>[nama]</code> untuk memanggil nama pelanggan secara otomatis.
                        </div>
                    </div>

                </div>

                <div class="col-xl-4 col-lg-5 d-flex flex-column gap-4">
                    
                    <div class="panel-editorial sticky-top" style="top: 2rem;">
                        <h3 class="panel-title border-bottom pb-3 mb-3"><i class="fas fa-paper-plane text-primary me-2"></i> Delivery Settings</h3>
                        
                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">Device / Akun WhatsApp <span class="text-danger">*</span></label>
                            <select class="form-control-editorial fw-bold" name="wa_account_id" required style="appearance: auto; cursor: pointer;">
                                <option value="">-- Pilih Device Pengirim --</option>
                                <?php foreach($wa_accounts as $acc): ?>
                                    <option value="<?= (int)$acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4 p-3 rounded-3" style="background: #FFFBEB; border: 1px solid #FDE68A;">
                            <label class="toggle-switch w-100" style="cursor: pointer; margin: 0;">
                                <div class="text-start pe-3">
                                    <div class="toggle-label text-warning fw-bold"><i class="fas fa-flask me-1"></i> Mode Uji Coba</div>
                                </div>
                                <input type="checkbox" id="mode_uji" name="mode_uji" value="1" class="switch-input">
                                <div class="switch-slider" style="background-color: #FCD34D;"></div>
                            </label>
                            
                            <div id="ujiFields" style="display:none;" class="mt-3 pt-3 border-top border-warning border-opacity-25">
                                <label class="fw-bold text-dark mb-1" style="font-size: 0.75rem;">Kirim ke Nomor Ini Saja:</label>
                                <input type="text" class="form-control-editorial fw-bold border-warning" id="nomor_uji" name="nomor_uji" placeholder="Contoh: 6281234567890">
                                <div class="text-danger mt-1 fw-bold" style="font-size: 0.7rem;">*Segmen & Limit otomatis diabaikan.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="toggle-switch w-100 mb-2" style="cursor: pointer; margin: 0;">
                                <div class="text-start pe-3">
                                    <div class="toggle-label text-dark" style="font-size: 0.85rem;"><i class="fas fa-calendar-alt text-info me-1"></i> Filter Tgl Transaksi</div>
                                </div>
                                <input type="checkbox" id="tglCheckbox" class="switch-input">
                                <div class="switch-slider"></div>
                            </label>
                            <div id="tanggalFields" style="display:none;" class="mt-2 p-3 bg-light rounded-3 border">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="text-muted" style="font-size: 0.7rem;">Dari Tanggal</label>
                                        <input type="date" class="form-control-editorial text-muted px-2" name="tanggal_mulai" value="<?= date('Y-m-01') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="text-muted" style="font-size: 0.7rem;">Sampai</label>
                                        <input type="date" class="form-control-editorial text-muted px-2" name="tanggal_akhir" value="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-2" style="font-size: 0.85rem;"><i class="fas fa-sort-numeric-down text-secondary me-1"></i> Batch Limit (Pengiriman)</label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <label class="text-muted" style="font-size: 0.7rem;">Mulai Data Ke-</label>
                                    <input type="number" class="form-control-editorial fw-bold text-center" id="urut_awal" name="urut_awal" min="1" value="1">
                                </div>
                                <div class="flex-grow-1">
                                    <label class="text-muted" style="font-size: 0.7rem;">Sampai Data Ke-</label>
                                    <input type="number" class="form-control-editorial fw-bold text-center" id="urut_akhir" name="urut_akhir" min="1" value="100">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill" style="font-size: 0.95rem; letter-spacing: 0.02em; transition: all 0.2s; box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);">
                            <i class="fas fa-paper-plane me-2"></i> Eksekusi Broadcast
                        </button>

                    </div>
                </div>
            </div>

            <div class="panel-editorial p-0 overflow-hidden mb-5">
                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h3 class="panel-title m-0"><i class="fas fa-eye text-info me-2"></i> Target Penerima Broadcast</h3>
                    <div class="badge-clean bg-light text-dark border">
                        Menampilkan <strong class="text-primary" id="totalBatch">0</strong> dari Total <strong class="text-primary" id="totalSemua">0</strong> Pelanggan
                    </div>
                </div>
                <div class="bg-light p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table-editorial mb-0">
                            <thead class="sticky-top" style="z-index: 5;">
                                <tr>
                                    <th width="60" class="text-center">No</th>
                                    <th>Pelanggan</th>
                                    <th width="150">Last Order</th>
                                    <th width="100" class="text-center" title="Jarak hari sejak transaksi terakhir">Recency</th>
                                    <th width="100" class="text-center" title="Jumlah total transaksi">Freq</th>
                                    <th width="150" class="text-end">Monetary</th>
                                    <th width="120" class="text-center pe-4">Segment</th>
                                </tr>
                            </thead>
                            <tbody id="previewTbody">
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-filter fa-3x text-muted mb-3 opacity-50"></i>
                                        <h6 class="fw-bold text-dark">Siap Memfilter Target</h6>
                                        <div class="text-muted" style="font-size: 0.85rem;">Pilih segmentasi atau produk di atas untuk melihat preview penerima.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
    // Data List Produk dari PHP
    const produkList = <?= $produk_json ?>;

    // Toggle Teks / Gambar (Native styling)
    document.querySelectorAll('input[name="tipe_pesan"]').forEach(radio => {
      radio.addEventListener('change', function() {
        const textContainer = document.getElementById('pesanTeks');
        const imgContainer = document.getElementById('pesanGambar');
        const textInput = textContainer.querySelector('textarea');
        
        if (this.value === 'text') {
          textContainer.style.display = 'block';
          imgContainer.style.display = 'none';
          textInput.required = true;
          textInput.name = "pesan";
          imgContainer.querySelector('textarea').name = "caption";
        } else {
          textContainer.style.display = 'none';
          imgContainer.style.display = 'block';
          textInput.required = false;
          textInput.name = "pesan_ignore"; // Biar gak kebawa POST
          imgContainer.querySelector('textarea').name = "pesan"; // Timpa nama pesannya kesini
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

    // Tanggal Toggle (Custom Switch)
    document.getElementById('tglCheckbox').addEventListener('change', function() {
      const tgF = document.getElementById('tanggalFields');
      if(this.checked) {
          tgF.style.display = 'block';
      } else {
          tgF.style.display = 'none';
      }
      triggerPreview();
    });

    // Mode Uji Toggle
    document.getElementById('mode_uji').addEventListener('change', function() {
        const ujiFields = document.getElementById('ujiFields');
        const nomorUji = document.getElementById('nomor_uji');
        if (this.checked) {
            ujiFields.style.display = 'block';
            nomorUji.required = true;
        } else {
            ujiFields.style.display = 'none';
            nomorUji.required = false;
        }
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
          tag.innerHTML = `${p.text} <span class="tag-remove" data-id="${p.id}">&times;</span>`;
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
    function triggerPreview() { 
        clearTimeout(previewTimeout); 
        previewTimeout = setTimeout(fetchPreview, 500); 
    }

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
        clearPreview("Pilih minimal 1 segment atau 1 produk target untuk memfilter daftar."); return;
      }

      document.getElementById('previewTbody').innerHTML = `<tr><td colspan="7" class="text-center py-5 text-primary"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><br><span class="fw-bold">Sinkronisasi Database...</span></td></tr>`;

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
              const nomorBaris = urutAwal + index; 
              
              // Define tag color logic for Segment display in table
              let badgeColor = '#6B7280'; let badgeBg = '#F3F4F6';
              if(segClass==='champ') { badgeBg = '#FEF9C3'; badgeColor = '#854D0E'; }
              else if(segClass==='loyal') { badgeBg = '#DBEAFE'; badgeColor = '#1E40AF'; }
              else if(segClass==='prime') { badgeBg = '#FEF08A'; badgeColor = '#854D0E'; }
              else if(segClass==='new') { badgeBg = '#D1FAE5'; badgeColor = '#065F46'; }
              else if(segClass==='risk') { badgeBg = '#FCE7F3'; badgeColor = '#9D174D'; }
              else if(segClass==='whale') { badgeBg = '#F3E8FF'; badgeColor = '#6B21A8'; }
              
              rows += `
                <tr>
                  <td class="text-center text-muted fw-bold" style="font-size: 0.85rem;">${nomorBaris}</td>
                  <td>
                      <div class="fw-bold text-dark" style="font-size: 0.85rem;">${p.nama}</div>
                      <div class="text-muted" style="font-size: 0.75rem;"><i class="fab fa-whatsapp text-success me-1"></i>${p.nomor_wa}</div>
                  </td>
                  <td><div class="text-dark fw-bold" style="font-size: 0.85rem;">${p.pembelian_terakhir}</div></td>
                  <td class="text-center"><span class="badge-clean bg-light text-muted border">${p.recency_hari} hr</span></td>
                  <td class="text-center"><span class="badge-clean bg-light text-primary border">${p.frekuensi}x</span></td>
                  <td class="text-end text-success fw-bold" style="font-size: 0.85rem;">Rp ${p.monetary.toLocaleString('id-ID')}</td>
                  <td class="text-center pe-4"><span class="badge-clean" style="background: ${badgeBg}; color: ${badgeColor}; font-size: 0.65rem;">${p.segment}</span></td>
                </tr>
              `;
            });
          tbody.innerHTML = rows;
        } else {
          tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-danger fw-bold"><i class="fas fa-exclamation-triangle fa-2x mb-3 opacity-50"></i><br>${data.error || 'Tidak ada data penerima yang cocok dengan filter ini.'}</td></tr>`;
        }
      }).catch(err => clearPreview("Gagal memuat data preview. Cek koneksi server Anda."));
    }

    function clearPreview(msg) {
      document.getElementById('totalSemua').textContent = '0'; 
      document.getElementById('totalBatch').textContent = '0';
      document.getElementById('previewTbody').innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-filter fa-3x mb-3 opacity-25"></i><br>${msg}</td></tr>`;
    }

    document.getElementById('urut_awal').addEventListener('input', triggerPreview);
    document.getElementById('urut_akhir').addEventListener('input', triggerPreview);

    // === UPDATE SEGMENTASI BUTTON ===
    document.getElementById('btnUpdateSegment').addEventListener('click', async () => {
      const btn = document.getElementById('btnUpdateSegment');
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sinkronisasi...';
      btn.disabled = true;

      try {
        const res = await fetch('backend/update_segmentasi.php');
        const data = await res.json();
        if (data.success) {
          // Ganti jadi feedback inline ala modern UI
          btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Data Terupdate';
          setTimeout(() => { btn.innerHTML = originalHTML; btn.disabled = false; }, 3000);
          triggerPreview();
        } else {
          alert('❌ ' + (data.error || 'Terjadi kesalahan saat memproses RFM.'));
          btn.innerHTML = originalHTML; btn.disabled = false;
        }
      } catch (e) { 
          alert('❌ Koneksi terputus. Silakan coba lagi.'); 
          btn.innerHTML = originalHTML; btn.disabled = false;
      }
    });

    // UX Feedback on Submit
    document.getElementById('formBroadcast').addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        if(btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengantre Broadcast...';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        }
    });

</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>