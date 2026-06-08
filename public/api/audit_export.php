<?php
/**
 * Stage 10 — Audit log CSV export endpoint.
 * Administrator only. Streams CSV; respects same filters as the viewer.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/audit_helpers.php';

$me = require_administrator();

$f = [
    'action'    => trim((string)($_GET['action']    ?? '')),
    'user_id'   => (int)   ($_GET['user_id']   ?? 0),
    'q'         => trim((string)($_GET['q']         ?? '')),
    'date_from' => trim((string)($_GET['date_from'] ?? '')),
    'date_to'   => trim((string)($_GET['date_to']   ?? '')),
];

audit('audit_export', null, $f);

while (ob_get_level() > 0) ob_end_clean();
audit_export_csv($f);
exit;
