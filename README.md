# Sekolah Grading — SD/SMP/SMA

Multi-role student grading dashboard (Indonesian K–12). PHP 8 + MySQL.

## Setup (XAMPP / Laragon)

1. Extract this folder into your web root, e.g. `htdocs/sekolah-grading/`.
2. Open PHPMyAdmin → buat database **`sekolah_grading`** (utf8mb4_general_ci).
3. Import `sql/schema.sql`.
4. Edit `includes/config.php` jika perlu (DB user/pass, base_url).
5. Buka URL lokal langsung di browser: `http://localhost/sekolah-grading/install.php` → akan hash semua password seed. Jangan buka URL `localhost` dari iframe/preview Lovable. Jika ada masalah database/PDO/schema, installer akan menampilkan detail dan langkah cek.
6. **Hapus `install.php` setelah selesai.**
7. Buka `http://localhost/sekolah-grading/public/login.php`.

## Akun seed

| Role          | NIY          | Password |
|---------------|--------------|----------|
| Administrator | 1990010001   | 0001     |
| Admin         | 1990020002   | 0002     |
| Kepsek SD     | 1990030003   | 0003     |
| Kepsek SMP    | 1990040004   | 0004     |
| Kepsek SMA    | 1990050005   | 0005     |
| Guru / Wali   | 1990060006   | 0006     |
| Guru          | 1990070007   | 0007     |

Parent: NISN `0123456701`–`0123456704`, password = `ddmmyyyy` tanggal lahir siswa (lihat output `install.php`).

## Scope global

Topbar: Tahun Ajaran · Semester (Ganjil/Genap) · Periode (PTS/PAS). Setiap perubahan langsung tersimpan di session dan dipakai semua halaman.

## Lock/Unlock PTS / PAS

Halaman **Tahun Ajaran**, klik badge PTS/PAS pada baris semester untuk toggle.

## Stage 3 — Rombel + Guru Pengampu + Subjek Penilaian

- `admin/rombel.php`               — CRUD rombel + assign wali kelas + tambah/keluarkan anggota siswa.
- `admin/rombel_teachers.php`      — Mapping guru × mapel × rombel × semester.
- `admin/subject_topics.php`       — Subjek penilaian (chapter/topik) per mapel × rombel + ranah/kategori/bobot.

## Stage 4 (rilis ini) — Absensi

- `public/attendance.php`          — Input absensi harian per rombel × tanggal (status H/I/S/A + catatan, bulk-set, live counters).
- `public/attendance_recap.php`    — Rekap bulanan per rombel: ringkasan jumlah H/I/S/A, % hadir, matrix harian, dan **Export CSV**.
- `includes/attendance_helpers.php` — `accessible_rombel($user)` membatasi akses guru ke rombel yang ia walikan / ampu.

**Akses per role**
| Role          | Rombel terlihat                                      | Bisa simpan? |
|---------------|------------------------------------------------------|--------------|
| Administrator | semua rombel di TA aktif                             | ✅           |
| Admin         | semua rombel di TA aktif                             | ✅           |
| Kepsek        | rombel pada `jenjang` kepsek tsb                     | ❌ read-only |
| Guru          | rombel di mana ia **wali** atau **guru pengampu**    | ✅           |
| Parent        | tidak bisa akses (akan muncul di portal Stage 9)     | —            |

Semua scope-aware (TA aktif menentukan rombel mana yang masuk daftar).

## Build order

1. ✅ Auth & Shell  
2. ✅ Master Data + KKM  
3. ✅ Rombel + Guru Pengampu + Subjek Penilaian  
4. ✅ **Absensi (rilis ini)**  
5. ⏳ Penilaian Harian SKP  
6. ⏳ Nilai Akhir + Verifikasi Kepsek  
7. ⏳ Catatan Wali + Character Evaluation  
8. ⏳ Leger + Rapor PDF  
9. ⏳ Parent Portal + publish gating  
10. ⏳ Audit log + Recent Activity widget

---

## Stage 7 (rilis ini) — Catatan Wali + Character Evaluation + Ekskul

