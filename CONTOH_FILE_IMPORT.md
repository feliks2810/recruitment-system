# Contoh Format File Import untuk Fitur Vacancy-Based Department

## 📌 REKOMENDASI: Gunakan Vacancy (Cara Paling Mudah)

Jika Anda memiliki daftar vacancy yang sudah lengkap dengan departemen, gunakan format ini:

```
applicant_id | nama              | email               | phone         | jk       | tanggal_lahir | jenjang_pendidikan | perguruan_tinggi | jurusan        | ipk  | source    | vacancy                       | department | psikotest_result | psikotest_date | cv            | flk
001          | John Doe          | john.doe@email.com  | 0812-1234567  | Laki-laki| 1990-05-15    | S1                 | UI               | Informatika    | 3.50 | Walk-in   | IT Officer                    |            | LULUS            | 2025-12-20     | cv_john.pdf   | flk_john.pdf
002          | Jane Smith        | jane.smith@email.com| 0812-2345678  | Perempuan| 1992-08-22    | S1                 | ITB              | Teknik         | 3.75 | Online    | Finance & Administration Officer|            | GAGAL            | 2025-12-20     | cv_jane.pdf   | flk_jane.pdf
003          | Bob Wilson        | bob.wilson@email.com| 0812-3456789  | Laki-laki| 1988-03-10    | S2                 | UGM              | Manajemen      | 3.40 | Referral  | Warehouse & Inventory Management Staff |  | RETEST           | 2025-12-21     | cv_bob.pdf    | flk_bob.pdf
```

**Keuntungan:**
✅ Departemen otomatis ter-resolve dari vacancy  
✅ Lebih cepat dan konsisten  
✅ Tidak perlu khawatir typo nama departemen  

**Daftar Vacancy yang Tersedia:**

### HCGAESRIT Department
- Section Head
- HCGAESR Staff
- HCGA Administrator
- IT Officer
- GA Personnel
- GA Building Maintainer
- Cleaner
- EHS Officer
- Safety Officer
- Safety Inspector
- Sustainability Staff
- Security

### Finance & Accounting Department
- Staff
- Finance & Administration Officer
- Finance Administrator
- Accounting Staff

### Warehouse & Inventory Department
- Warehouse & Inventory Management Staff
- SCM Staff
- Receiving Personnel
- Binning Personnel
- Stock Taking Personnel
- Issuing & Supply Personnel

### Procurement & Subcontractor Department
- Procurement Officer
- Procurement Administrator

---

## ⚠️ ALTERNATIF: Gunakan Department (Fallback)

Jika tidak memiliki vacancy, gunakan kolom department:

```
applicant_id | nama              | email               | jk       | jenjang_pendidikan | vacancy | department          | psikotest_result
004          | Alice Brown       | alice@email.com     | Perempuan| S1                 |         | IT Department       | LULUS
005          | Charlie Davis     | charlie@email.com   | Laki-laki| S1                 |         | HR Department       | GAGAL
006          | Diana Evans       | diana@email.com     | Perempuan| S2                 |         | Finance Department  | LULUS
```

**Catatan:**
⚠️ Department akan dibuat baru jika belum ada di database  
⚠️ Lebih rentan typo nama departemen  
✅ Tetap berfungsi jika vacancy tidak tersedia  

---

## 📋 FIELD DETAIL & CONTOH

### applicant_id (WAJIB)
Identifier unik untuk setiap pelamar. Tidak boleh duplikat dalam satu file import.
```
Contoh: 001, APP-2025-001, JOHN-DOE-001
```

### nama (WAJIB)
Nama lengkap kandidat.
```
Contoh: John Doe, Jane Smith, Bob Wilson
```

### email (OPSIONAL)
Format email yang valid.
```
Contoh: john@example.com, jane@company.com
```

### phone (OPSIONAL)
Nomor telepon dalam berbagai format.
```
Contoh: 0812-1234567, +62-812-1234567, 082112345678
```

