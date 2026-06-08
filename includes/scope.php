<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Global scope: Tahun Ajaran + Semester + Periode (PTS/PAS).
 * Stored in session, switchable from the topbar.
 */

function active_year(): array
{
    if (!empty($_SESSION['scope']['year_id'])) {
        $stmt = db()->prepare("SELECT * FROM academic_years WHERE id = :i");
        $stmt->execute(['i' => $_SESSION['scope']['year_id']]);
        $row = $stmt->fetch();
        if ($row) return $row;
    }
    $row = db()->query("SELECT * FROM academic_years WHERE is_active = 1 ORDER BY label DESC LIMIT 1")->fetch()
        ?: db()->query("SELECT * FROM academic_years ORDER BY label DESC LIMIT 1")->fetch();
    if (!$row) {
        // create a default
        db()->prepare("INSERT INTO academic_years (label, is_active) VALUES (:l, 1)")->execute(['l' => date('Y') . '/' . (date('Y')+1)]);
        $id = (int)db()->lastInsertId();
        db()->prepare("INSERT INTO semesters_state (academic_year_id, semester) VALUES (:y1,'ganjil'),(:y2,'genap')")->execute(['y1' => $id, 'y2' => $id]);
        $row = db()->query("SELECT * FROM academic_years WHERE id = $id")->fetch();
    }
    return $row;
}

function active_semester(): string
{
    $s = $_SESSION['scope']['semester'] ?? 'ganjil';
    return in_array($s, ['ganjil','genap'], true) ? $s : 'ganjil';
}

function active_period(): string
{
    $p = $_SESSION['scope']['period'] ?? 'PTS';
    return in_array($p, ['PTS','PAS'], true) ? $p : 'PTS';
}

function active_scope(): array
{
    $y = active_year();
    return [
        'year_id'  => (int)$y['id'],
        'year'     => $y['label'],
        'semester' => active_semester(),
        'period'   => active_period(),
    ];
}

/** Whether the active semester (Ganjil/Genap) is locked. PTS/PAS is no longer lockable separately. */
function scope_is_locked(): bool
{
    $sc = active_scope();
    return semester_is_locked((int)$sc['year_id'], $sc['semester']);
}

/** Lock state for a specific (year, semester). */
function semester_is_locked(int $yearId, string $semester): bool
{
    if (!in_array($semester, ['ganjil','genap'], true)) return false;
    $stmt = db()->prepare("SELECT semester_locked FROM semesters_state WHERE academic_year_id = :y AND semester = :s");
    $stmt->execute(['y' => $yearId, 's' => $semester]);
    return (int)$stmt->fetchColumn() === 1;
}

/** Lock state for both semesters of a year — useful for header & list views. */
function year_lock_states(int $yearId): array
{
    $stmt = db()->prepare("SELECT semester, semester_locked FROM semesters_state WHERE academic_year_id = :y");
    $stmt->execute(['y' => $yearId]);
    $out = ['ganjil' => false, 'genap' => false];
    foreach ($stmt->fetchAll() as $r) {
        $out[$r['semester']] = (int)$r['semester_locked'] === 1;
    }
    return $out;
}

function set_scope(?int $yearId, ?string $semester, ?string $period): void
{
    $_SESSION['scope']['year_id']  = $yearId  ?: ($_SESSION['scope']['year_id']  ?? null);
    $_SESSION['scope']['semester'] = in_array($semester, ['ganjil','genap'], true) ? $semester : ($_SESSION['scope']['semester'] ?? 'ganjil');
    $_SESSION['scope']['period']   = in_array($period,   ['PTS','PAS'], true)      ? $period   : ($_SESSION['scope']['period']   ?? 'PTS');
}