Halaman baru (`/public/`):
- `wali_notes.php` — Catatan wali kelas per siswa per (semester, PTS/PAS).
- `character_eval.php` — Penilaian aspek karakter (NI / SI / WI / PR + remark) per siswa per (semester, PTS/PAS). Dua mode tampilan:
  - **Matrix**: siswa × aspek (skala saja) — cepat untuk pengisian massal.
  - **Fokus siswa**: form vertikal lengkap dengan kolom remark per aspek.
- `general_eval.php` — Narasi umum per siswa per (semester, PTS/PAS).
- `extracurricular_grades.php` — Predikat ekskul (A / B / C / D + catatan) per (ekskul, semester, TA). Filter rombel hanya untuk memilih daftar siswa yang dinilai.

Helper baru: `includes/wali_helpers.php` (akses wali, skala karakter, predikat ekskul, upsert untuk semua tabel Stage 7).

### Aturan akses Stage 7

| Role          | Catatan/Char/General Eval                              | Ekskul                           |
|---------------|---------------------------------------------------------|----------------------------------|
| Administrator | semua rombel · edit                                     | semua rombel · edit              |
| Admin         | semua rombel · edit                                     | semua rombel · edit              |
| Kepsek        | rombel jenjang-nya · **read-only**                      | rombel jenjang-nya · read-only   |
| Guru (wali)   | rombel di mana ia **wali kelas** · edit                 | rombel walinya · edit            |
| Guru biasa    | tidak ditampilkan                                       | tidak ditampilkan                |
| Parent        | —                                                       | —                                |

Periode terkunci (`pts_locked` / `pas_locked` di `semesters_state`) juga membuat halaman menjadi read-only sama seperti Stage 5/6.

### Tabel yang dipakai

Semua sudah tersedia di `sql/schema.sql` Stage 1 — **tidak ada migrasi baru** di Stage 7:
- `wali_notes (rombel_id, student_id, semester, period_kind, catatan)`
- `character_aspects` + `character_evaluations (... aspect_id, scale ENUM('NI','SI','WI','PR'), remark)`
- `general_evaluations (... narasi)`
- `extracurricular_grades (extracurricular_id, student_id, semester, academic_year_id, predikat, catatan)`


---

## Stage 8 (rilis ini) — Leger + Rapor PDF + Display Settings

Halaman baru:
- `public/leger.php` — Leger nilai per rombel (Pe/Ke/Si per mapel + rata-rata + rank kelas + rank paralel + rekap absensi). Tombol **Print / Save PDF** menggunakan dialog cetak browser (A4, sidebar/topbar otomatis disembunyikan via `@media print`).
- `public/rapor.php` — Rapor printable per siswa, layout dinamis dari `report_templates.layout_json`. Section: identitas → karakter → akademik (grouped per kategori mapel + skala KKM) → ekskul → kehadiran → catatan wali → narasi umum → 4 slot TTD. Banner status "Published / Belum Published" mengikuti `final_grades.status`.
- `public/admin/report_templates.php` — Display Settings per jenjang (SD/SMP/SMA): upload header & footer image, kelola 4 slot TTD (wali / kepsek / direktur / parent), reorder section rapor (panah ↑/↓).

Helper baru: `includes/report_helpers.php` (leger matrix, ranking kelas/paralel, attendance summary, KKM scale, template + signatures CRUD, `save_image_upload`).

CSS: tambahan blok `@media print` + class `.rapor-page`, `.leger-page`, `.t-print`, `.sig-grid` di `assets/css/design-system.css`.

Folder upload baru:
- `public/uploads/reports/` — header/footer image
- `public/uploads/signatures/` — TTD per slot per jenjang

### Catatan PDF

Stage ini sengaja **tanpa vendor** (dompdf/mPDF) — gunakan dialog **Print → Save as PDF** browser (Chrome/Edge/Safari/Firefox semuanya mendukung). Ini menjaga ZIP kecil & deployment XAMPP sederhana. Bila ingin server-side PDF, drop `dompdf` ke `/vendor/` dan render `rapor.php` via `Dompdf::loadHtml(ob_get_clean())`.

### Aturan akses Stage 8

