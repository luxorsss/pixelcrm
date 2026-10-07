<?php
/**
 * Checkout Alias - Redirect to co.php
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: co.php" . $qs, true, 301);
exit;