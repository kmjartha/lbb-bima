<?php
/**
 * Stage 10 — Audit Log Viewer.
 *
 * Administrator only. Filters: action, user, free-text (target/user_label/action),
 * date range. Pagination. Export CSV link respects current filters.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/audit_helpers.php';

require_administrator();

$f = [
    'action'    => trim((string)($_GET['action']    ?? '')),
    'user_id'   => (int)   ($_GET['user_id']   ?? 0),
    'q'         => trim((string)($_GET['q']         ?? '')),
    'date_from' => trim((string)($_GET['date_from'] ?? '')),
    'date_to'   => trim((string)($_GET['date_to']   ?? '')),
];
$page = (int)($_GET['page'] ?? 1);
$res  = audit_query($f, $page, 50);

$actions = audit_distinct_actions();
$users   = audit_distinct_users();

$exportQs = http_build_query(array_filter($f, fn($v) => $v !== '' && $v !== 0));

$page_title = 'Audit Log';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Audit Log <span class="badge"><?= number_format($res['total']) ?> baris</span></h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('api/audit_export.php' . ($exportQs ? '?' . $exportQs : ''))) ?>">⬇️ Export CSV</a>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="gap: var(--sp-3); align-items:end; flex-wrap:wrap">
      <div class="field" style="min-width:160px">
        <label class="label">Aksi</label>
        <select class="select" name="action">
          <option value="">— semua —</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?= esc($a) ?>" <?= $f['action']===$a?'selected':'' ?>><?= esc($a) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="min-width:200px">
        <label class="label">Pengguna (staff)</label>
        <select class="select" name="user_id">
          <option value="0">— semua —</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $f['user_id']===(int)$u['id']?'selected':'' ?>>
              <?= esc($u['nama'] . ' (' . $u['niy'] . ' · ' . $u['role'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="min-width:220px; flex:1">
        <label class="label">Cari (target / nama / aksi)</label>
        <input class="input" name="q" value="<?= esc($f['q']) ?>" placeholder="user:12, parent_view_rapor, …">
      </div>
      <div class="field" style="min-width:140px">
        <label class="label">Dari</label>
        <input class="input" type="date" name="date_from" value="<?= esc($f['date_from']) ?>">
      </div>
      <div class="field" style="min-width:140px">
        <label class="label">Sampai</label>
        <input class="input" type="date" name="date_to" value="<?= esc($f['date_to']) ?>">
      </div>
      <div class="field">
        <button class="btn btn-primary">Filter</button>
        <a class="btn btn-ghost" href="<?= esc(url('admin/audit_log.php')) ?>">Reset</a>
      </div>
    </form>
  </div>

  <div class="table-wrap">
    <table class="t">
      <thead><tr><th style="width:160px">Waktu</th><th>Pengguna</th><th>Aksi</th><th>Target</th><th>IP</th><th>Meta</th></tr></thead>
      <tbody>
        <?php if (!$res['rows']): ?>
          <tr><td colspan="6"><div class="empty">Tidak ada baris cocok dengan filter.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($res['rows'] as $r): ?>
          <tr>
            <td class="text-sm text-muted"><?= esc($r['created_at']) ?></td>
            <td>
              <?= esc($r['user_label'] ?? '—') ?>
              <?php if ($r['user_id']): ?><div class="text-xs text-muted">#<?= (int)$r['user_id'] ?></div><?php endif; ?>
            </td>
            <td><span class="badge"><?= esc($r['action']) ?></span></td>
            <td class="text-sm" style="word-break:break-all"><?= esc($r['target'] ?? '—') ?></td>
            <td class="text-xs text-muted"><?= esc($r['ip'] ?? '—') ?></td>
            <td class="text-xs" style="max-width:280px; word-break:break-all">
              <?php if (!empty($r['meta_json'])): ?>
                <code style="background:#f3f4f6; padding:2px 4px; border-radius:4px"><?= esc(mb_strimwidth((string)$r['meta_json'], 0, 120, '…')) ?></code>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($res['pages'] > 1):
    $qs = $_GET; unset($qs['page']);
    $qsBase = http_build_query($qs);
    $url = url('admin/audit_log.php') . '?' . $qsBase . ($qsBase ? '&' : '');
    $cur = $res['page']; $max = $res['pages'];
  ?>
    <div class="card-body" style="display:flex; justify-content:space-between; align-items:center">
      <div class="text-sm text-muted">Halaman <?= $cur ?> / <?= $max ?> · <?= number_format($res['total']) ?> baris total</div>
      <div class="row" style="gap:.4rem">
        <a class="btn btn-ghost btn-sm <?= $cur<=1?'disabled':'' ?>" href="<?= esc($url . 'page=' . max(1,$cur-1)) ?>">‹ Sebelumnya</a>
        <a class="btn btn-ghost btn-sm <?= $cur>=$max?'disabled':'' ?>" href="<?= esc($url . 'page=' . min($max,$cur+1)) ?>">Berikutnya ›</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
