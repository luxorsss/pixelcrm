<?php
/**
 * Invoice with Pixel Alias - Redirect to invoice.php
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: invoice.php" . $qs, true, 301);
exit;