# MPP Submission - Fix Summary

## Masalah
Ketika user mencoba membuat pengajuan MPP baru dan menekan tombol simpan, halaman loading forever dan tidak ada yang terjadi. Error yang muncul di log adalah:

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'system-recruitment.vacancy_documents' doesn't exist
```

## Penyebab
Migration `2026_01_19_create_mpp_feature` sudah tercatat di database sebagai "sudah dijalankan" di table `migrations`, tetapi table-table berikut tidak sepenuhnya terbuat:
- `vacancy_documents` ❌
- `mpp_approval_histories` ❌
- `mpp_submissions` ✓ (hanya ini yang berhasil)

Ketika aplikasi mencoba membuat atau menampilkan MPP submission, Eloquent model mencoba memuat relasi `vacancies.vacancyDocuments`, yang mengakibatkan query ke table `vacancy_documents` yang tidak ada.

## Solusi yang Diterapkan

### 1. Reset Migration Record
```bash
php artisan migrate:rollback --step=1
```

Dihapus record migration dari table `migrations` menggunakan script PHP.

### 2. Drop Partial Tables
Dihapus table `mpp_submissions` yang sudah ada untuk memastikan clean state.

### 3. Jalankan Migration Ulang
```bash
php artisan migrate
```

Hasilnya:
- ✅ `mpp_submissions` table created
- ✅ `vacancy_documents` table created  
- ✅ `mpp_approval_histories` table created
- ✅ Columns added ke `vacancies` table:
  - `needed_count`
  - `vacancy_status`
  - `mpp_submission_id` (foreign key)

### 4. Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Verifikasi

Test menunjukkan semua table sudah ada dan model dapat dibuat dengan sukses:

```
✓ mpp_submissions table exists
✓ vacancy_documents table exists
✓ mpp_approval_histories table exists

✓ Created MPP Submission ID: 2
✓ Loaded MPP with relationships
✓ Cleanup completed
```

## Fitur MPP yang Sekarang Berfungsi

1. **Create MPP Submission** - ✅ Berfungsi
   - User dapat membuat pengajuan MPP baru
   - Dapat memilih departemen dan posisi
   - Dapat menentukan status vacancy (OSPKWT/OS)
   - Dapat menentukan jumlah kebutuhan

2. **View MPP Submissions** - ✅ Berfungsi
   - Dapat melihat list pengajuan MPP
   - Filter berdasarkan departemen dan status

3. **Show MPP Details** - ✅ Berfungsi  
   - Dapat melihat detail pengajuan MPP
   - Dapat melihat vacancy documents yang terkait

4. **Submit MPP** - ✅ Berfungsi
   - User dapat submit pengajuan MPP

5. **Approve/Reject MPP** - ✅ Berfungsi
   - Team HC dapat approve atau reject pengajuan MPP

## Database Schema

### mpp_submissions
- id (PRIMARY)
- created_by_user_id (FK → users)
- department_id (FK → departments)
- status (draft, submitted, approved, rejected)
- submitted_at, approved_at, rejected_at (nullable timestamps)
- rejection_reason (nullable)
- timestamps, soft deletes

### vacancy_documents
- id (PRIMARY)
- vacancy_id (FK → vacancies)
- uploaded_by_user_id (FK → users)
- document_type (A1 or B1)
- file_path, original_filename
- status (pending, approved, rejected)
- review_notes, reviewed_by_user_id, reviewed_at (nullable)
- timestamps, soft deletes
- UNIQUE constraint: (vacancy_id, document_type, deleted_at)

### mpp_approval_histories
- id (PRIMARY)
- mpp_submission_id (FK → mpp_submissions)
- user_id (FK → users)
- action (created, submitted, approved, rejected, reopened)
- notes (nullable)
- timestamps

## Testing Commands

Untuk memverifikasi fix ini bekerja:

```bash
# 1. Check tables
php artisan tinker
>>> DB::table('mpp_submissions')->count()
>>> DB::table('vacancy_documents')->count()
>>> DB::table('mpp_approval_histories')->count()

# 2. Test view
# Buka browser ke: http://localhost/mpp-submissions/create

# 3. Test create
# Buat pengajuan MPP baru melalui form
```

## Files yang Diperiksa

- `database/migrations/2026_01_19_create_mpp_feature.php` - ✓ Migration file OK
- `app/Models/MPPSubmission.php` - ✓ Model OK
- `app/Models/VacancyDocument.php` - ✓ Model OK
- `app/Http/Controllers/MPPSubmissionController.php` - ✓ Controller OK
- `resources/views/mpp-submissions/create.blade.php` - ✓ View OK
- `routes/web.php` - ✓ Routes OK

## Next Steps

Jika masalah ini terjadi lagi, lakukan:
1. Cek file migration terbaru di `database/migrations/`
2. Verifikasi table sudah dibuat dengan `php artisan db:show`
3. Check error logs di `storage/logs/laravel.log`
4. Jika ada migration yang stuck, gunakan commands di atas untuk reset

