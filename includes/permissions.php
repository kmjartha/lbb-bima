<?php
/**
 * Centralised role -> feature permission matrix.
 *
 * Roles:
 *   - administrator : Global full access to everything.
 *   - admin         : Operational data entry. Full on master data (incl. Login Pegawai
 *                     except viewing administrator credentials). Global view-only on
 *                     attendance recap, daily-grade recap, final PTS/PAS grades.
 *   - kepsek        : Per-jenjang. Full on Verifikasi Nilai (jenjang). View-only
 *                     (jenjang) on rombel & anggota, guru pengampu, attendance recap,
 *                     daily-grade recap, final PTS/PAS grades.
 *   - guru          : Single-view, scoped by assignment. Full on subjek penilaian,
 *                     penilaian harian, rekap nilai harian, nilai akhir PTS/PAS.
 *   - guru (wali=1) : All of guru, plus full access to absensi harian, rekap absensi,
 *                     catatan wali, character evaluation, leger nilai, rapor siswa.
 *                     View-only on rombel & anggota of his/her rombel.
 */
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Permission map.
 *   'view' / 'edit' => list of roles allowed.
 * Where Wali matters, we use the synthetic role 'wali' (resolved from
 * users.role='guru' AND is_wali=1) in addition to 'guru'.
 */
function _permission_matrix(): array
{
    return [
        // ---------- Master data ----------
        'school_profile'    => ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']],
        'academic_years'    => ['view' => ['administrator'],                          'edit' => ['administrator']],
        'predikat'          => ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']],
        'subjects'          => ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']],
        'subject_categories'=> ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']],
        'teachers'          => ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']],
        'students'          => ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']],
        'users'             => ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']], // admin can't see administrator credentials (enforced in users.php)
        'character_aspects' => ['view' => ['administrator','kepsek'],             'edit' => ['administrator','kepsek']],
        'extracurriculars'  => ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']],
        'report_templates'  => ['view' => ['administrator'],                          'edit' => ['administrator']],
        'audit_log'         => ['view' => ['administrator'],                          'edit' => ['administrator']],
        'profile'           => ['view' => ['administrator','admin','kepsek','guru'],    'edit' => ['administrator','admin','kepsek','guru']],

        // ---------- KBM ----------
        // Rombel & Anggota: admin full; kepsek view (jenjang); guru/wali view (assigned)
        'rombel'            => ['view' => ['administrator','admin','kepsek','guru'], 'edit' => ['administrator','admin']],
        'rombel_teachers'   => ['view' => ['administrator','admin','kepsek'],        'edit' => ['administrator','admin']],
        'subject_topics'    => ['view' => ['administrator','admin','guru'],          'edit' => ['administrator','admin','guru']],
        'electives'         => ['view' => ['administrator','admin'],                 'edit' => ['administrator','admin']],
        'elective_assignment'=>['view' => ['administrator','guru'],                 'edit' => ['administrator','guru']],

        // ---------- Penilaian ----------
        // Absensi harian: administrator/admin? and guru wali can input; kepsek can input
        // only for rombel in his own jenjang via the attendance helpers scope.
        'attendance'        => ['view' => ['administrator','kepsek','guru'],          'edit' => ['administrator','kepsek','guru']],
        'attendance_recap'  => ['view' => ['administrator','admin','kepsek','guru'], 'edit' => ['administrator','guru']],
        'grades_daily'      => ['view' => ['administrator','guru'],                  'edit' => ['administrator','guru']],
        'grades_topic_recap'=> ['view' => ['administrator','admin','kepsek','guru'], 'edit' => ['administrator','guru']],
        'final_grades'      => ['view' => ['administrator','admin','kepsek','guru'], 'edit' => ['administrator','guru']],
        'final_grades_review'=>['view' => ['administrator','kepsek'],                'edit' => ['administrator','kepsek']],

        // ---------- Catatan & Karakter (Wali only + Administrator) ----------
        'wali_notes'        => ['view' => ['administrator','guru'],                  'edit' => ['administrator','guru']], // guru must be wali (checked separately)
        'character_eval'    => ['view' => ['administrator','guru'],                  'edit' => ['administrator','guru']],
        'general_eval'      => ['view' => ['administrator','guru'],                  'edit' => ['administrator','guru']],
        'extracurricular_grades' => ['view' => ['administrator','admin','guru'],     'edit' => ['administrator','guru']],

        // ---------- Rapor & Leger ----------
        'leger'             => ['view' => ['administrator','admin','kepsek','guru'], 'edit' => ['administrator','guru']],
        'rapor'             => ['view' => ['administrator','guru'],                  'edit' => ['administrator','guru']],


    ];
}

/** Features that REQUIRE a guru to be wali=1 to access (when role is guru). */
function _wali_only_features(): array
{
    return [
        'attendance', 'attendance_recap',
        'wali_notes', 'character_eval', 'general_eval',
        'leger', 'rapor',
        'elective_assignment',
    ];
}

/** Returns true if the user can VIEW the feature. */
function can_view(string $feature, ?array $user = null): bool
{
    $u = $user ?? current_user();
    if (!$u) return false;
    $m = _permission_matrix()[$feature] ?? null;
    if (!$m) return false;
    if (!in_array($u['role'], $m['view'], true)) return false;
    if ($u['role'] === 'guru' && in_array($feature, _wali_only_features(), true)) {
        return !empty($u['is_wali']);
    }
    return true;
}

/** Returns true if the user can EDIT (write) the feature. */
function can_edit(string $feature, ?array $user = null): bool
{
    $u = $user ?? current_user();
    if (!$u) return false;
    $m = _permission_matrix()[$feature] ?? null;
    if (!$m) return false;
    if (!in_array($u['role'], $m['edit'], true)) return false;
    if ($u['role'] === 'guru' && in_array($feature, _wali_only_features(), true)) {
        return !empty($u['is_wali']);
    }
    return true;
}

/** True if the user only has view (not edit) for this feature. */
function is_view_only(string $feature, ?array $user = null): bool
{
    return can_view($feature, $user) && !can_edit($feature, $user);
}

/** Guard: require view-permission on the feature; otherwise 403. */
function require_view(string $feature): array
{
    $u = current_user();
    if (!$u) { header('Location: ' . url('login.php')); exit; }
    if (!can_view($feature, $u)) {
        http_response_code(403);
        die('403 — Anda tidak memiliki akses ke halaman ini.');
    }
    return $u;
}

/** Guard: require edit-permission on the feature; otherwise throw. */
function require_edit(string $feature): array
{
    $u = current_user();
    if (!$u) { header('Location: ' . url('login.php')); exit; }
    if (!can_edit($feature, $u)) {
        throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
    }
    return $u;
}
