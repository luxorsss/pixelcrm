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
if (!$produk_list) {
    $produk_list = [];
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

    /* Multi-select Dropdown UI */
    .multi-select-container { position: relative; width: 100%; }
    .multi-select-box {
      display: flex; align-items: center; background: #ffffff;
      border: 1px solid #D1D5DB; border-radius: 12px; padding: 2px 12px 2px 14px;
      transition: all 0.2s ease; cursor: pointer; min-height: 44px;
    }
    .multi-select-box:hover { border-color: #9CA3AF; }
    .multi-select-box.is-active, .multi-select-box:focus-within {
      border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .multi-select-input {
      flex: 1; border: none; outline: none; background: transparent;
      padding: 8px 6px; font-size: 0.85rem; font-weight: 500; color: #1F2937;
    }
    .multi-select-arrow {
      color: #6B7280; font-size: 0.8rem; transition: transform 0.2s ease; cursor: pointer; padding: 4px;
    }
    .multi-select-box.is-active .multi-select-arrow {
      transform: rotate(180deg); color: #2563EB;
    }
    .dropdown-list {
      position: absolute; top: calc(100% + 6px); left: 0; right: 0;
      background: #ffffff; border: 1px solid #D1D5DB; border-radius: 12px;
      box-shadow: 0 16px 36px rgba(0,0,0,0.14); max-height: 240px; overflow-y: auto;
      z-index: 99999; display: none; padding: 4px 0;
    }
    .dropdown-item {
      padding: 9px 14px; cursor: pointer; font-size: 0.85rem; font-weight: 500;
      color: #374151; background: #ffffff; display: flex; align-items: center; justify-content: space-between;
      transition: background 0.15s, color 0.15s; border-bottom: 1px solid #F9FAFB;
    }
    .dropdown-item:last-child { border-bottom: none; }
    .dropdown-item:hover { background: #F3F4F6; color: #1D4ED8; }
    .dropdown-item.selected { background: #EFF6FF; color: #1D4ED8; font-weight: 600; }
    .dropdown-item .check-icon { font-size: 0.8rem; color: #3B82F6; opacity: 0; }
    .dropdown-item.selected .check-icon { opacity: 1; }
    
    .multi-select-tags { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; }
    .tag {
      background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;
      padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .tag-remove { cursor: pointer; color: #93C5FD; font-size: 1rem; line-height: 1; }
    .tag-remove:hover { color: #DC2626; }
    
    /* Custom Radio Toggle */
    .radio-option { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; padding: 8px 16px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 0.85rem; transition: all 0.2s; background: white; margin-right: 10px; }
    .radio-option:has(input:checked) { border-color: #3B82F6; background: #EFF6FF; color: #1D4ED8; }
    .radio-option input { display: none; }
</style>

<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 1400px;">
        
        <div class="dash-header d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
            <div>
                <h1 class="dash-title d-flex align-items-center gap-2">
                    <i class="fas fa-bullhorn text-primary"></i> Broadcast Campaign
                </h1>
                <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">
                    Kirim pesan massal berdasarkan segmentasi RFM dan riwayat transaksi pelanggan.
                </div>
            </div>
        </div>

        <form method="POST" action="proses_kirim.php" id="formBroadcast">
            <div class="row g-4">
                
                <div class="col-xl-8 col-lg-7 d-flex flex-column gap-4">
                    
                    <div class="panel-editorial p-0" style="position: relative; z-index: 20;">
                        <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center flex-wrap gap-3" style="border-radius: 20px 20px 0 0;">
                            <h3 class="panel-title m-0"><i class="fas fa-users text-success me-2"></i> Filter Audiens (RFM)</h3>
                            <button type="button" id="btnUpdateSegment" class="btn btn-light btn-sm border fw-bold text-dark rounded-pill px-3">
                                <i class="fas fa-sync-alt text-primary me-1"></i> Update Data RFM
                            </button>
                        </div>
                        <div class="p-4" style="background: #FAFAFA; border-radius: 0 0 20px 20px;">
                            
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
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="fw-bold text-dark m-0" style="font-size: 0.85rem;"><i class="fas fa-filter text-info me-1"></i> Wajib Beli Produk Ini (Include)</label>
                                        <span class="badge rounded-pill bg-light text-muted border" id="includeCountBadge" style="font-size: 0.7rem; font-weight: 600;">0 dipilih</span>
                                    </div>
                                    <div class="multi-select-container mt-1" id="includeContainer">
                                        <div class="multi-select-box">
                                            <i class="fas fa-search text-muted me-2" style="font-size: 0.85rem; pointer-events: none;"></i>
                                            <input type="text" class="multi-select-input" placeholder="Klik untuk pilih produk..." autocomplete="off">
                                            <i class="fas fa-chevron-down multi-select-arrow" title="Buka daftar produk"></i>
                                        </div>
                                        <div class="dropdown-list"></div>
                                        <div class="multi-select-tags" id="includeTags"></div>
                                    </div>
                                    <input type="hidden" name="include_produk_json" id="includeProdukJson">
                                </div>

                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="fw-bold text-dark m-0" style="font-size: 0.85rem;"><i class="fas fa-ban text-danger me-1"></i> Jangan Kirim Jika Pernah Beli</label>
                                        <span class="badge rounded-pill bg-light text-muted border" id="excludeCountBadge" style="font-size: 0.7rem; font-weight: 600;">0 dipilih</span>
                                    </div>
                                    <div class="multi-select-container mt-1" id="excludeContainer">
                                        <div class="multi-select-box">
                                            <i class="fas fa-search text-muted me-2" style="font-size: 0.85rem; pointer-events: none;"></i>
                                            <input type="text" class="multi-select-input" placeholder="Klik untuk pilih produk..." autocomplete="off">
                                            <i class="fas fa-chevron-down multi-select-arrow" title="Buka daftar produk"></i>
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

    // Multi-select Dropdown Logic (Include/Exclude Produk)
    function initMultiSelect(containerId, tagsId, hiddenInputId, badgeId) {
      const container = document.getElementById(containerId);
      const box = container.querySelector('.multi-select-box');
      const input = container.querySelector('.multi-select-input');
      const arrow = container.querySelector('.multi-select-arrow');
      const dropdown = container.querySelector('.dropdown-list');
      const tagsContainer = document.getElementById(tagsId);
      const hiddenInput = document.getElementById(hiddenInputId);
      const countBadge = document.getElementById(badgeId);
      let selected = [];

      function isOpen() {
        return dropdown.style.display === 'block';
      }

      function showDropdown() {
        renderDropdownItems();
        dropdown.style.display = 'block';
        box.classList.add('is-active');
        container.style.zIndex = '9999';
        const panel = container.closest('.panel-editorial');
        if (panel) panel.style.zIndex = '100';
      }

      function hideDropdown() {
        dropdown.style.display = 'none';
        box.classList.remove('is-active');
        container.style.zIndex = '';
        const panel = container.closest('.panel-editorial');
        if (panel) panel.style.zIndex = '20';
      }

      function toggleDropdown() {
        if (isOpen()) {
          hideDropdown();
        } else {
          showDropdown();
        }
      }

      box.addEventListener('click', (e) => {
        if (e.target !== input) {
          input.focus();
        }
        if (!isOpen()) {
          showDropdown();
        }
      });

      arrow.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleDropdown();
      });

      input.addEventListener('focus', () => {
        showDropdown();
      });

      input.addEventListener('input', () => {
        if (!isOpen()) showDropdown();
        renderDropdownItems();
      });

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          hideDropdown();
        }
      });

      function renderDropdownItems() {
        const term = input.value.trim().toLowerCase();
        dropdown.innerHTML = '';

        if (!Array.isArray(produkList) || produkList.length === 0) {
          const empty = document.createElement('div');
          empty.className = 'dropdown-item text-muted';
          empty.style.cursor = 'default';
          empty.textContent = 'Belum ada data produk di sistem';
          dropdown.appendChild(empty);
          return;
        }

        const filtered = produkList.filter(p => p.text.toLowerCase().includes(term));
        if (filtered.length === 0) {
          const empty = document.createElement('div');
          empty.className = 'dropdown-item text-muted';
          empty.style.cursor = 'default';
          empty.textContent = 'Tidak ada produk yang cocok';
          dropdown.appendChild(empty);
        } else {
          filtered.forEach(p => {
            const isSelected = selected.some(s => s.id === p.id);
            const item = document.createElement('div');
            item.className = 'dropdown-item' + (isSelected ? ' selected' : '');
            item.innerHTML = `<span>${p.text}</span><i class="fas fa-check check-icon"></i>`;
            
            item.addEventListener('click', (e) => {
              e.stopPropagation();
              if (selected.some(s => s.id === p.id)) {
                selected = selected.filter(s => s.id !== p.id);
              } else {
                selected.push(p);
              }
              renderTags();
              renderDropdownItems();
              triggerPreview();
              input.focus();
            });
            dropdown.appendChild(item);
          });
        }
      }

      function renderTags() {
        tagsContainer.innerHTML = '';
        selected.forEach(p => {
          const tag = document.createElement('div');
          tag.className = 'tag';
          tag.innerHTML = `<span>${p.text}</span> <span class="tag-remove" data-id="${p.id}">&times;</span>`;
          tag.querySelector('.tag-remove').addEventListener('click', (e) => {
            e.stopPropagation();
            selected = selected.filter(s => s.id !== p.id);
            renderTags();
            renderDropdownItems();
            triggerPreview();
          });
          tagsContainer.appendChild(tag);
        });
        hiddenInput.value = JSON.stringify(selected.map(p => p.id));
        if (countBadge) {
          countBadge.textContent = selected.length + ' dipilih';
          if (selected.length > 0) {
            countBadge.classList.remove('bg-light', 'text-muted');
            countBadge.classList.add('bg-primary', 'text-white');
          } else {
            countBadge.classList.remove('bg-primary', 'text-white');
            countBadge.classList.add('bg-light', 'text-muted');
          }
        }
      }

      document.addEventListener('click', (e) => { 
        if (!container.contains(e.target)) hideDropdown(); 
      });
    }

    initMultiSelect('includeContainer', 'includeTags', 'includeProdukJson', 'includeCountBadge');
    initMultiSelect('excludeContainer', 'excludeTags', 'excludeProdukJson', 'excludeCountBadge');

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