<?php
/**
 * Pixel & Meta Conversion API (CAPI) Centralized Helper
 * PixelCRM
 */

require_once __DIR__ . '/init.php';

/**
 * Mengambil konfigurasi pixel untuk suatu produk
 * Mendukung pixel_id baru dari tabel pixels, dengan fallback ke kolom lama di produk
 * 
 * @param array|int $produk Data produk (array) atau produk_id (int)
 * @return array|null
 */
function getPixelForProduk($produk) {
    if (is_numeric($produk)) {
        $produk = fetchRow("SELECT * FROM produk WHERE id = ?", [(int)$produk]);
    }
    
    if (!$produk) {
        return null;
    }
    
    // 1. Cek apakah tracking diaktifkan untuk produk ini
    if (isset($produk['tracking_aktif']) && (int)$produk['tracking_aktif'] === 0) {
        return null;
    }
    
    // 2. Cek relasi ke tabel pixels
    if (!empty($produk['pixel_id'])) {
        $pixel = fetchRow("SELECT * FROM pixels WHERE id = ? AND is_active = 1", [(int)$produk['pixel_id']]);
        if ($pixel && !empty($pixel['meta_pixel_id'])) {
            return [
                'id' => $pixel['id'],
                'nama' => $pixel['nama'],
                'meta_pixel_id' => trim($pixel['meta_pixel_id']),
                'conversion_api_token' => trim($pixel['conversion_api_token'] ?? ''),
                'test_event_code' => trim($pixel['test_event_code'] ?? ''),
            ];
        }
    }
    
    // 3. Fallback ke kolom lama di tabel produk jika belum terhubung ke tabel pixels
    if (!empty($produk['meta_pixel_id'])) {
        return [
            'id' => null,
            'nama' => 'Legacy Pixel',
            'meta_pixel_id' => trim($produk['meta_pixel_id']),
            'conversion_api_token' => trim($produk['conversion_api_token'] ?? ''),
            'test_event_code' => '',
        ];
    }
    
    return null;
}

/**
 * Mengirim event ke Meta Conversion API (CAPI)
 * 
 * @param string $access_token
 * @param string $pixel_id
 * @param string $event_name
 * @param array $user_data
 * @param array $custom_data
 * @param string|null $event_id
 * @param string|null $test_event_code
 * @return string|false
 */
function sendMetaCAPIEvent($access_token, $pixel_id, $event_name, $user_data, $custom_data = [], $event_id = null, $test_event_code = null) {
    $access_token = trim($access_token);
    $pixel_id = trim($pixel_id);
    
    if (empty($access_token) || empty($pixel_id)) {
        return false;
    }
    
    if (!$event_id) {
        $event_id = uniqid('event_', true);
    }
    
    $capi_url = 'https://graph.facebook.com/v20.0/' . $pixel_id . '/events';
    
    // Pastikan user data memiliki client IP & User Agent jika belum ada
    if (empty($user_data['client_ip_address']) && isset($_SERVER['REMOTE_ADDR'])) {
        $user_data['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    if (empty($user_data['client_user_agent']) && isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_data['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    $event_payload = [
        'event_name' => $event_name,
        'event_time' => time(),
        'event_id' => $event_id,
        'action_source' => 'website',
        'user_data' => $user_data,
        'custom_data' => $custom_data
    ];
    
    $post_data = [
        'data' => [$event_payload],
        'access_token' => $access_token
    ];
    
    // Tambahkan Test Event Code jika disediakan untuk live testing di Meta Events Manager
    if (!empty($test_event_code)) {
        $post_data['test_event_code'] = trim($test_event_code);
    }
    
    $ch = curl_init($capi_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log("CAPI Error [{$event_name}] Pixel {$pixel_id}: HTTP {$http_code}, Error: {$curl_error}, Response: {$response}");
    }
    
    return $response;
}

/**
 * Render script Meta Pixel untuk browser dengan deduplikasi Event ID
 * 
 * @param string $pixel_id
 * @param string $event_name Nama event standard (cth: InitiateCheckout, AddPaymentInfo, Purchase)
 * @param array $custom_data Parameter custom data untuk fbq track
 * @param string|null $event_id ID event deduplikasi (harus sama dengan yang dikirim via CAPI)
 * @param string|null $fbc Cookie _fbc
 * @param string|null $fbp Cookie _fbp
 * @return string HTML script tag
 */
function renderMetaPixelScript($pixel_id, $event_name = '', $custom_data = [], $event_id = null, $fbc = null, $fbp = null) {
    $pixel_id = trim($pixel_id);
    if (empty($pixel_id)) {
        return '';
    }
    
    $fbc = $fbc ?? ($_COOKIE['_fbc'] ?? $_SESSION['fbclid_final'] ?? null);
    $fbp = $fbp ?? ($_COOKIE['_fbp'] ?? null);
    
    $init_params = ['agent' => 'pl_web'];
    if ($fbc) $init_params['fbc'] = $fbc;
    if ($fbp) $init_params['fbp'] = $fbp;
    $json_init_params = json_encode($init_params, JSON_UNESCAPED_SLASHES);
    
    $track_code = '';
    if (!empty($event_name)) {
        $json_custom_data = !empty($custom_data) ? json_encode($custom_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '{}';
        if ($event_id) {
            $event_options = json_encode(['eventID' => (string)$event_id]);
            $track_code = "fbq('track', '{$event_name}', {$json_custom_data}, {$event_options});";
        } else {
            $track_code = "fbq('track', '{$event_name}', {$json_custom_data});";
        }
    }
    
    $html = <<<HTML
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');

fbq('init', '{$pixel_id}', {$json_init_params});
{$track_code}
</script>
<noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id={$pixel_id}&ev={$event_name}&noscript=1"/>
</noscript>
<!-- End Meta Pixel Code -->
HTML;

    return $html;
}
