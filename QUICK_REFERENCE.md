# ⚡ QUICK REFERENCE: Vacancy-Based Department Import

## 🎯 TL;DR

**Fitur Baru:** Import candidates langsung dengan vacancy, department otomatis ter-resolve dari vacancy!

**Cara Pakai:**
1. Download template: `{{ route('import.template', 'candidates') }}`
2. Isi 3 kolom minimal: `applicant_id`, `nama`, `vacancy`
3. Upload file
4. Done! Department otomatis dari vacancy ✅

---

## 📋 Template Minimal

```
applicant_id | nama       | email            | vacancy
001          | John Doe   | john@example.com | IT Officer
002          | Jane Smith | jane@example.com | Finance & Administration Officer
```

Result: Department otomatis = HCGAESRIT (John), Finance & Accounting (Jane)

---

## 🚀 Quick Steps

```
1. DOWNLOAD
   Click: "Template Import" → Save file

2. FILL
   - applicant_id: (required) ID unik
   - nama: (required) Nama lengkap
   - vacancy: (optional) Nama vacancy
   - Kolom lain: semua optional

3. UPLOAD
   Go to: Import page → Upload file

4. PREVIEW
   Review data yang akan diimport

5. CONFIRM
   Click: "Confirm" → Import starts

6. DONE!
   Check Candidates page untuk hasil
```

---

## 🔍 Daftar Vacancy (Common)

### HCGAESRIT
- Section Head
- IT Officer
- GA Personnel
- EHS Officer
- Safety Officer
- (dan lainnya)

### Finance & Accounting
- Finance & Administration Officer
- Accounting Staff
- Staff
- (dan lainnya)

### Warehouse & Inventory
- Warehouse & Inventory Management Staff
- Procurement Officer
- Receiving Personnel
- (dan lainnya)

👉 Full list: Check database atau dokumentasi

---

## ✅ CHECKLIST

Sebelum import:
- [ ] `applicant_id` tidak kosong
- [ ] `nama` tidak kosong
- [ ] Tidak ada duplikat `applicant_id`
- [ ] Nama vacancy sesuai database (exact match)
- [ ] Format kolom benar (Excel format)

---

## ⚠️ Common Mistakes

| ❌ Salah | ✅ Benar | Issue |
|---------|---------|-------|
| IT officer | IT Officer | Case-sensitive |
| IT  Officer | IT Officer | Extra space |
| Finance | Finance & Administration Officer | Incomplete name |
| john doe | John Doe | Email lowercase OK, nama case OK |

---

## 🎓 Contoh Real

### CASE 1: Vacancy Only (Recommended)
```
applicant_id | nama    | email          | vacancy        | department
001          | John    | j@email.com    | IT Officer     | [kosong]
```
→ Dept = HCGAESRIT (dari vacancy)

### CASE 2: Department Only (Fallback)
```
applicant_id | nama    | email          | vacancy | department
002          | Jane    | j@email.com    | [kosong]| IT Department
```
→ Dept = IT Department (dari fallback)

### CASE 3: Priority Test
```
applicant_id | nama    | email          | vacancy        | department
003          | Bob     | b@email.com    | IT Officer     | Finance
```
→ Dept = HCGAESRIT (vacancy wins, Finance ignored)

---

## 🔧 Troubleshooting

**Q: Vacancy tidak ditemukan?**
A: Check nama di database. Harus exact match!

**Q: Department tetap kosong?**
A: Pastikan ada di kolom `vacancy` atau `department`

**Q: Data tidak terupdate?**
A: applicant_id sudah ada? Akan di-UPDATE, bukan create baru

**Q: Mau lihat hasil?**
A: Run: `php verify_import.php`

---

## 📚 Full Docs

Untuk detail lengkap:
- `FITUR_VACANCY_DEPARTMENT_IMPORT.md` - Complete guide
- `CONTOH_FILE_IMPORT.md` - Field explanations
- `USAGE_GUIDE.txt` - Full documentation

---

## 💡 Pro Tips

✨ **Tip 1:** Pakai vacancy jika mungkin = lebih konsisten  
✨ **Tip 2:** Export existing candidates → modify → re-import (duplicate detection)  
✨ **Tip 3:** Check logs di `storage/logs/laravel.log` jika ada issue  
✨ **Tip 4:** Gunakan applicant_id yang meaningful (e.g., APP-2025-001)  

---

**Last Updated:** 24 Dec 2025  
**Status:** ✅ Ready to Use
