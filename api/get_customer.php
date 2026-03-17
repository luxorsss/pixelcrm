<?php
/**
 * Customer API - Simple lookup
 */
require_once '../includes/init.php';

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$phone = get('phone');
if (!$phone) {
    echo json_encode(['success' => false, 'error' => 'Phone required']);
    exit;
}

// Format phone - remove all non-numeric first
$phone = preg_replace('/[^0-9]/', '', $phone);

// Convert to 62 format
if (substr($phone, 0, 1) === '0') {
    $phone = '62' . substr($phone, 1);
} elseif (substr($phone, 0, 2) !== '62') {
    $phone = '62' . $phone;
}

try {
    $customer = fetchRow("SELECT * FROM pelanggan WHERE nomor_wa = ? LIMIT 1", [$phone]);

    if ($customer) {
        echo json_encode([
            'success' => true,
            'customer' => [
                'nama' => $customer['nama'],
                'nomor_wa' => $customer['nomor_wa']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'customer' => null]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>