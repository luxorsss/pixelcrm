<?php
/**
 * Template Pesan Functions - Simple & Fast
 */

// Get template by produk dan jenis
function getTemplate($produk_id, $jenis_pesan) {
    $template = fetchRow("SELECT * FROM template_pesan_produk WHERE produk_id = ? AND jenis_pesan = ?", 
        [$produk_id, $jenis_pesan]);
    return $template ? $template['isi_pesan'] : '';
}

// Save/Update template
function saveTemplate($produk_id, $jenis_pesan, $isi_pesan) {
    // Check if exists
    $existing = fetchRow("SELECT id FROM template_pesan_produk WHERE produk_id = ? AND jenis_pesan = ?", 
        [$produk_id, $jenis_pesan]);
    
    if ($existing) {
        // Update
        return execute("UPDATE template_pesan_produk SET isi_pesan = ? WHERE id = ?", 
            [$isi_pesan, $existing['id']]);
    } else {
        // Insert
        return execute("INSERT INTO template_pesan_produk (produk_id, jenis_pesan, isi_pesan) VALUES (?, ?, ?)", 
            [$produk_id, $jenis_pesan, $isi_pesan]);
    }
}

// Get all products for select
function getAllProducts() {
    return fetchAll("SELECT id, nama FROM produk ORDER BY nama");
}

// Get templates by product
function getTemplatesByProduct($produk_id) {
    $templates = [];
    $templates['invoice'] = getTemplate($produk_id, 'invoice');
    $templates['akses_produk'] = getTemplate($produk_id, 'akses_produk');
    return $templates;
}

// Delete templates by product
function deleteTemplatesByProduct($produk_id) {
    return execute("DELETE FROM template_pesan_produk WHERE produk_id = ?", [$produk_id]);
}

// Get product name
function getProductName($produk_id) {
    $product = fetchRow("SELECT nama FROM produk WHERE id = ?", [$produk_id]);
    return $product ? $product['nama'] : 'Produk Tidak Ditemukan';
}

/**
 * Template Placeholder System
 * Replace placeholder dengan data real
 */
function replaceTemplatePlaceholders($template, $data) {
    if (empty($template)) return '';
    
    // Basic replacements
    $replacements = [
        '[nama]' => $data['nama_customer'] ?? '',
        '[nowa]' => $data['nomor_wa'] ?? '',
		'[email]' => $data['email_customer'] ?? '',
        '[tanggal]' => $data['tanggal'] ?? date('d/m/Y'),
        '[waktu]' => $data['waktu'] ?? date('H:i'),
        '[total]' => isset($data['total_harga']) ? formatCurrency($data['total_harga']) : '',
        '[id_transaksi]' => $data['id_transaksi'] ?? '',
    ];
    
    // Handle products (single or multiple/bundling)
    if (isset($data['produk_list']) && is_array($data['produk_list'])) {
        // Multiple products (bundling case)
        $produk_names = [];
        $link_akses_list = [];
        $harga_list = [];
        
        foreach ($data['produk_list'] as $produk) {
            $produk_names[] = $produk['nama'];
            if (!empty($produk['link_akses'])) {
                $link_akses_list[] = $produk['nama'] . ': ' . $produk['link_akses'];
            }
            $harga_list[] = $produk['nama'] . ': ' . formatCurrency($produk['harga']);
        }
        
        $replacements['[produk]'] = $data['nama_produk'] ?? $produk_names[0] ?? '';
        $replacements['[daftar_produk]'] = "• " . implode("\n• ", $produk_names);
        $replacements['[link_akses]'] = implode("\n", $link_akses_list);
        $replacements['[daftar_link]'] = "• " . implode("\n• ", $link_akses_list);
        $replacements['[harga]'] = implode("\n", $harga_list);
        $replacements['[daftar_harga]'] = "• " . implode("\n• ", $harga_list);
        
    } else {
        // Single product
        $replacements['[produk]'] = $data['nama_produk'] ?? '';
        $replacements['[daftar_produk]'] = $data['nama_produk'] ?? '';
        $replacements['[harga]'] = isset($data['harga_produk']) ? formatCurrency($data['harga_produk']) : '';
        $replacements['[daftar_harga]'] = isset($data['harga_produk']) ? $data['nama_produk'] . ': ' . formatCurrency($data['harga_produk']) : '';
        $replacements['[link_akses]'] = $data['link_akses'] ?? '';
        $replacements['[daftar_link]'] = $data['link_akses'] ?? '';
    }
    
    // Apply replacements
    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

/**
 * Get sample data untuk preview
 */
function getSampleTemplateData($produk_id) {
    $product = fetchRow("SELECT * FROM produk WHERE id = ?", [$produk_id]);
    
    // Check if product has bundling
    $bundling = fetchAll("
        SELECT p.nama, p.harga, p.link_akses 
        FROM bundling b 
        JOIN produk p ON b.produk_bundling_id = p.id 
        WHERE b.produk_id = ?
    ", [$produk_id]);
    
    $sample_data = [
        'nama_customer' => 'John Doe',
		'email_customer' => 'john@example.com',
        'nomor_wa' => '628123456789',
        'tanggal' => date('d/m/Y'),
        'waktu' => date('H:i'),
        'id_transaksi' => 'TRX001',
    ];
    
    if (!empty($bundling)) {
        // Has bundling - multiple products
        $produk_list = [$product]; // Main product first
        foreach ($bundling as $bundle_item) {
            $produk_list[] = $bundle_item;
        }
        
        $sample_data['produk_list'] = $produk_list;
        $sample_data['total_harga'] = array_sum(array_column($produk_list, 'harga'));
    } else {
        // Single product
        $sample_data['nama_produk'] = $product['nama'];
        $sample_data['harga_produk'] = $product['harga'];
        $sample_data['link_akses'] = $product['link_akses'] ?? '';
        $sample_data['total_harga'] = $product['harga'];
    }
    
    return $sample_data;
}

/**
 * Get available placeholders list
 */
function getAvailablePlaceholders() {
    return [
        'Customer' => [
            '[nama]' => 'Nama customer',
            '[nowa]' => 'Nomor WhatsApp customer',
			'[email]' => 'Alamat Email customer'
        ],
        'Transaksi' => [
            '[id_transaksi]' => 'ID/Nomor transaksi',
            '[tanggal]' => 'Tanggal transaksi (dd/mm/yyyy)',
            '[waktu]' => 'Waktu transaksi (hh:mm)',
            '[total]' => 'Total harga (Rp format)'
        ],
        'Produk (Single)' => [
            '[produk]' => 'Nama produk',
            '[harga]' => 'Harga produk (Rp format)',
            '[link_akses]' => 'Link akses produk digital'
        ],
        'Produk (Multiple/Bundling)' => [
            '[daftar_produk]' => 'Daftar semua produk (dengan bullet)',
            '[daftar_harga]' => 'Daftar harga per produk',
            '[daftar_link]' => 'Daftar link akses per produk'
        ]
    ];
}
?>