| Role          | Leger                             | Rapor                                 | Display Settings  |
|---------------|-----------------------------------|---------------------------------------|-------------------|
| Administrator | semua rombel                      | semua siswa                           | ✅                |
| Admin         | semua rombel                      | semua siswa                           | ❌                |
| Kepsek        | rombel jenjang-nya (read-only)    | siswa jenjang-nya (read-only)         | ❌                |
| Guru          | rombel yang ia akses              | rombel yang ia akses                  | ❌                |
| Parent        | —                                 | (Stage 9, gating `published`)         | —                 |


---

## Stage 9 (rilis ini) — Parent Portal (mobile-first) + Publish Gating

Portal mobile-first untuk orang tua. Login via NISN + password (default = ddmmyyyy tanggal lahir; user wajib ganti pada login pertama). Semua data difilter ketat ke **anak yang ter-bind ke akun** — tidak ada parameter `student_id` yang menerima input user.

### Halaman baru

| URL                              | Fungsi                                                                                       |
|----------------------------------|----------------------------------------------------------------------------------------------|
| `public/parent/home.php`         | Dashboard: identitas, status rapor (matrix Ganjil/Genap × PTS/PAS), rekap absensi semester aktif, preview 5 mapel teratas (jika published), kartu link cepat. |
| `public/parent/grades.php`       | Daftar nilai per kategori mapel (Pe/Ke/Si + KKM predikat + catatan guru). **Hanya tampil bila `final_grades.status='published'`** untuk periode yang dipilih. |
| `public/parent/rapor.php`        | Rapor lengkap (reuse renderer Stage 8) dengan tab pemilih periode + tombol Print/Save PDF. Period yang belum published berlabel 🔒 dan tidak bisa dibuka. |
| `public/parent/attendance.php`   | Rekap kehadiran semester + persentase hadir + log harian 60-baris terakhir.                  |
| `public/parent/notes.php`        | Catatan Wali Kelas + narasi umum + penilaian karakter (gated).                               |
| `public/parent/profile.php`      | Profil singkat + ganti password. Force-change-pw banner jika `must_change_pw=1`.             |

### Komponen UI baru

`assets/css/design-system.css` blok **Stage 9**:
- `.parent-shell`, `.parent-topbar` (gradient blue header)
- `.p-card`, `.p-stat-grid`/`.p-stat`, `.p-list`, `.p-grade-row`, `.p-link-card`
- `.p-period-tabs` (tab pemilih semester+period dengan state `is-active` & `is-locked`)
- `.p-bottom-nav` — tab bar mobile sticky (Beranda · Nilai · Rapor · Hadir · Profil), otomatis sembunyi di ≥720px & saat print
- `.pill ok|no|warn`, `.p-locked-banner`, `.p-published-banner`

### Helper

`includes/parent_helpers.php`:
- `parent_student($p)` — full row (auto-logout jika siswa ter-soft-delete)
- `parent_rombel_for_year($studentId, $yearId)` — resolve rombel
- `parent_publish_matrix($studentId, $yearId)` — `[sem][period] => bool`
- `parent_published_grades(...)` — query final_grades dengan `status='published'`
- `parent_grades_overall_avg(rows)` — mean (Pe+Ke)/2
- `parent_attendance_summary/log` — recap & daily log
- `parent_wali_note(...)` — gated wali note
- `parent_change_password($id, $newPw)` — validasi (≥8, huruf+angka), clear flag, audit log
- `parent_set_period(sem, period)` — switch period saja (tahun ajaran terkunci ke active)

### Publish gating — sumber kebenaran

Rapor / nilai / catatan untuk satu periode tampil ke ortu **jika dan hanya jika** ada minimal 1 baris `final_grades` dengan `status='published'` untuk (rombel, student, semester, period_kind). Logikanya dipusatkan di `rapor_is_published()` dari Stage 8 — kepsek tinggal mengubah status jadi `published` di `final_grades_review.php` agar otomatis muncul di portal ortu.

### Audit

Setiap akses ortu di-log via `audit()` dengan kunci:
`parent_login`, `parent_view_home`, `parent_view_rapor`, `parent_view_grades`, `parent_view_attendance`, `parent_view_notes`, `parent_change_pw`.

### Flow login pertama kali

