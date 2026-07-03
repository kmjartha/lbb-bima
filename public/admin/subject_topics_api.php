<?php
/**
 * AJAX API untuk Subject Topics
 * Digunakan untuk mengambil subjects saat copy operation
 */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/scope.php';
require_once __DIR__ . '/../../includes/grading_helpers.php';
require_once __DIR__ . '/../../includes/permissions.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$sc = active_scope();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'get_subjects') {
        require_edit('subject_topics');
        
        $rombelId = (int)($input['rombel_id'] ?? 0);
        if (!$rombelId) {
            throw new RuntimeException('Rombel ID required');
        }
        
        // Verify rombel exists
        $stmt = $pdo->prepare("SELECT jenjang FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
        $stmt->execute(['id'=>$rombelId, 'y'=>$sc['year_id']]);
        $rombel = $stmt->fetch();
        if (!$rombel) {
            throw new RuntimeException('Rombel tidak ditemukan');
        }
        
        // Get user info
        $me = require_view('subject_topics');
        
        // Fetch subjects based on user role
        if (in_array($me['role'], ['guru','kepsek'], true)) {
            $s = $pdo->prepare(
                "SELECT DISTINCT s.id, s.kode, s.nama, e.kode AS elective_kode
                 FROM subjects s
                 JOIN rombel_subject_teachers rst ON rst.subject_id = s.id
                 JOIN teachers t ON t.id = rst.teacher_id
                 LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
                 LEFT JOIN electives e ON e.id = ec.elective_id
                 WHERE rst.rombel_id = :r
                   AND t.user_id = :u
                   AND (rst.semester IS NULL OR rst.semester = :sem)
                   AND s.deleted_at IS NULL
                   AND s.academic_year_id = :y
                 ORDER BY s.kode"
            );
            $s->execute(['r'=>$rombelId, 'u'=>$me['id'], 'sem'=>$sc['semester'], 'y'=>$sc['year_id']]);
        } else {
            $s = $pdo->prepare(
                "SELECT DISTINCT s.id, s.kode, s.nama, e.kode AS elective_kode 
                 FROM subjects s
                 JOIN subject_jenjang_map jm ON jm.subject_id=s.id
                 LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
                 LEFT JOIN electives e ON e.id = ec.elective_id
                 WHERE jm.jenjang=:j 
                   AND s.deleted_at IS NULL 
                   AND s.academic_year_id = :y
                 ORDER BY s.kode"
            );
            $s->execute(['j'=>$rombel['jenjang'], 'y'=>$sc['year_id']]);
        }
        
        $subjects = $s->fetchAll();
        $result = [
            'success' => true,
            'subjects' => array_map(function($subj) {
                return [
                    'id' => (int)$subj['id'],
                    'label' => esc(($subj['kode'] ? $subj['kode'] . ' — ' : '') . elective_subject_label($subj['nama'], $subj['elective_kode'] ?? null))
                ];
            }, $subjects)
        ];
        
        echo json_encode($result);
    } else {
        throw new RuntimeException('Unknown action');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
