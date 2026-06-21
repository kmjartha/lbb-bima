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
        unset($_SESSION['scope']['year_id']);
    }
    $row = db()->query("SELECT * FROM academic_years WHERE is_active = 1 ORDER BY label DESC LIMIT 1")->fetch()
        ?: db()->query("SELECT * FROM academic_years ORDER BY label DESC LIMIT 1")->fetch();
    if (!$row) {
        // create a default academic year + semester ranges
        $label = date('Y') . '/' . (date('Y') + 1);
        db()->prepare("INSERT INTO academic_years (label, is_active) VALUES (:l, 1)")->execute(['l' => $label]);
        $id = (int)db()->lastInsertId();
        $y1 = (int)date('Y');
        $y2 = $y1 + 1;
        db()->prepare(
            "INSERT INTO semesters_state (academic_year_id, semester, start_date, end_date)
             VALUES (:y1,'ganjil',:g1s,:g1e),(:y2,'genap',:g2s,:g2e)"
        )->execute([
            'y1' => $id,
            'y2' => $id,
            'g1s' => "$y1-07-01",
            'g1e' => "$y1-12-31",
            'g2s' => "$y2-01-01",
            'g2e' => "$y2-06-30",
        ]);
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

/** Convenience: bind ":y" to the active year in a prepared statement. */
function bind_year(PDOStatement $st, string $key = 'y'): void
{
    $st->bindValue(':' . $key, (int) active_scope()['year_id'], PDO::PARAM_INT);
}

/** SQL fragment to scope a query by the active year. */
function year_where(string $alias = ''): string
{
    $a = $alias ? "$alias." : '';
    return "{$a}academic_year_id = :y";
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

function semester_date_window(int $yearId, string $semester): array
{
    if (!in_array($semester, ['ganjil','genap'], true)) {
        throw new InvalidArgumentException('Semester invalid.');
    }
    $stmt = db()->prepare("SELECT start_date, end_date FROM semesters_state WHERE academic_year_id = :y AND semester = :s");
    $stmt->execute(['y' => $yearId, 's' => $semester]);
    $row = $stmt->fetch();
    if ($row && $row['start_date'] && $row['end_date']) {
        return [$row['start_date'], $row['end_date']];
    }

    $stmt = db()->prepare("SELECT label FROM academic_years WHERE id = :y");
    $stmt->execute(['y' => $yearId]);
    $label = (string)$stmt->fetchColumn();
    $parts = explode('/', $label);
    $y1 = (int)($parts[0] ?? date('Y'));
    $y2 = (int)($parts[1] ?? ($y1 + 1));
    if ($semester === 'ganjil') {
        return ["$y1-07-01", "$y1-12-31"];
    }
    return ["$y2-01-01", "$y2-06-30"];
}

function set_scope(?int $yearId, ?string $semester, ?string $period): void
{
    if ($yearId !== null && $yearId > 0) {
        $stmt = db()->prepare("SELECT 1 FROM academic_years WHERE id = :i");
        $stmt->execute(['i' => $yearId]);
        if ($stmt->fetchColumn()) {
            $_SESSION['scope']['year_id'] = $yearId;
        } else {
            unset($_SESSION['scope']['year_id']);
        }
    }

    $_SESSION['scope']['semester'] = in_array($semester, ['ganjil','genap'], true) ? $semester : ($_SESSION['scope']['semester'] ?? 'ganjil');
    $_SESSION['scope']['period']   = in_array($period,   ['PTS','PAS'], true)      ? $period   : ($_SESSION['scope']['period']   ?? 'PTS');
}
