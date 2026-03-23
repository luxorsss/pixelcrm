<?php
// Normalisasi WA ke format 62
function normalizeWa($wa) {
    $wa = preg_replace('/[^0-9]/', '', $wa);
    if (empty($wa)) return '';
    if (substr($wa, 0, 1) === '0') {
        $wa = '62' . substr($wa, 1);
    } elseif (substr($wa, 0, 2) !== '62') {
        if (strlen($wa) <= 11) {
            $wa = '62' . $wa;
        }
    }
    return $wa;
}

// Logika Penentuan Segmen RFM
function getRfmSegment($R, $F, $M) {
    $R = (int)$R; $F = (int)$F; $M = (int)$M;

    if ($R == 1 && $F == 1 && $M == 1) return 'Cold';
    if ($R <= 2 && $F >= 2 && $M >= 2) return 'Risk';
    if ($M >= 4 && $F >= 1) return 'Whale';
    if ($R >= 4 && $F >= 3 && $M >= 3) return 'Champ';
    if ($R >= 3 && $F == 2 && $M >= 2) return 'Loyal';
    if ($R == 5 && $F == 1 && $M >= 2) return 'Prime';
    if ($R == 5 && $F == 1 && $M == 1) return 'New';
    return 'Others';
}
?>