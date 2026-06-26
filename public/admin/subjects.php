<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/scope.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/grading_helpers.php';
require_admin_any();

$pdo = db();
$sc = active_scope();
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);

// Setup Search & Pagination
$q      = trim((string)($_GET['q'] ?? ''));
$limit  = 15;
$page   = max(1, (int)($_GET['p'] ?? 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'save_category') {
            $id = int_or_null($_POST['id'] ?? null);
            $nama = req_str($_POST, 'nama', 80);
            if ($id) {
                $pdo->prepare("UPDATE subject_categories SET nama=:n WHERE id=:id AND academic_year_id = :y")
                    ->execute(['n'=>$nama,'id'=>$id,'y'=>$sc['year_id']]);
                audit('save', 'subject_category:' . $id);
                flash('success', 'Kategori disimpan.');
            } else {
                try {
                    $pdo->prepare("INSERT INTO subject_categories (academic_year_id, nama) VALUES (:y, :n)")
                        ->execute(['y'=>$sc['year_id'],'n'=>$nama]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Kategori dengan nama tersebut sudah ada di tahun ajaran ini.');
                    }
                    throw $e;
                }
                audit('save', 'subject_category:' . $pdo->lastInsertId());
                flash('success', 'Kategori disimpan.');
            }
            redirect('admin/subjects.php');
        }

        if ($op === 'delete_category') {
            $id = int_or_null($_POST['id'] ?? null);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE category_id = :id AND academic_year_id = :y AND deleted_at IS NULL");
            $stmt->execute(['id'=>$id, 'y'=>$sc['year_id']]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('Kategori masih digunakan oleh mata pelajaran di tahun ajaran aktif.');
            }
            $pdo->prepare("DELETE FROM subject_categories WHERE id = :id AND academic_year_id = :y")
                ->execute(['id'=>$id, 'y'=>$sc['year_id']]);
            audit('delete', 'subject_category:' . $id);
            flash('success', 'Kategori dihapus.');
            redirect('admin/subjects.php');
        }

        if ($op === 'save') {
            $id   = int_or_null($_POST['id'] ?? null);
            if ($id) {
                $chk = $pdo->prepare("SELECT elective_class_id FROM subjects WHERE id = :id AND academic_year_id = :y");
                $chk->execute(['id' => $id, 'y' => $sc['year_id']]);
                if ($chk->fetchColumn()) {
                    throw new RuntimeException('Mapel ini berasal dari opsi Mapel Pilihan dan hanya bisa diubah lewat halaman Mapel Pilihan.');
                }
            }
            $kode = req_str($_POST, 'kode', 20);
            $nama = req_str($_POST, 'nama', 120);
            $catId = int_or_null($_POST['category_id'] ?? null);
            $newCat = trim((string)($_POST['new_category'] ?? ''));
            $jenjangs = $_POST['jenjang'] ?? [];
            if (!is_array($jenjangs) || !$jenjangs) throw new RuntimeException('Pilih minimal 1 jenjang.');
            foreach ($jenjangs as $j) if (!in_array($j, ['TK','SD','SMP','SMA'], true)) throw new RuntimeException('Jenjang invalid.');

            // KKM defaults per jenjang (e.g. kkm_default[SD] = 70)
            $kkmDefaults = [];
            foreach (['SD','SMP','SMA'] as $j) {
                $raw = $_POST['kkm_default'][$j] ?? null;
                if ($raw !== null && $raw !== '') {
                    $v = (float)$raw;
                    if ($v < 0 || $v > 100) throw new RuntimeException('KKM default ' . $j . ' harus antara 0-100.');
                    $kkmDefaults[$j] = $v;
                }
            }
            // KKM per-tingkat overrides (e.g. kkm_tingkat[7] = 78)
            $kkmOverrides = [];
            $rawOverrides = $_POST['kkm_tingkat'] ?? [];
            if (is_array($rawOverrides)) {
                foreach ($rawOverrides as $t => $v) {
                    $t = (int)$t;
                    if ($t < 1 || $t > 12) continue;
                    if ($v === '' || $v === null) continue;
                    $fv = (float)$v;
                    if ($fv < 0 || $fv > 100) throw new RuntimeException('KKM kelas ' . $t . ' harus antara 0-100.');
                    $kkmOverrides[$t] = $fv;
                }
            }

            $pdo->beginTransaction();
            // Inline category create
            if ($newCat !== '') {
                $stmt = $pdo->prepare("SELECT id FROM subject_categories WHERE nama = :n AND academic_year_id = :y");
                $stmt->execute(['n' => $newCat, 'y' => $sc['year_id']]);
                $catId = (int)($stmt->fetchColumn() ?: 0);
                if (!$catId) {
                    $pdo->prepare("INSERT INTO subject_categories (academic_year_id, nama) VALUES (:y, :n)")
                        ->execute(['y' => $sc['year_id'], 'n' => $newCat]);
                    $catId = (int)$pdo->lastInsertId();
                }
            }

            if ($id) {
                $pdo->prepare("UPDATE subjects SET kode=:k, nama=:n, category_id=:c WHERE id=:id AND academic_year_id=:y")
                    ->execute(['k'=>$kode,'n'=>$nama,'c'=>$catId,'id'=>$id,'y'=>$sc['year_id']]);
            } else {
                $pdo->prepare("INSERT INTO subjects (academic_year_id, kode, nama, category_id) VALUES (:y,:k,:n,:c)")
                    ->execute(['y'=>$sc['year_id'],'k'=>$kode,'n'=>$nama,'c'=>$catId]);
                $id = (int)$pdo->lastInsertId();
            }
            // Reset jenjang map
            $pdo->prepare("DELETE FROM subject_jenjang_map WHERE subject_id = :id")->execute(['id'=>$id]);
            $insJ = $pdo->prepare("INSERT INTO subject_jenjang_map (subject_id, jenjang) VALUES (:s,:j)");
            foreach ($jenjangs as $j) $insJ->execute(['s'=>$id,'j'=>$j]);

            // Rebuild KKM rows (default per jenjang, overridable per tingkat)
            subject_kkm_save($id, $jenjangs, $kkmDefaults, $kkmOverrides);

            $pdo->commit();
            audit('save', 'subject:' . $id);
            flash('success', 'Mata pelajaran disimpan.');
            redirect('admin/subjects.php');
        }

        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $chk = $pdo->prepare("SELECT elective_class_id FROM subjects WHERE id = :id AND academic_year_id = :y");
            $chk->execute(['id' => $id, 'y' => $sc['year_id']]);
            if ($chk->fetchColumn()) {
                throw new RuntimeException('Mapel ini berasal dari opsi Mapel Pilihan. Hapus opsinya lewat halaman Mapel Pilihan.');
            }
            $pdo->prepare("UPDATE subjects SET deleted_at = NOW() WHERE id = :id AND academic_year_id = :y")
                ->execute(['id'=>$id, 'y'=>$sc['year_id']]);
            audit('delete', 'subject:' . $id);
            flash('success', 'Mata pelajaran dihapus.');
            redirect('admin/subjects.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}

$cats = $pdo->prepare(
    "SELECT id, nama FROM subject_categories WHERE academic_year_id = :y ORDER BY nama"
);
$cats->execute(['y' => $sc['year_id']]);
$cats = $cats->fetchAll();

// Build Search and Pagination Query
$conds = ["s.deleted_at IS NULL", "s.academic_year_id = :y"];
$params = ['y' => $sc['year_id']];

if ($q !== '') {
    $conds[] = "(s.kode LIKE :q1 OR s.nama LIKE :q2)";
    $params['q1'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
}

$whereSql = "WHERE " . implode(" AND ", $conds);

// Count total records
$countSql = "SELECT COUNT(*) FROM subjects s $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();

// Pagination offsets
$totalPages = max(1, (int)ceil($totalRows / $limit));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

// Fetch rows
$rowSql = "SELECT s.*, c.nama AS cat_nama, e.id AS elective_id, e.kode AS elective_kode,
                  GROUP_CONCAT(jm.jenjang ORDER BY FIELD(jm.jenjang,'TK','SD','SMP','SMA') SEPARATOR ',') AS jenjangs
           FROM subjects s
           LEFT JOIN subject_categories c ON c.id = s.category_id
           LEFT JOIN subject_jenjang_map jm ON jm.subject_id = s.id
           LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
           LEFT JOIN electives e ON e.id = ec.elective_id
           $whereSql
           GROUP BY s.id ORDER BY s.kode
           LIMIT $limit OFFSET $offset";

$stmtRows = $pdo->prepare($rowSql);
$stmtRows->execute($params);
$rows = $stmtRows->fetchAll();

// Pull KKM maps for all listed subjects in one query (avoid N+1).
$kkmBySubject = [];
if ($rows) {
    $ids = array_map(fn($r) => (int)$r['id'], $rows);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stK = $pdo->prepare("SELECT subject_id, tingkat, kkm FROM subject_kkm WHERE subject_id IN ($in)");
    $stK->execute($ids);
    foreach ($stK->fetchAll() as $k) {
        $kkmBySubject[(int)$k['subject_id']][(int)$k['tingkat']] = (float)$k['kkm'];
    }
}

/** Ringkasan KKM per jenjang untuk satu subject, mis. "SD 70 · SMP 75-78 · SMA 75". */
function kkm_summary_for_subject(array $kkmMap): string
{
    if (!$kkmMap) return '—';
    $parts = [];
    foreach (['SD' => [1,2,3,4,5,6], 'SMP' => [7,8,9], 'SMA' => [10,11,12]] as $jenjang => $tingkatList) {
        $vals = [];
        foreach ($tingkatList as $t) if (isset($kkmMap[$t])) $vals[] = $kkmMap[$t];
        if (!$vals) continue;
        $min = min($vals); $max = max($vals);
        $parts[] = $jenjang . ' ' . ($min === $max ? fmt_kkm($min) : fmt_kkm($min) . '-' . fmt_kkm($max));
    }
    return $parts ? implode(' · ', $parts) : '—';
}

$edit = null; $editJ = []; $editKkm = [];
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = :id AND deleted_at IS NULL AND academic_year_id = :y");
    $stmt->execute(['id'=>$editId,'y'=>$sc['year_id']]);
    $edit = $stmt->fetch();
    if ($edit && $edit['elective_class_id']) {
        $ecRow = $pdo->prepare("SELECT elective_id FROM elective_classes WHERE id = :id");
        $ecRow->execute(['id' => $edit['elective_class_id']]);
        $electiveId = (int)($ecRow->fetchColumn() ?: 0);
        flash('error', 'Mapel ini berasal dari opsi Mapel Pilihan dan hanya bisa diubah lewat halaman Mapel Pilihan.');
        redirect('admin/electives.php' . ($electiveId ? '?edit=' . $electiveId : ''));
    }
    if ($edit) {
        $j = $pdo->prepare("SELECT jenjang FROM subject_jenjang_map WHERE subject_id = :id");
        $j->execute(['id'=>$editId]);
        $editJ = $j->fetchAll(PDO::FETCH_COLUMN);
        $editKkm = subject_kkm_map($editId);
    }
}

$page_title = 'Mata Pelajaran';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="row">
  <div class="card" style="flex: 1; min-width: 320px">
    <div class="card-header"><h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Mapel</h3>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/subjects.php')) ?>">Batal</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="row">
          <div class="field"><label class="label">Kode *</label><input class="input" name="kode" required value="<?= esc($edit['kode'] ?? '') ?>"></div>
          <div class="field" style="flex: 2"><label class="label">Nama *</label><input class="input" name="nama" required value="<?= esc($edit['nama'] ?? '') ?>"></div>
        </div>
        <div class="field">
          <label class="label">Kategori</label>
          <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap">
            <select class="select" name="category_id" style="flex:1; min-width:220px">
              <option value="">— Pilih kategori —</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= ($edit['category_id'] ?? null)==$c['id']?'selected':'' ?>><?= esc($c['nama']) ?></option>
              <?php endforeach; ?>
            </select>
            <a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/subject_categories.php')) ?>">Kelola kategori</a>
          </div>
        </div>
        <div class="field">
          <label class="label">Jenjang &amp; KKM *</label>
          <p class="text-muted text-sm" style="margin:0 0 .5rem;">Centang jenjang, atur KKM default per jenjang. KKM dapat disesuaikan per tingkat kelas bila perlu.</p>
          <div id="jenjang-kkm-rows" style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach (['TK','SD','SMP','SMA'] as $j): ?>
              <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <label class="checkbox-row" style="min-width:70px;">
                  <input type="checkbox" class="jenjang-check" name="jenjang[]" value="<?= $j ?>" data-j="<?= $j ?>" <?= in_array($j, $editJ, true) ? 'checked' : '' ?>> <?= $j ?>
                </label>
                <?php if ($j !== 'TK'): ?>
                  <?php
                    $tingkatList = tingkat_for_jenjang($j);
                    $existingVals = array_intersect_key($editKkm, array_flip($tingkatList));
                    $defaultVal = $existingVals ? round(array_sum($existingVals) / count($existingVals), 2) : 70;
                  ?>
                  <span class="text-muted text-sm" style="min-width:80px;">kelas <?= $tingkatList[0] ?>-<?= $tingkatList[count($tingkatList)-1] ?></span>
                  <label class="text-sm text-muted" style="margin-left:auto;">KKM default</label>
                  <input type="number" class="input kkm-default-input" style="max-width:80px;" min="0" max="100" step="0.01"
                         name="kkm_default[<?= $j ?>]" data-j="<?= $j ?>" value="<?= esc((string)$defaultVal) ?>">
                <?php else: ?>
                  <span class="text-muted text-sm">tidak memiliki KKM (jenjang TK)</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <button type="button" id="kkm-advanced-toggle" class="btn btn-secondary btn-sm" style="margin-top:.5rem;">Sesuaikan KKM per tingkat kelas</button>
          <div id="kkm-advanced-panel" style="display:none; margin-top:.5rem; padding:.75rem; border:1px solid var(--border); border-radius:10px; background:rgba(0,0,0,.02);">
            <div id="kkm-tingkat-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap:8px;"></div>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <h3 class="card-title">Daftar Mapel (<?= $totalRows ?>)</h3>
        <form method="get" style="display:flex; gap:5px;">
            <input type="text" name="q" class="input input-sm" placeholder="Cari Kode/Nama..." value="<?= esc($q) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
            <?php if ($q): ?>
                <a href="subjects.php" class="btn btn-ghost btn-sm" title="Reset Pencarian">✕</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jenjang</th><th>KKM</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6"><div class="empty">Belum ada data.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <?php $isElective = !empty($r['elective_id']); ?>
          <tr>
            <td><strong><?= esc($r['kode']) ?></strong></td>
            <td><?= esc($r['nama']) ?>
              <?php if ($isElective): ?><span class="badge" title="Opsi mapel pilihan <?= esc($r['elective_kode']) ?>">Opsi (<?= esc($r['elective_kode']) ?>)</span><?php endif; ?>
            </td>
            <td><?= esc($r['cat_nama'] ?? '—') ?></td>
            <td><?php foreach (explode(',', (string)$r['jenjangs']) as $j) if ($j) echo '<span class="badge badge-primary" style="margin-right:4px">' . esc($j) . '</span>'; ?></td>
            <td class="text-sm text-muted"><?= esc(kkm_summary_for_subject($kkmBySubject[(int)$r['id']] ?? [])) ?></td>
            <td style="text-align:right; white-space:nowrap">
              <?php if ($isElective): ?>
                <a class="btn btn-secondary btn-sm" href="<?= esc(url('admin/electives.php?edit=' . (int)$r['elective_id'])) ?>" title="Kelola opsi ini lewat Mapel Pilihan">Kelola di Mapel Pilihan</a>
              <?php else: ?>
                <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $page>1 ? '&p='.$page : '' ?>">Edit</a>
                <form method="post" style="display:inline" data-confirm="Hapus mapel <?= esc($r['nama']) ?>?">
                  <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-body" style="border-top:1px solid var(--c-border); display:flex; justify-content:space-between; align-items:center;">
        <span class="text-sm text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></span>
        <div style="display:flex; gap:5px;">
            <?php if ($page > 1): ?>
                <a href="?p=<?= $page - 1 ?><?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-secondary btn-sm">← Prev</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?p=<?= $page + 1 ?><?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-secondary btn-sm">Next →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
(function () {
  var TINGKAT_BY_JENJANG = {
    SD:  [1,2,3,4,5,6],
    SMP: [7,8,9],
    SMA: [10,11,12]
  };
  // Existing per-tingkat KKM values from server (edit mode), e.g. {"7": 78, "8": 75}
  var existingOverrides = <?= $editKkm ? json_encode($editKkm, JSON_NUMERIC_CHECK | JSON_FORCE_OBJECT) : '{}' ?>;

  var grid = document.getElementById('kkm-tingkat-grid');
  var toggleBtn = document.getElementById('kkm-advanced-toggle');
  var panel = document.getElementById('kkm-advanced-panel');
  if (!grid || !toggleBtn || !panel) return;

  function defaultForJenjang(j) {
    var input = document.querySelector('.kkm-default-input[data-j="' + j + '"]');
    return input ? (parseFloat(input.value) || 70) : 70;
  }

  function isChecked(j) {
    var cb = document.querySelector('.jenjang-check[data-j="' + j + '"]');
    return cb ? cb.checked : false;
  }

  function renderGrid() {
    // Preserve any values the user already typed in this session before re-render
    var typed = {};
    grid.querySelectorAll('.tingkat-kkm-input').forEach(function (inp) {
      typed[inp.dataset.tingkat] = inp.value;
    });

    grid.innerHTML = '';
    Object.keys(TINGKAT_BY_JENJANG).forEach(function (j) {
      if (!isChecked(j)) return;
      TINGKAT_BY_JENJANG[j].forEach(function (t) {
        var val = typed[t] !== undefined ? typed[t] : (existingOverrides[t] !== undefined ? existingOverrides[t] : defaultForJenjang(j));
        var cell = document.createElement('div');
        cell.style.cssText = 'background:rgba(0,0,0,.03); border-radius:8px; padding:6px 8px;';
        cell.innerHTML = '<div class="text-muted" style="font-size:11px; margin-bottom:3px;">Kelas ' + t + '</div>' +
          '<input type="number" class="input tingkat-kkm-input" style="width:100%; padding:4px 6px;" min="0" max="100" step="0.01" ' +
          'name="kkm_tingkat[' + t + ']" data-tingkat="' + t + '" value="' + val + '">';
        grid.appendChild(cell);
      });
    });
  }

  toggleBtn.addEventListener('click', function () {
    var hidden = panel.style.display === 'none';
    panel.style.display = hidden ? 'block' : 'none';
    toggleBtn.textContent = hidden ? 'Sembunyikan KKM per tingkat kelas' : 'Sesuaikan KKM per tingkat kelas';
    if (hidden) renderGrid();
  });

  document.querySelectorAll('.jenjang-check, .kkm-default-input').forEach(function (el) {
    el.addEventListener('change', function () { if (panel.style.display !== 'none') renderGrid(); });
    el.addEventListener('input', function () { if (panel.style.display !== 'none') renderGrid(); });
  });
})();
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>