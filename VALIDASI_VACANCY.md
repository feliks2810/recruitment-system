# ✅ VALIDASI VACANCY: Import Gagal jika Vacancy Tidak Ada

> **Status:** ✅ Implemented & Tested  
> **Date:** 24 December 2025

---

## 📌 RINGKASAN FITUR

Fitur validasi baru memastikan bahwa **jika Anda menyertakan kolom `vacancy` di file import, vacancy HARUS ada di database**. Jika vacancy tidak ditemukan, maka:

- ❌ Import untuk row tersebut **GAGAL**
- 📝 Error message yang **jelas** di log
- ⏭️ Continue ke row berikutnya (tidak berhenti semua)

---

## 🔄 ALUR LOGIKA VALIDASI

```
JIKA vacancy disediakan di file:
  │
  ├─ Cari vacancy di database
  │
  ├─ DITEMUKAN? 
  │  ├─ YES → Import berhasil ✅
  │  │        Ambil department dari vacancy
  │  │
  │  └─ NO → Import GAGAL ❌
  │          Row di-skip (tidak import)
  │          Error message di log
  │
  └─ Continue ke row berikutnya
```

---

## 📊 CONTOH BEHAVIOR

### Scenario 1: Valid Vacancy ✅

**Input File:**
```
applicant_id | nama       | vacancy
001          | John Doe   | IT Officer  (ada di database)
```

**Output:**
```
✅ BERHASIL
   - Candidate imported
   - Department = HCGAESRIT (dari vacancy IT Officer)
```

---

### Scenario 2: Invalid Vacancy ❌

**Input File:**
```
applicant_id | nama       | vacancy
002          | Jane Smith | INVALID_VACANCY_TIDAK_ADA
```

**Output:**
```
❌ GAGAL
   - Row di-skip (tidak import)
   - Error message di log:
     "Vacancy 'INVALID_VACANCY_TIDAK_ADA' tidak ditemukan di database"
   - Applicant Jane Smith tidak ter-import
```

---

### Scenario 3: Invalid Vacancy + Fallback Department ❌

**Input File:**
```
applicant_id | nama    | vacancy              | department
003          | Bob W.  | INVALID_VACANCY      | IT Support
```

**Output:**
```
❌ GAGAL (walaupun ada fallback department)
   - Row di-skip (tidak import)
   - Department fallback DIABAIKAN
   - Error: Vacancy tidak valid = tidak import sama sekali
```

**Alasan:**
- Jika Anda menyediakan vacancy, sistem expect itu valid
- Fallback tidak digunakan untuk invalid vacancy
- Ini untuk mencegah kesalahan/typo di file

---

### Scenario 4: Vacancy Kosong + Department Fallback ✅

**Input File:**
```
applicant_id | nama  | vacancy | department
004          | Alice | [kosong]| IT Support
```

**Output:**
```
✅ BERHASIL
   - Candidate imported
   - Department = IT Support (fallback)
   - Tidak ada error (vacancy kosong = OK)
```

---

## 🎯 KAPAN IMPORT GAGAL?

Import akan gagal (row di-skip) jika:

1. ❌ **Vacancy disediakan TETAPI tidak ada di database**
   ```
   vacation = "INVALID_NAME"
   Result: SKIP + ERROR LOG
   ```

2. ✅ **Vacancy kosong** - OK, tidak gagal
   ```
   vacancy = "" atau [empty]
   Result: Gunakan fallback department
   ```

3. ✅ **Vacancy ada di database** - OK, tidak gagal
   ```
   vacancy = "IT Officer" (ada)
   Result: Import berhasil
   ```

---

## 📝 ERROR MESSAGE

Saat import gagal, message yang terlihat di log:

```json
{
  "error": "CandidatesImport: Vacancy not found in database - IMPORT FAILED",
  "row": 3,
  "applicant_id": "INVALID-002",
  "nama": "Invalid Vacancy Test",
  "vacancy_name_provided": "INVALID_VACANCY_TIDAK_ADA_DI_DB",
  "error_detail": "Vacancy \"INVALID_VACANCY_TIDAK_ADA_DI_DB\" tidak ditemukan di database. Silakan cek nama vacancy."
}
```

**Yang tertera di log:**
- Row number dalam file
- Applicant ID
- Nama kandidat  
- Vacancy yang disediakan
- Error message yang jelas

---

## 🔍 CARA TROUBLESHOOT

Jika vacancy import gagal:

### Step 1: Check Error Message
```
Buka: storage/logs/laravel.log
Cari: "Vacancy not found in database"
```

