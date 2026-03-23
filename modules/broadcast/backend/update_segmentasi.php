<?php
require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

try {
    $sql = "
        SELECT
            p.id,
            DATEDIFF(CURDATE(), MAX(t.tanggal_transaksi)) AS recency_hari,
            COUNT(DISTINCT t.id) AS frequency,
            COALESCE(SUM(dt.harga), 0) AS monetary
        FROM pelanggan p
        INNER JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai'
        INNER JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        GROUP BY p.id
    ";

    $results = fetchAll($sql);
    $updates = [];

    if ($results) {
        foreach ($results as $row) {
            $days = (int)$row['recency_hari'];
            $freq = (int)$row['frequency'];
            $monetary = (float)$row['monetary'];

            $R = $days <= 7 ? 5 : ($days <= 30 ? 4 : ($days <= 90 ? 3 : ($days <= 180 ? 2 : 1)));
            $F = match($freq) { 1 => 1, 2 => 3, 3,4,5 => 4, default => 5 };
            $M = $monetary <= 30000 ? 1 : ($monetary <= 120000 ? 2 : ($monetary <= 300000 ? 3 : ($monetary <= 600000 ? 4 : 5)));

            $segment = getRfmSegment($R, $F, $M);
            $updates[] = [$R, $F, $M, $segment, $row['id']];
        }
    }

    if (!empty($updates)) {
        db()->begin_transaction();
        foreach ($updates as $params) {
            execute("UPDATE pelanggan SET rfm_recency = ?, rfm_frequency = ?, rfm_monetary = ?, rfm_segment = ? WHERE id = ?", $params);
        }
        db()->commit();
    }

    execute("
        UPDATE pelanggan SET rfm_recency = NULL, rfm_frequency = NULL, rfm_monetary = NULL, rfm_segment = 'Others'
        WHERE id NOT IN (SELECT DISTINCT pelanggan_id FROM transaksi WHERE status = 'selesai')
    ");

    echo json_encode(['success' => true, 'updated' => count($updates), 'message' => 'Segmentasi berhasil diperbarui.']);

} catch (Exception $e) {
    db()->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>