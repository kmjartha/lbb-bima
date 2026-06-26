<?php
/**
 * Stage 9+ — Parent rapor PDF export (server-side, via Dompdf).
 *
 * Same publish-gating rule as public/parent/rapor.php — a parent can only
 * download a PDF for a (semester, period) that the school has marked
 * 'published'. The student is always resolved from the parent's own
 * session binding (parent_student()), never from a request parameter.
 *
 * Query: ?sem=ganjil|genap&pk=PTS|PAS  (defaults to active scope).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/parent_helpers.php';
require_once __DIR__ . '/../../includes/rapor_render.php';
require_once __DIR__ . '/../../includes/rapor_pdf_css.php';

$p       = require_parent();
$student = parent_student($p);
$sc      = active_scope();

$sem = in_array($_GET['sem'] ?? '', ['ganjil','genap'], true) ? $_GET['sem'] : $sc['semester'];
$pk  = in_array($_GET['pk']  ?? '', ['PTS','PAS'], true)      ? $_GET['pk']  : $sc['period'];

$requestedYearId = isset($_GET['year_id']) ? (int)$_GET['year_id'] : null;
$yearId = parent_resolve_year_id((int)$student['id'], $requestedYearId, (int)$sc['year_id']);

$rombel    = parent_rombel_for_year((int)$student['id'], $yearId);
$published = $rombel ? rapor_is_published((int)$rombel['id'], (int)$student['id'], $sem, $pk, $yearId) : false;

if (!$rombel || !$published) {
    http_response_code(403);
    die('Rapor untuk periode ini belum dipublikasi.');
}

$yearLabel = $sc['year'];
foreach (parent_available_years((int)$student['id']) as $y) {
    if ((int)$y['id'] === $yearId) { $yearLabel = (string)$y['label']; break; }
}

$pdo    = db();
$school = $pdo->query("SELECT * FROM school_profile WHERE id = 1")->fetch() ?: [];
$tpl    = report_template_for($rombel['jenjang']);
$sigs   = report_signatures_for($rombel['jenjang'], (int)$rombel['id']);

audit('parent_rapor_pdf_export', 'student:' . $student['id'], ['sem' => $sem, 'pk' => $pk, 'year_id' => $yearId]);

$bodyHtml = rapor_render_body([
    'student'     => $student,
    'rombel'      => $rombel,
    'school'      => $school,
    'tpl'         => $tpl,
    'sigs'        => $sigs,
    'scope'       => ['year' => $yearLabel, 'year_id' => $yearId, 'semester' => $sem, 'period' => $pk],
    'uploadsBase' => __DIR__ . '/..',
    'forPdf'      => true,
]);

$html = '<!doctype html><html><head><meta charset="utf-8">'
      . '<style>' . rapor_pdf_css() . '</style></head><body>'
      . $bodyHtml
      . '</body></html>';

require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// See public/rapor_pdf.php for why this probes with a real tempnam() call
// instead of just is_writable() — open_basedir restrictions (common in
// XAMPP/MAMP) can block tempnam() specifically while is_writable() still
// reports true, which is what produces Dompdf's confusing
// "Path cannot be empty" crash deep inside font subsetting.
$cacheDir = '';
$triedDirs = [];
$attemptDirs = [
    __DIR__ . '/../../storage/cache/dompdf',
    __DIR__ . '/../../storage/cache',
    __DIR__ . '/../../storage',
    ini_get('upload_tmp_dir') ?: '',
    sys_get_temp_dir(),
    '/tmp',
];
foreach ($attemptDirs as $attemptDir) {
    if ($attemptDir === '') {
        continue;
    }
    $dir = rtrim($attemptDir, DIRECTORY_SEPARATOR);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        $triedDirs[] = $dir;
        continue;
    }
    $probe = @tempnam($dir, 'probe_');
    if ($probe !== false) {
        @unlink($probe);
        $cacheDir = $dir;
        break;
    }
    $triedDirs[] = $dir;
}
if ($cacheDir === '') {
    http_response_code(500);
    die(
        "Tidak dapat membuat PDF: server tidak bisa menulis file sementara.\n" .
        "Folder yang dicoba: " . ($triedDirs ? implode($triedDirs, ', ') : '(tidak ada)') . "\n" .
        "Kemungkinan sebab: izin folder (chmod), atau setting 'open_basedir' di php.ini " .
        "yang membatasi PHP hanya boleh menulis ke folder tertentu.\n" .
        "Solusi: pastikan 'storage/cache' writable (chmod -R 775 storage/cache), " .
        "dan pastikan folder project ini termasuk dalam open_basedir di php.ini Anda."
    );
}

$options = new Options();
$options->setIsRemoteEnabled(false);
$options->setIsHtml5ParserEnabled(true);
$options->setChroot(array_merge($options->getChroot(), [realpath(__DIR__ . '/../..')]));
$options->setFontCache($cacheDir);
$options->setTempDir($cacheDir);
$options->setDefaultFont('DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$fileName = 'Rapor_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $student['nama']) . '_' . $sem . '_' . $pk . '.pdf';

$dompdf->stream($fileName, ['Attachment' => true]);
