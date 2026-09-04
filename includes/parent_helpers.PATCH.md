# Patch untuk includes/parent_helpers.php

File aslinya (325 baris) belum pernah saya terima utuh — hanya potongan
`parent_published_grades()` yang sempat Anda tempel di chat. Supaya tidak
menebak-nebak isi function lain di file itu, ini instruksi MINIMAL dan
TARGETED, bukan file pengganti utuh. Terapkan dua perubahan berikut ke
file asli Anda.

---

## 1. `parent_publish_matrix()` — ganti SELURUH isi function body

Cari function ini (nama harus persis sama, karena dipanggil `rapor.php`
sebagai `$publishMatrix[$s][$k]`):

```php
function parent_publish_matrix(int $studentId, int $yearId): array
{
    // ... isi lama, apapun itu, berbasis final_grades.status='published' ...
}
```

Ganti isi function-nya (signature TETAP SAMA, jangan diubah) menjadi:

```php
function parent_publish_matrix(int $studentId, int $yearId): array
{
    // MIGRASI 2026-09: delegasi ke report_helpers.php — sumber kebenaran
    // publish sekarang rapor_publications, bukan lagi final_grades.status.
    return rapor_publish_matrix_for_student($studentId, $yearId);
}
```

Pastikan `includes/report_helpers.php` sudah di-require sebelum function
ini dipanggil (biasanya sudah otomatis, karena
`parent_helpers.php` -> `report_helpers.php` -> ... , tapi cek baris
`require_once` di bagian atas `parent_helpers.php` untuk pastikan).

Return shape TIDAK berubah — `rapor.php` tidak perlu disentuh untuk
bagian ini.

---

## 2. `parent_published_grades()` — ganti SATU klausa WHERE saja

Di dalam function ini, cari baris persis:

```php
AND fg.status='published'
```

(atau variasi spasi/alias serupa — intinya baris yang membandingkan
`status` dengan string `'published'` di dalam query `parent_published_grades()`).

Ganti jadi:

```php
AND fg.status IN ('approved','published')
```

**Kenapa tetap menyertakan `'published'` di sini padahal kolom itu sudah
dihentikan penggunaannya?** Supaya query ini tetap benar untuk baris lama
yang migrasinya belum sempat dijalankan, dan tidak rusak kalau ternyata
ada proses lain di luar kode yang saya lihat yang masih menulis nilai itu.
Setelah `migrations/2026_09_rapor_level_publish.sql` dijalankan (yang
mengonversi semua `published` lama jadi `approved`), klausa `IN (...)` ini
efektifnya sama saja dengan `= 'approved'` — aman untuk disederhanakan
lagi nanti kalau Anda mau.

**Efeknya:** begitu `rapor_is_published()` (dipanggil dari `rapor.php`,
biasanya SEBELUM memanggil `parent_published_grades()`) bilang rapor
siswa ini published, maka SEMUA mapelnya yang sudah disetujui kepsek ikut
tampil isi nilainya — bukan cuma yang kebetulan masih berstatus
`published` di baris lama. Mapel yang belum disetujui (`draft`/
`submitted`/`revised`) tetap tidak muncul, sesuai keputusan Anda (rapor
boleh terbit partial).

---

## Yang WAJIB Anda verifikasi sendiri (saya tidak bisa cek ini)

1. **Apakah ada function lain di `parent_helpers.php`** yang juga membaca
   `final_grades.status='published'` atau memanggil `rapor_is_published()`
   dengan asumsi lama? Grep dulu:
   ```
   grep -n "published" includes/parent_helpers.php
   ```
2. **`public/rapor.php`** (Parent Portal) — saya belum pernah menerima file
   ini. Asumsi saya: dia memanggil `rapor_is_published()` untuk gating
   tampil/tidak, lalu `parent_publish_matrix()` untuk badge per semester,
   lalu `parent_published_grades()` untuk isi nilai. Selama urutan dan
   signature itu yang dipakai, `rapor.php` seharusnya tidak perlu diubah.
   **Tolong konfirmasi ini dengan membuka file aslinya**, terutama kalau
   ada logika tambahan yang langsung query `final_grades.status='published'`
   di luar ketiga function di atas.
3. **`includes/admin_helpers.php`** — `dashboard.php` menampilkan
   `$counts['published']` dari `dashboard_counters_for()`, yang
   didefinisikan di file ini (belum pernah saya terima). Kemungkinan besar
   ini juga menghitung `COUNT(...) WHERE final_grades.status='published'`
   dan sekarang akan selalu bernilai 0 pasca-migrasi. Ganti sumbernya ke:
   ```php
   SELECT COUNT(DISTINCT rp.student_id) FROM rapor_publications rp
   JOIN rombel r ON r.id = rp.rombel_id
   WHERE rp.status='published' AND r.academic_year_id = :y
     AND rp.semester = :sem AND rp.period_kind = :p
     [AND r.jenjang = :j untuk kepsek]
   ```
   (gunakan `$sc['semester']`/`$sc['period']` scope aktif, sama seperti
   pola di `publish_summary_counts()` pada `final_grades_helpers.php` yang
   sudah direvisi).