### jk (OPSIONAL)
Jenis Kelamin. Gunakan: "Laki-laki" atau "Perempuan"
```
Contoh: Laki-laki, Perempuan
```

### tanggal_lahir (OPSIONAL)
Tanggal lahir dalam format YYYY-MM-DD
```
Contoh: 1990-05-15, 1992-08-22
```

### jenjang_pendidikan (OPSIONAL)
Jenjang pendidikan terakhir.
```
Contoh: SMA, S1, S2, S3, D3
```

### perguruan_tinggi (OPSIONAL)
Nama universitas/perguruan tinggi.
```
Contoh: UI, ITB, UGM, UnPad
```

### jurusan (OPSIONAL)
Nama jurusan/program studi.
```
Contoh: Informatika, Teknik, Manajemen, Akuntansi
```

### ipk (OPSIONAL)
IPK dalam format desimal.
```
Contoh: 3.50, 3.75, 2.80
```

### source (OPSIONAL)
Sumber lamaran/recruitment.
```
Contoh: Walk-in, Online, Referral, Job Fair, LinkedIn
```

### vacancy (OPSIONAL tapi RECOMMENDED)
**NAMA VACANCY** persis seperti di database. Sistem akan otomatis ambil department-nya.
```
Contoh: IT Officer, Finance & Administration Officer
PENTING: Harus exact match dengan data di database!
```

### department (OPSIONAL - FALLBACK)
Nama departemen (hanya jika vacancy kosong). Dapat membuat departemen baru.
```
Contoh: IT Department, HR Department, Finance Department
```

### psikotest_result (OPSIONAL)
Status hasil psikotes.
```
Valid values: 
- LULUS (atau: lulus, pass, passed)
- GAGAL (atau: gagal, fail, failed)
- RETEST (atau: retest, ulang, retry)
Default: PROSES
```

### psikotest_date (OPSIONAL)
Tanggal psikotes dalam format YYYY-MM-DD.
```
Contoh: 2025-12-20, 2025-12-21
```

### cv, flk (OPSIONAL)
Nama file atau path CV dan FLK.
```
Contoh: cv_john.pdf, flk_jane.pdf, /path/to/file.pdf
```

---

## ✅ CHECKLIST SEBELUM IMPORT

Sebelum upload file, pastikan:

- [ ] Kolom `applicant_id` tidak ada yang kosong
- [ ] Kolom `nama` tidak ada yang kosong
- [ ] Tidak ada duplikat `applicant_id` dalam satu file
- [ ] Jika pakai `vacancy`, pastikan nama sesuai dengan database (exact match)
- [ ] Format tanggal menggunakan YYYY-MM-DD
- [ ] Format email valid (jika ada)
- [ ] Tidak ada spasi berlebihan di awal/akhir kolom
- [ ] File dalam format Excel (.xlsx atau .xls)

---

## 🔍 COMMON MISTAKES & SOLUTIONS

### ❌ Vacancy tidak ter-resolve
**Problem:** File punya vacancy tapi department tetap kosong  
**Penyebab:** Nama vacancy tidak match dengan database (typo, spasi, case)  
**Solusi:** Cek nama vacancy di halaman Vacancies, pastikan exact match  

### ❌ Department baru tidak sesuai
**Problem:** Department ter-import tapi nama salah  
**Penyebab:** Typo di kolom department  
**Solusi:** Periksa kembali file atau gunakan vacancy-based import  

### ❌ Psikotes status tidak ter-recognize
**Problem:** psikotest_result tidak ter-normalize  
**Penyebab:** Format tidak sesuai (capital case, spasi, dll)  
**Solusi:** Gunakan: LULUS, GAGAL, atau RETEST (case-sensitive)  

---

## 📞 SUPPORT

Jika ada pertanyaan atau masalah dengan format file:
1. Cek dokumentasi: `FITUR_VACANCY_DEPARTMENT_IMPORT.md`
2. Lihat test case: `test_vacancy_department.php`
3. Contact: Team HC/Admin

---

**Last Updated:** 24 December 2025
