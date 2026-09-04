# Ringkasan: Publish Rapor per-Siswa (bukan per-Mapel)

## Yang berubah secara konsep

- **Sebelum:** visibilitas ke ortu ditentukan oleh `final_grades.status='published'`
  per baris (mapel, siswa). Rapor bisa "setengah terbit".
- **Sesudah:** visibilitas ke ortu ditentukan oleh tabel baru
  `rapor_publications`, satu baris per (siswa, rombel, semester, periode,
  tahun ajaran). Kepsek publish/batal-publish **per siswa** (atau bulk),
  bukan lagi per mapel.
- `final_grades.status` sekarang hanya `draft`/`submitted`/`revised`/
  `approved` — `approved` adalah status akhir yang wajar untuk sebuah
  nilai. Nilai `published` di kolom ini **dihentikan total** (bukan cuma
  berhenti dipakai kode baru — data lama juga dikonversi, lihat migrasi).
- **Partial publish diizinkan secara sadar**: kepsek boleh publish rapor
  siswa walau belum semua mapelnya disetujui. Mapel yang belum disetujui
  akan tampil kosong di rapor ortu, sisanya tampil normal. Sistem tidak
  memblokir ini.

## File yang saya kerjakan (isi folder ini)

| File | Status |
|---|---|
| `migrations/2026_09_rapor_level_publish.sql` | **Baru** — jalankan lebih dulu, sebelum deploy kode |
| `includes/report_helpers.php` | **Diubah** — `rapor_is_published()` ditulis ulang + 6 fungsi baru untuk publish/unpublish level-rapor |
| `includes/final_grades_helpers.php` | **Diubah** — semua jejak `'published'` sebagai status mapel dihapus dari logic |
| `public/publish_rapor.php` | **Ditulis ulang total** — publish/unpublish sekarang per siswa, kelengkapan mapel jadi info saja |
| `public/final_grades_review.php` | **Diubah** — hapus `'published'` dari opsi "minta revisi" + teks UI |
| `includes/parent_helpers.PATCH.md` | **Instruksi patch** (bukan file utuh — lihat alasan di bawah) |

## ⚠️ WAJIB dicek manual sebelum deploy — saya tidak punya akses ke file-file ini

1. **`includes/parent_helpers.php`** (325 baris, hanya potongan yang saya
   terima) — terapkan 2 perubahan targeted di `parent_helpers.PATCH.md`.
2. **`public/rapor.php`** (Parent Portal) — tidak pernah saya terima sama
   sekali. Desain saya **mengasumsikan** dia memanggil `rapor_is_published()`
   → `parent_publish_matrix()` → `parent_published_grades()` tanpa logic
   tambahan yang langsung menyentuh `final_grades.status='published'`.
   Kalau asumsi ini salah, bagian itu bisa lolos tanpa efek atau malah
   error. **Buka file ini dan grep `published` di dalamnya sebelum deploy.**
3. **`includes/admin_helpers.php`** (tidak pernah saya terima) — fungsi
   `dashboard_counters_for()` di sini kemungkinan besar menghitung
   `$counts['published']` dari `final_grades.status='published'`, yang
   sekarang akan selalu 0. Lihat query pengganti di `parent_helpers.PATCH.md`
   bagian 3.

## ⚠️ Perubahan perilaku yang perlu Anda sadari (bukan bug, tapi konsekuensi desain)

**Sebelumnya**, kalau kepsek klik "Minta Revisi" pada mapel yang statusnya
`published`, sistem otomatis menurunkan status jadi `revised` — yang
secara tidak langsung "mencabut" publikasi baris itu (karena
`rapor_is_published()` lama membaca status per-baris).

**Sekarang**, karena publish sudah lepas total dari `final_grades.status`,
meminta revisi pada mapel yang approved **tidak lagi otomatis membatalkan
publikasi rapor** siswa itu. Kalau kepsek sudah publish rapor siswa lalu
belakangan meminta revisi salah satu mapelnya, rapor tetap **terbit** ke
ortu dengan nilai lama sampai kepsek **manual** buka Publish Rapor dan
klik Batal Publish (kalau memang itu yang diinginkan) — atau publish ulang
setelah revisi selesai (nilai baru otomatis ikut tampil begitu disetujui
lagi, karena `parent_published_grades()` membaca `status IN
('approved','published')` secara real-time, bukan snapshot).

Ini konsekuensi wajar dari memisahkan dua konsep (verifikasi vs
visibilitas) seperti yang Anda minta — saya angkat di sini supaya bukan
kejutan pas production, bukan untuk didiskusikan ulang kecuali Anda mau
perilaku otomatis itu dipertahankan (bisa ditambahkan sebagai hook
opsional di `final_grade_set_status()` kalau Anda mau).

## Urutan deploy yang disarankan

1. Backup database.
2. Jalankan `migrations/2026_09_rapor_level_publish.sql`.
3. Deploy `includes/report_helpers.php`, `includes/final_grades_helpers.php`,
   `public/publish_rapor.php`, `public/final_grades_review.php`.
4. Terapkan patch manual ke `includes/parent_helpers.php` (lihat PATCH.md).
5. Cek & sesuaikan `public/rapor.php` dan `includes/admin_helpers.php`
   sesuai poin di atas.
6. Smoke test: publish 1 siswa (partial, sebagian mapel belum approved) →
   pastikan rapor ortu tampil dengan mapel approved terisi dan mapel
   belum-approved kosong, bukan seluruh halaman blank atau error.
