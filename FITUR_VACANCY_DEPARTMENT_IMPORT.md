# 📋 Fitur: Auto-Resolve Department dari Vacancy

## Ringkasan
Sistem import kandidat sekarang mendukung penentuan departemen **otomatis** dari vacancy. Ini memungkinkan Anda untuk mengimport file kandidat tanpa harus menyertakan kolom departemen, karena sistem akan secara otomatis mencocokkan departemen berdasarkan vacancy yang dipilih.

## Alur Logika

```
┌─────────────────────────────────────────────────┐
│ Proses Resolusi Departemen saat Import         │
└─────────────────────────────────────────────────┘

1. Cari Vacancy berdasarkan nama (dari kolom 'vacancy')
   ├─ Ditemukan? ✓ Ambil department dari vacancy
   │  (Priority 1 - Tertinggi)
   │
   └─ Tidak ditemukan? ✗ Lanjut ke step 2

2. Gunakan nama departemen dari kolom 'department' 
   ├─ Ada nilai? ✓ Buat/gunakan department
   │  (Priority 2)
   │
   └─ Kosong? ✗ Lanjut ke step 3

3. Candidate dibuat tanpa department
   (Priority 3 - Jika keduanya kosong)
```

## Format File Import

### Kolom yang Digunakan

| Kolom | Tipe | Wajib | Keterangan |
|-------|------|-------|-----------|
| `applicant_id` | String | ✅ Ya | ID pelamar unik (primary key) |
| `nama` | String | ✅ Ya | Nama lengkap kandidat |
| `email` | String | ❌ Opsional | Email pelamar |
| `phone` | String | ❌ Opsional | Nomor telepon |
| `jk` | String | ❌ Opsional | Jenis kelamin (Laki-laki/Perempuan) |
| `tanggal_lahir` | Date | ❌ Opsional | Format: YYYY-MM-DD |
| `alamat` | String | ❌ Opsional | Alamat lengkap |
| `jenjang_pendidikan` | String | ❌ Opsional | S1, S2, SMA, dll |
| `perguruan_tinggi` | String | ❌ Opsional | Nama universitas |
| `jurusan` | String | ❌ Opsional | Nama jurusan |
| `ipk` | String | ❌ Opsional | IPK (format: 3.5) |
| `source` | String | ❌ Opsional | Sumber lamaran (Walk-in, Online, dll) |
| **`vacancy`** | String | ❌ Opsional | **Nama vacancy** (untuk auto-resolve dept) |
| `department` | String | ❌ Opsional | Nama departemen (fallback jika vacancy kosong) |
| `psikotest_result` | String | ❌ Opsional | LULUS / GAGAL / RETEST |
| `psikotest_date` | Date | ❌ Opsional | Tanggal psikotes |
| `cv` | String | ❌ Opsional | Path/nama file CV |
| `flk` | String | ❌ Opsional | Path/nama file FLK |

### Contoh 1: Dengan Vacancy (Recommended)
Jika Anda memiliki vacancy yang sudah terdaftar dengan departemen, gunakan format ini:

```
applicant_id | nama          | email             | vacancy      | department | psikotest_result
001          | John Doe      | john@email.com    | IT Officer   | [kosong]   | LULUS
002          | Jane Smith    | jane@email.com    | IT Officer   | [kosong]   | GAGAL
003          | Bob Wilson    | bob@email.com     | Finance Mgr  | [kosong]   | LULUS
```

**Hasil:**
- John & Jane → Department dari vacancy "IT Officer" = HCGAESRIT
- Bob → Department dari vacancy "Finance Mgr" = Finance & Accounting

### Contoh 2: Dengan Department Fallback
Jika tidak memiliki vacancy atau ingin specify departemen secara manual:

```
applicant_id | nama          | email             | vacancy      | department       | psikotest_result
004          | Alice Brown   | alice@email.com   | [kosong]     | IT Department    | LULUS
005          | Charlie Davis | charlie@email.com | [kosong]     | HR Department    | RETEST
```

**Hasil:**
- Alice → Department = "IT Department" (dibuat baru jika belum ada)
- Charlie → Department = "HR Department" (dibuat baru jika belum ada)

### Contoh 3: Keduanya Ada (Priority = Vacancy)
Ketika kedua kolom ada, **vacancy** memiliki prioritas lebih tinggi:

```
applicant_id | nama          | email             | vacancy      | department  | psikotest_result
006          | Eve Foster    | eve@email.com     | IT Officer   | Finance     | GAGAL
```

**Hasil:**
- Eve → Department dari vacancy "IT Officer" = HCGAESRIT
- (Kolom "Finance" diabaikan)

## Cara Menggunakan

### 1. Download Template
- Klik tombol "Template Import" di halaman Import
- Template akan memiliki struktur kolom yang sesuai

### 2. Isi Data
- **Opsional A:** Jika ingin auto-resolve departemen, isi kolom `vacancy` saja
- **Opsional B:** Jika tidak punya vacancy, isi kolom `department` 
- Pastikan `applicant_id` dan `nama` tidak kosong

### 3. Upload File
- Pilih file Excel yang sudah disiapkan
- Sistem akan validasi data
- Klik "Confirm" untuk mulai import

### 4. Monitoring
- Lihat progress import di halaman Import
- Cek hasil di halaman Candidates setelah selesai

## Keuntungan Fitur Ini

✅ **Efisiensi:** Tidak perlu input departemen manual jika sudah ada vacancy  
✅ **Konsistensi:** Department otomatis sesuai dengan vacancy yang terdaftar  
✅ **Fleksibilitas:** Support both vacancy-based dan department-based import  
✅ **Fallback:** Tetap bisa import tanpa departemen jika diperlukan  

## Catatan Penting

⚠️ **Pencocokan Vacancy:**
- Sistem mencari vacancy berdasarkan **nama exact match**
- Pastikan nama vacancy di file sesuai dengan yang ada di database
- Contoh benar: "IT Officer" (sesuai di database)
- Contoh salah: "IT officer" atau "IT  Officer" (spasi/case berbeda)

⚠️ **Department Otomatis:**
- Jika vacancy tidak ditemukan, sistem akan fallback ke kolom `department`
- Jika kolom `department` juga kosong, candidate akan dibuat **tanpa department**

⚠️ **Update Data:**
- Jika `applicant_id` sudah ada, sistem akan **update** data (bukan create baru)
- Department akan diperbarui sesuai vacancy/departement terbaru

## Testing

Ada script test untuk verifikasi fitur:

```bash
php test_vacancy_department.php
```

Output akan menunjukkan:
- Daftar vacancy yang tersedia
- 3 test case berbeda (vacancy only, department only, both)
- Hasil import dan department yang diresolvse

## Developer Notes

### Location Files
- **Import Logic:** `app/Imports/CandidatesImport.php` (line 50-94)
- **Template Export:** `app/Exports/CandidateTemplateExport.php`
- **Test Script:** `test_vacancy_department.php`

### Key Changes
1. **Priority Logic:** Vacancy > Department > None
2. **Vacancy Lookup:** `Vacancy::where('name', $vacancyName)->first()`
3. **Application Department:** Set via `department_id` field
4. **Logging:** Semua proses di-log untuk debugging

---

**Last Updated:** 24 December 2025  
**Feature Status:** ✅ Active & Tested
