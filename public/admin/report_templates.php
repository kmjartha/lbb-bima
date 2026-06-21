<?php
/**
 * Stage 8 — Report Display Settings.
 * Per jenjang (SD / SMP / SMA):
 *   - header & footer image (upload / hapus)
 *   - 3 slot tanda tangan tetap: kepsek, direktur, parent — nama/jabatan/TTD upload
 *   - slot wali otomatis diambil dari data wali kelas per rombel
 *   - urutan section rapor (drag-by-arrow)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/report_helpers.php';
require_administrator();

$pdo = db();
$err = null;

$jenjang = strtoupper((string)($_GET['j'] ?? 'SD'));
if (!in_array($jenjang, ['SD','SMP','SMA'], true)) $jenjang = 'SD';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $jenjang = strtoupper(req_str($_POST, 'jenjang', 4));
        if (!in_array($jenjang, ['SD','SMP','SMA'], true)) throw new RuntimeException('Jenjang invalid.');
        $op = (string)($_POST['op'] ?? '');
        $tpl = report_template_for($jenjang);

        if ($op === 'save_template') {
            $headerImg = $tpl['header_img']; $footerImg = $tpl['footer_img'];

            // Header upload / clear
            if (!empty($_POST['clear_header'])) $headerImg = null;
            if (!empty($_FILES['header_img']['name'])) {
                $headerImg = save_image_upload($_FILES['header_img'], 'reports', 'hdr_' . $jenjang);
            }
            if (!empty($_POST['clear_footer'])) $footerImg = null;
            if (!empty($_FILES['footer_img']['name'])) {
                $footerImg = save_image_upload($_FILES['footer_img'], 'reports', 'ftr_' . $jenjang);
            }

            // Layout order
            $layoutIn = $_POST['layout'] ?? [];
            $allowed  = rapor_default_layout();
            $layout   = [];
            foreach ($layoutIn as $k) {
                $k = (string)$k;
                if (in_array($k, $allowed, true) && !in_array($k, $layout, true)) $layout[] = $k;
            }
            // Append any missing keys at the end
            foreach ($allowed as $k) if (!in_array($k, $layout, true)) $layout[] = $k;

            // Hidden sections — anything NOT in visible[] map is hidden (except identitas).
            $visibleIn = (array)($_POST['visible'] ?? []);
            $hidden = [];
            foreach ($layout as $k) {
                if ($k === 'identitas') continue;
                if (empty($visibleIn[$k])) $hidden[] = $k;
            }

            report_template_save($jenjang, $headerImg, $footerImg, $layout, $hidden);
            audit('save_report_template', "jenjang:$jenjang");
            flash('success', "Template rapor $jenjang tersimpan.");
            redirect("admin/report_templates.php?j=$jenjang");
        }

        if ($op === 'save_signature') {
            $slot   = req_str($_POST, 'slot', 12);
            if (!in_array($slot, ['wali','kepsek','direktur','parent'], true)) throw new RuntimeException('Slot invalid.');
            $cur    = report_signatures_for($jenjang)[$slot];
            $nama   = opt_str($_POST, 'nama', 120);
            $jab    = opt_str($_POST, 'jabatan', 120);
            $ttd    = $cur['ttd_path'];
            if (!empty($_POST['clear_ttd'])) $ttd = null;
            if (!empty($_FILES['ttd']['name'])) {
                $ttd = save_image_upload($_FILES['ttd'], 'signatures', 'sig_' . $jenjang . '_' . $slot);
            }
            report_signature_save($jenjang, $slot, $nama, $jab, $ttd);
            audit('save_signature', "jenjang:$jenjang/slot:$slot");
            flash('success', "TTD slot " . ucfirst($slot) . " ($jenjang) disimpan.");
            redirect("admin/report_templates.php?j=$jenjang");
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$tpl    = report_template_for($jenjang);
$sigs   = report_signatures_for($jenjang);
$resolved = rapor_layout_resolve($tpl);
$layout   = $resolved['order'];
$hiddenSet = $resolved['hidden'];

$sectionLabels = [
    'identitas'       => 'Identitas Siswa',
    'character'       => 'Penilaian Karakter',
    'academic'        => 'Penilaian Akademik (Mapel)',
    'attendance'      => 'Kehadiran',
    'general_eval'    => 'Narasi Umum',
    'signatures'      => 'Tanda Tangan',
];

$page_title = 'Template Rapor';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Template Rapor — <?= esc($jenjang) ?></h3>
    <div class="row" style="gap:6px">
      <?php foreach (['SD','SMP','SMA'] as $j): ?>
        <a class="btn btn-sm <?= $j===$jenjang?'btn-primary':'btn-secondary' ?>" href="?j=<?= $j ?>"><?= $j ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="row" style="align-items: stretch">

  <!-- Header / Footer + Layout -->
  <form method="post" enctype="multipart/form-data" class="card" style="flex: 1.2; min-width: 360px">
    <?= csrf_field() ?>
    <input type="hidden" name="op" value="save_template">
    <input type="hidden" name="jenjang" value="<?= esc($jenjang) ?>">
    <div class="card-header"><h3 class="card-title">Header, Footer &amp; Urutan Section</h3></div>
    <div class="card-body">

      <div class="row" style="gap: var(--sp-4)">
        <div class="field" style="flex:1; min-width:200px">
          <label class="label">Header Image (kop rapor)</label>
          <?php if (!empty($tpl['header_img'])): ?>
            <div style="margin-bottom:6px"><img src="<?= esc(uploads_url($tpl['header_img'])) ?>" style="max-height:70px; border:1px solid var(--border); border-radius:6px"></div>
            <label class="text-sm"><input type="checkbox" name="clear_header" value="1"> Hapus header saat simpan</label>
          <?php endif; ?>
          <input class="input" type="file" name="header_img" accept="image/*">
        </div>
        <div class="field" style="flex:1; min-width:200px">
          <label class="label">Footer Image</label>
          <?php if (!empty($tpl['footer_img'])): ?>
            <div style="margin-bottom:6px"><img src="<?= esc(uploads_url($tpl['footer_img'])) ?>" style="max-height:70px; border:1px solid var(--border); border-radius:6px"></div>
            <label class="text-sm"><input type="checkbox" name="clear_footer" value="1"> Hapus footer saat simpan</label>
          <?php endif; ?>
          <input class="input" type="file" name="footer_img" accept="image/*">
        </div>
      </div>

      <div class="field" style="margin-top: var(--sp-4)">
        <label class="label">Urutan &amp; Visibilitas Section Rapor</label>
        <div class="text-sm text-muted" style="margin-bottom:6px">
          Geser ke atas/bawah untuk mengatur urutan. Klik ikon mata untuk
          menyembunyikan/menampilkan section pada rapor.
          <em>(Identitas siswa tidak dapat disembunyikan.)</em>
        </div>
        <ol id="layoutList" class="layout-list" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px">
          <?php foreach ($layout as $key):
            if (!isset($sectionLabels[$key])) continue;
            $isHidden = isset($hiddenSet[$key]);
            $isLocked = ($key === 'identitas');
          ?>
            <li class="layout-item"
                data-key="<?= esc($key) ?>"
                data-visible="<?= $isHidden ? '0' : '1' ?>"
                style="display:flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid var(--border); border-radius:6px; background:#fff; <?= $isHidden ? 'opacity:.55;' : '' ?>">
              <span style="cursor:grab">⠿</span>
              <strong style="flex:1"><?= esc($sectionLabels[$key]) ?><span class="hidden-tag text-sm text-muted" style="margin-left:6px; <?= $isHidden ? '' : 'display:none;' ?>">(disembunyikan)</span></strong>
              <span class="text-sm text-muted"><?= esc($key) ?></span>
              <button type="button" class="btn btn-ghost btn-sm" data-toggle-visible
                      title="<?= $isLocked ? 'Tidak dapat disembunyikan' : 'Tampilkan / sembunyikan' ?>"
                      <?= $isLocked ? 'disabled' : '' ?>>
                <span class="eye-on"  style="<?= $isHidden ? 'display:none;' : '' ?>" aria-label="Visible">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <span class="eye-off" style="<?= $isHidden ? '' : 'display:none;' ?>" aria-label="Hidden">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a19.77 19.77 0 0 1 4.22-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a19.77 19.77 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </span>
              </button>
              <button type="button" class="btn btn-ghost btn-sm" data-up>↑</button>
              <button type="button" class="btn btn-ghost btn-sm" data-down>↓</button>
              <input type="hidden" name="layout[]" value="<?= esc($key) ?>">
              <?php if ($isLocked): ?>
                <input type="hidden" name="visible[<?= esc($key) ?>]" value="1">
              <?php else: ?>
                <input type="hidden" class="visible-input" name="visible[<?= esc($key) ?>]" value="<?= $isHidden ? '' : '1' ?>" <?= $isHidden ? 'disabled' : '' ?>>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>

      <div style="text-align:right; margin-top: var(--sp-4)">
        <button class="btn btn-primary" type="submit">Simpan Template</button>
      </div>
    </div>
  </form>

  <!-- Signatures -->
  <div class="card" style="flex: 1; min-width: 360px">
    <div class="card-header"><h3 class="card-title">Tanda Tangan</h3></div>
    <div class="card-body">
      <div class="alert alert-info">
        Slot <strong>Wali</strong> tidak diatur di sini. Nama dan TTD wali akan otomatis diambil dari data wali kelas untuk setiap rombel.
      </div>
      <?php foreach (['kepsek','direktur','parent'] as $slot): $sg = $sigs[$slot]; ?>
        <form method="post" enctype="multipart/form-data" style="border:1px solid var(--border); border-radius:8px; padding:10px; margin-bottom:10px">
          <?= csrf_field() ?>
          <input type="hidden" name="op" value="save_signature">
          <input type="hidden" name="jenjang" value="<?= esc($jenjang) ?>">
          <input type="hidden" name="slot" value="<?= esc($slot) ?>">
          <div class="between"><strong><?= esc(ucfirst($slot)) ?></strong>
            <span class="text-sm text-muted"><?= $slot==='parent' ? 'Slot kosong (TTD orang tua)' : '' ?></span>
          </div>
          <div class="row" style="gap:8px; margin-top:6px">
            <div class="field" style="flex:1; min-width:160px"><label class="label">Nama</label>
              <input class="input" name="nama" maxlength="120" value="<?= esc($sg['nama'] ?? '') ?>">
            </div>
            <div class="field" style="flex:1; min-width:160px"><label class="label">Jabatan</label>
              <input class="input" name="jabatan" maxlength="120" value="<?= esc($sg['jabatan'] ?? '') ?>">
            </div>
          </div>
          <?php if (!empty($sg['ttd_path'])): ?>
            <div style="margin-top:6px"><img src="<?= esc(uploads_url($sg['ttd_path'])) ?>" style="max-height:50px; background:#fff; border:1px solid var(--border); border-radius:4px; padding:4px"></div>
            <label class="text-sm"><input type="checkbox" name="clear_ttd" value="1"> Hapus TTD saat simpan</label>
          <?php endif; ?>
          <div class="field" style="margin-top:6px"><label class="label">Upload TTD (PNG transparan dianjurkan)</label>
            <input class="input" type="file" name="ttd" accept="image/*">
          </div>
          <div style="text-align:right; margin-top:8px">
            <button class="btn btn-primary btn-sm" type="submit">Simpan TTD <?= esc(ucfirst($slot)) ?></button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
(function () {
  const list = document.getElementById('layoutList');
  if (!list) return;
  list.addEventListener('click', (e) => {
    const t = e.target;
    if (!(t instanceof Element)) return;
    const li = t.closest('.layout-item');
    if (!li) return;
    if (t.closest('[data-up]') && li.previousElementSibling) {
      list.insertBefore(li, li.previousElementSibling);
    } else if (t.closest('[data-down]') && li.nextElementSibling) {
      list.insertBefore(li.nextElementSibling, li);
    } else if (t.closest('[data-toggle-visible]')) {
      const btn = t.closest('[data-toggle-visible]');
      if (btn.hasAttribute('disabled')) return;
      const visible = li.dataset.visible === '1';
      const next = !visible;
      li.dataset.visible = next ? '1' : '0';
      li.style.opacity = next ? '' : '.55';
      const tag = li.querySelector('.hidden-tag');
      if (tag) tag.style.display = next ? 'none' : '';
      const on  = btn.querySelector('.eye-on');
      const off = btn.querySelector('.eye-off');
      if (on)  on.style.display  = next ? '' : 'none';
      if (off) off.style.display = next ? 'none' : '';
      const input = li.querySelector('.visible-input');
      if (input) {
        if (next) { input.disabled = false; input.value = '1'; }
        else      { input.disabled = true;  input.value = ''; }
      }
    }
  });
})();
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