### Step 2: Verify Vacancy Name
```
Lihat: 'vacancy_name_provided' di error message
Bandingkan dengan daftar vacancy di database
```

### Step 3: Correct the Vacancy Name
- Pastikan nama **exact match** dengan database
- Cek **CASE** (uppercase vs lowercase)
- Cek **SPASI** (extra spaces?)
- Cek **TYPO**

### Step 4: Re-import
```
Update file dengan vacancy name yang benar
Upload dan import ulang
```

---

## 📋 CHECKLISTS SEBELUM IMPORT

```
Jika pakai vacancy:
  [ ] Vacancy name exact match dengan database (case-sensitive)
  [ ] Tidak ada spasi ekstra di awal/akhir
  [ ] Tidak ada typo
  
Jika tidak punya vacancy valid:
  [ ] Kosongkan kolom vacancy
  [ ] Isi kolom department sebagai fallback
  [ ] Atau jangan sertakan keduanya (optional)
```

---

## 🧪 TESTING RESULTS

Semua test case untuk validasi ini **PASSED**:

```
✅ TEST 1: Valid Vacancy
   Status: BERHASIL import dengan department dari vacancy

✅ TEST 2: Invalid Vacancy
   Status: GAGAL import, row di-skip, error logged

✅ TEST 3: Invalid Vacancy + Fallback Department
   Status: GAGAL import (fallback diabaikan)

✅ TEST 4: Kosong Vacancy + Department Fallback
   Status: BERHASIL import, gunakan fallback department
```

Run test: `php test_vacancy_validation.php`

---

## 💡 BEST PRACTICES

1. **Validate Terlebih Dahulu**
   - Sebelum import, pastikan vacancy valid
   - Download daftar vacancy dari database
   - Cross-check dengan file import

2. **Gunakan Template**
   - Download template dari halaman Import
   - Template punya struktur yang benar
   - Tinggal isi data saja

3. **Clear Vs Valid**
   - Jika tidak yakin vacancy ada: **kosongkan**
   - Gunakan department fallback saja
   - Lebih aman daripada typo vacancy name

4. **Monitor Logs**
   - Check `storage/logs/laravel.log` setelah import
   - Lihat berapa row yang berhasil vs skip
   - Lihat error message untuk row yang skip

---

## ⚠️ PENTING DIINGAT

### Jika Vacancy Disediakan = MUST EXIST

```
❌ SALAH:
   Kasih kolom vacancy dengan nama yang tidak ada
   → Import gagal untuk row itu

✅ BENAR:
   - Pastikan vacancy ada di database
   - ATAU kosongkan kolom vacancy
   - Gunakan fallback department saja
```

### Priority Logic

```
1. Jika vacancy disediakan:
   - HARUS valid (ada di database)
   - Jika tidak → GAGAL
   
2. Jika vacancy kosong:
   - Gunakan department (fallback)
   - OK jika keduanya kosong
```

---

## 🔗 RELASI DENGAN FITUR LAIN

### Backward Compatibility

✅ **File lama tetap berfungsi**
- File yang hanya punya department: TETAP BEKERJA
- File yang kosong keduanya: TETAP BEKERJA
- File dengan valid vacancy: BEKERJA LEBIH BAIK

### Dengan Fallback Department

```
Vacancy disediakan → gunakan vacancy (strict validation)
Vacancy kosong → fallback ke department (lenient)
Keduanya kosong → OK, candidate tanpa dept
```

---

## 📞 SUPPORT

Jika ada pertanyaan:

1. **Vacancy mana yang valid?**
   - Lihat halaman Vacancies di aplikasi
   - Download template import (ada daftar)

2. **Kenapa import gagal?**
   - Check error message di log
   - Verify vacancy name (case-sensitive)

3. **Bagaimana kalau tidak punya vacancy?**
   - Kosongkan kolom vacancy
   - Isi kolom department saja
   - Atau buat vacancy terlebih dahulu

---

## 📚 DOKUMENTASI TERKAIT

- [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - Quick start
- [CONTOH_FILE_IMPORT.md](CONTOH_FILE_IMPORT.md) - Format file
- [FITUR_VACANCY_DEPARTMENT_IMPORT.md](FITUR_VACANCY_DEPARTMENT_IMPORT.md) - Complete guide
- [test_vacancy_validation.php](test_vacancy_validation.php) - Test script

---

**Status:** ✅ Production Ready  
**Last Updated:** 24 December 2025  
**Test Coverage:** 4 test cases (all passed)