1. Admin men-seed `parents_auth` (default `password = tanggal_lahir ddmmyyyy`, `must_change_pw=1`).
2. Ortu login → `_layout.php` mendeteksi flag → redirect paksa ke `profile.php?force=1`.
3. Setelah ganti password (≥8 char, huruf+angka), flag dibersihkan dan ortu diarahkan ke beranda.

### Roadmap berikut (Stage 10)

- Audit Log viewer untuk Administrator (filter by user/role/action/date range).
- Recent Activity widget di Dashboard.
- Notification panel untuk Kepsek (rapor menunggu verifikasi).

---

## Stage 10 (rilis ini) — Audit Log Viewer + Recent Activity + Kepsek Notifications

### Halaman & endpoint baru

| URL                                | Akses          | Fungsi                                                                                  |
|------------------------------------|----------------|-----------------------------------------------------------------------------------------|
| `public/admin/audit_log.php`       | Administrator  | Viewer audit dengan filter (action, pengguna, free-text target, date range) + pagination |
| `public/api/audit_export.php`      | Administrator  | Streaming CSV export (UTF-8 BOM untuk Excel) — menghormati filter aktif                  |
| `public/dashboard.php` (upgrade)   | Semua staf     | Widget per-role: counter, pending review (kepsek), today-by-action (administrator), aktivitas terbaru |

### Helper

`includes/audit_helpers.php`:
- `audit_query($filters, $page, $per)` — paginated query (action, user_id, q, date_from, date_to)
- `audit_export_csv($filters)` — streaming CSV
- `audit_distinct_actions()`, `audit_distinct_users()` — opsi dropdown filter
- `audit_today_by_action($limit)` — breakdown hari ini untuk dashboard administrator
- `notif_pending_review_count($jenjang)` — jumlah `final_grades.status='submitted'` (kepsek difilter ke jenjang-nya)
- `notif_pending_review_list($jenjang, $limit)` — agregat per (rombel, subject, sem, period) + count siswa + last_at
- `dashboard_counters_for($user)` — counter set per-role

### Topbar bell (Kepsek / Admin / Administrator)

- Loncen dengan badge merah jumlah pending. Klik membuka dropdown 8 entri terakhir.
- Setiap baris men-deep-link ke `final_grades_review.php?rombel_id=…&subject_id=…`.
- Kepsek difilter ke jenjang sendiri; Admin/Administrator melihat semua.
- JS toggle (close on outside-click + `Escape`) di `assets/js/app.js`.

### Audit log — coverage

Semua tindakan write-path di Stage 1–9 sudah memanggil `audit()`. Stage 10 menambah satu aksi baru:
- `audit_export` — dicatat tiap kali admin men-download CSV.

### Sidebar

Item baru di group **Lainnya** (Administrator only): **Audit Log**.

### Dashboard widget map

| Role          | Counter cards                                              | Widget tambahan                                  |
|---------------|------------------------------------------------------------|--------------------------------------------------|
| Administrator | siswa, guru, rombel, mapel (global)                        | Today-by-action chips → deep-link ke audit log   |
| Admin         | sama seperti Administrator                                 | —                                                |
| Kepsek        | siswa & rombel jenjang-nya, pending review, published      | Tabel "Rapor Menunggu Verifikasi" + link ke review |
| Guru          | rombel saya, total siswa, total mapel, total rombel        | Aktivitas saya terbaru (filtered ke user_id)     |

### Selesai — roadmap inti

Dengan Stage 10 selesai, semua item dari spec awal sudah terpenuhi:

1. ✅ Auth + roles
2. ✅ Master data
3. ✅ Scope switcher + KKM
4. ✅ Rombel + Guru Pengampu + Subjek Penilaian
5. ✅ Absensi
6. ✅ Penilaian harian SKP
7. ✅ Nilai Akhir + Verifikasi + Lock/Unlock
8. ✅ Catatan Wali + Character Evaluation
9. ✅ Leger + Rapor PDF + Display Settings
10. ✅ Parent portal mobile-first + publish gating
11. ✅ Audit log + recent activity dashboard

---

Catatan: mapel pilihan otomatis ditandai sebagai kategori **Skill** di UI —
admin tidak perlu memilih kategori saat membuat mapel pilihan.
