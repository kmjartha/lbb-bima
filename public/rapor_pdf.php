<?php
/**
 * Stage 8+ — Rapor PDF export (server-side, via Dompdf).
 *
 * Same access rules as public/rapor.php (administrator / admin / kepsek /
 * guru-wali for their own rombel). Streams a real PDF file instead of
 * relying on the browser's Print dialog.
 *
 * Query: ?rombel_id=&student_id=  (period & semester come from the scope
 * topbar in session, same as the screen view).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/wali_helpers.php';
require_once __DIR__ . '/../includes/report_helpers.php';
require_once __DIR__ . '/../includes/rapor_render.php';
require_once __DIR__ . '/../includes/rapor_pdf_css.php';

$user = require_view('rapor');
$pdo  = db();
$sc   = active_scope();

$rid = int_or_null($_GET['rombel_id'] ?? null);
$sid = int_or_null($_GET['student_id'] ?? null);
if (!$rid || !$sid) {
    http_response_code(400);
    die('rombel_id dan student_id wajib diisi.');
}

$rombel  = assert_can_access_rombel($user, $rid); // 403/redirect internally if not allowed
$members = rombel_members($rid);
$student = student_by_id($sid);
$isMember = false;
foreach ($members as $m) { if ((int)$m['id'] === $sid) { $isMember = true; break; } }
if (!$student || !$isMember) {
    http_response_code(404);
    die('Siswa tidak ditemukan pada rombel ini.');
}

$school = $pdo->query("SELECT * FROM school_profile WHERE id = 1")->fetch() ?: [];
$tpl    = report_template_for($rombel['jenjang']);
$sigs   = report_signatures_for($rombel['jenjang'], (int)$rombel['id']);

audit('rapor_pdf_export', 'student:' . $sid, ['rombel_id' => $rid, 'sem' => $sc['semester'], 'period' => $sc['period']]);

$bodyHtml = rapor_render_body([
    'student'     => $student,
    'rombel'      => $rombel,
    'school'      => $school,
    'tpl'         => $tpl,
    'sigs'        => $sigs,
    'scope'       => $sc,
    'uploadsBase' => __DIR__,
    'forPdf'      => true,
]);

$html = '<!doctype html><html><head><meta charset="utf-8">'
      . '<style>' . rapor_pdf_css() . '</style></head><body>'
      . $bodyHtml
      . '</body></html>';

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Dompdf needs two writable locations: a persistent font *cache* (where it
// stores parsed font metrics so it doesn't re-parse fonts on every request)
// and a scratch *temp* dir (used transiently when subsetting fonts, e.g.
// tempnam() calls inside Cpdf::processFont()). Keeping them in the same
// folder is fine as long as that folder is genuinely writable by the web
// server user — if it isn't, tempnam() fails silently and Dompdf crashes
// with a confusing "Path cannot be empty" error deep inside font handling.
//
// is_writable() alone doesn't always catch this — open_basedir
// restrictions (very common in XAMPP/MAMP php.ini configs) can block
// tempnam() specifically while is_writable() still reports true. So we
// actually call tempnam() here, the same call Dompdf will make, and bail
// out with a clear message if it fails instead of letting Dompdf crash
// deep inside font subsetting.
$cacheDir = __DIR__ . '/../storage/cache/dompdf';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
}
$probe = is_dir($cacheDir) ? @tempnam($cacheDir, 'probe_') : false;
if ($probe === false) {
    // storage/cache/dompdf isn't usable — fall back to the system temp dir.
    $cacheDir = sys_get_temp_dir();
    $probe = @tempnam($cacheDir, 'probe_');
}
if ($probe === false) {
    http_response_code(500);
    die(
        "Tidak dapat membuat PDF: server tidak bisa menulis file sementara.\n" .
        "Folder yang dicoba: {$cacheDir}\n" .
        "Kemungkinan sebab: izin folder (chmod), atau setting 'open_basedir' di php.ini " .
        "yang membatasi PHP hanya boleh menulis ke folder tertentu.\n" .
        "Solusi: pastikan 'storage/cache' writable (chmod -R 775 storage/cache), " .
        "dan pastikan folder project ini termasuk dalam open_basedir di php.ini Anda."
    );
}
@unlink($probe);

$options = new Options();
$options->setIsRemoteEnabled(false);     // no remote URL fetching — images are local filesystem paths
$options->setIsHtml5ParserEnabled(true);
// Append the project root to dompdf's existing chroot list (which already
// includes dompdf's own install dir, e.g. for its bundled lib/fonts) —
// setChroot() REPLACES the list, so passing an array here merges both.
$options->setChroot(array_merge($options->getChroot(), [realpath(__DIR__ . '/..')]));
$options->setFontCache($cacheDir);
$options->setTempDir($cacheDir);
$options->setDefaultFont('DejaVu Sans'); // bundled font with full Indonesian/Unicode glyph coverage

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$fileName = 'Rapor_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $student['nama']) . '_' . $sc['semester'] . '_' . $sc['period'] . '.pdf';

$dompdf->stream($fileName, ['Attachment' => true]);
