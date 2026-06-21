<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';

function electives_for_year(int $yearId): array
{
    $st = db()->prepare(
        "SELECT * FROM electives WHERE academic_year_id = :y AND deleted_at IS NULL ORDER BY kode, nama"
    );
    $st->execute(['y' => $yearId]);
    return $st->fetchAll();
}

function elective_by_id(int $id, ?int $yearId = null): ?array
{
    $sql = "SELECT * FROM electives WHERE id = :id AND deleted_at IS NULL";
    $params = ['id' => $id];
    if ($yearId !== null) {
        $sql .= " AND academic_year_id = :y";
        $params['y'] = $yearId;
    }
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
}

function elective_classes(int $electiveId): array
{
    $st = db()->prepare(
        "SELECT id, nama, kapasitas
         FROM elective_classes
         WHERE elective_id = :e AND deleted_at IS NULL
         ORDER BY id"
    );
    $st->execute(['e' => $electiveId]);
    return $st->fetchAll();
}

function elective_rombels_for(int $electiveId): array
{
    $st = db()->prepare(
        "SELECT r.id, r.jenjang, r.tingkat, r.nama
         FROM elective_rombels er
         JOIN rombel r ON r.id = er.rombel_id
         WHERE er.elective_id = :e AND r.deleted_at IS NULL
         ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama"
    );
    $st->execute(['e' => $electiveId]);
    return $st->fetchAll();
}

function elective_rombel_ids(int $electiveId): array
{
    $st = db()->prepare("SELECT rombel_id FROM elective_rombels WHERE elective_id = :e");
    $st->execute(['e' => $electiveId]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

function elective_class_counts(int $electiveId, string $semester): array
{
    $st = db()->prepare(
        "SELECT elective_class_id, COUNT(*) AS cnt
         FROM elective_assignments
         WHERE elective_id = :e AND semester = :sem
         GROUP BY elective_class_id"
    );
    $st->execute(['e' => $electiveId, 'sem' => $semester]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[(int)$row['elective_class_id']] = (int)$row['cnt'];
    }
    return $out;
}

function electives_for_rombel(int $rombelId): array
{
    $sc = active_scope();
    $st = db()->prepare(
        "SELECT DISTINCT e.*
         FROM electives e
         JOIN elective_rombels er ON er.elective_id = e.id
         WHERE er.rombel_id = :r
           AND e.academic_year_id = :y
           AND e.deleted_at IS NULL
         ORDER BY e.nama"
    );
    $st->execute(['r' => $rombelId, 'y' => $sc['year_id']]);
    return $st->fetchAll();
}

function elective_rombel_options(int $yearId, string $jenjang): array
{
    $st = db()->prepare(
        "SELECT id, jenjang, tingkat, nama
         FROM rombel
         WHERE academic_year_id = :y
           AND jenjang = :j
           AND deleted_at IS NULL
         ORDER BY tingkat, nama"
    );
    $st->execute(['y' => $yearId, 'j' => $jenjang]);
    return $st->fetchAll();
}
