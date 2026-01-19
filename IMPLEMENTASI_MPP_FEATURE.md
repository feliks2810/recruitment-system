# Dokumentasi Implementasi Sistem MPP (Manpower Planning Request)

## 📋 Ringkasan Fitur

Sistem MPP telah diimplementasikan dengan lengkap untuk mendukung alur pengajuan manpower planning dengan dokumen pendukung. Fitur ini mencakup:

1. **Pengajuan MPP oleh Team HC**
2. **Notifikasi ke Department Head**
3. **Upload Dokumen oleh Department Head**
4. **Review & Approval oleh Team HC**
5. **Tampilan Tabel Propose Vacancy yang Diperbaharui**

---

## 🗄️ Database Changes

### File Migrasi: `2026_01_19_create_mpp_feature.php`

#### Tabel yang Dibuat:

1. **`mpp_submissions`** - Menyimpan data pengajuan MPP
   - `id` (Primary Key)
   - `created_by_user_id` (FK: users)
   - `department_id` (FK: departments)
   - `status` (draft, submitted, approved, rejected)
   - `submitted_at`, `approved_at`, `rejected_at`
   - `rejection_reason`
   - timestamps & soft deletes

2. **`vacancy_documents`** - Menyimpan dokumen vacancy (A1/B1)
   - `id` (Primary Key)
   - `vacancy_id` (FK: vacancies)
   - `uploaded_by_user_id` (FK: users)
   - `document_type` (A1 atau B1)
   - `file_path`, `original_filename`
   - `status` (pending, approved, rejected)
   - `review_notes`
   - `reviewed_by_user_id`, `reviewed_at`
   - timestamps & soft deletes

3. **`mpp_approval_histories`** - Menyimpan riwayat approval MPP
   - `id` (Primary Key)
   - `mpp_submission_id` (FK: mpp_submissions)
   - `user_id` (FK: users)
   - `action` (created, submitted, approved, rejected, reopened)
   - `notes`
   - timestamps

#### Kolom yang Ditambah ke `vacancies`:

- `vacancy_status` - Status vacancy (OSPKWT atau OS)
- `mpp_submission_id` (FK: mpp_submissions)

---

## 📦 Models yang Dibuat

### 1. **MPPSubmission.php**
Model untuk menyimpan data pengajuan MPP dengan methods:
- `submit()` - Untuk submit pengajuan
- `approve()` - Untuk approve pengajuan
- `reject()` - Untuk reject pengajuan
- `notifyDepartmentHeads()` - Notifikasi ke department head

### 2. **VacancyDocument.php**
Model untuk menyimpan dokumen vacancy:
- `approve()` - Approve dokumen
- `reject()` - Reject dokumen
- `isApproved()` - Check status
- `isPending()` - Check status
- `isRejected()` - Check status

### 3. **MPPApprovalHistory.php**
Model untuk audit trail approval MPP

---

## 🎮 Controllers

### 1. **MPPSubmissionController.php**
Endpoints:
- `GET /mpp-submissions` - List pengajuan MPP
- `GET /mpp-submissions/create` - Form buat MPP
- `POST /mpp-submissions` - Store MPP baru
- `GET /mpp-submissions/{id}` - Detail MPP
- `POST /mpp-submissions/{id}/submit` - Submit MPP
- `POST /mpp-submissions/{id}/approve` - Approve MPP
- `POST /mpp-submissions/{id}/reject` - Reject MPP
- `DELETE /mpp-submissions/{id}` - Delete MPP

### 2. **VacancyDocumentController.php**
Endpoints:
- `GET /vacancies/{vacancy}/documents` - Form upload dokumen
- `POST /vacancies/{vacancy}/documents` - Upload dokumen
- `GET /vacancies/{vacancy}/documents/{document}/download` - Download dokumen
- `POST /vacancies/{vacancy}/documents/{document}/approve` - Approve dokumen
- `POST /vacancies/{vacancy}/documents/{document}/reject` - Reject dokumen
- `DELETE /vacancies/{vacancy}/documents/{document}` - Delete dokumen

### 3. **VacancyProposalController.php**
Method baru:
- `proposeVacancy()` - Tampilan tabel Propose Vacancy yang baru

---

## 🎨 Blade Templates

### 1. **mpp-submissions/index.blade.php**
Menampilkan list pengajuan MPP dengan:
- Filter berdasarkan status
- Pagination
- Informasi posisi yang diajukan
- Status approval

### 2. **mpp-submissions/create.blade.php**
Form untuk membuat pengajuan MPP dengan:
- Dropdown departemen
- Dropdown posisi (dinamis berdasarkan departemen)
- Selection status vacancy (OSPKWT/OS)
- Tombol tambah/hapus posisi

### 3. **mpp-submissions/show.blade.php**
Detail pengajuan MPP dengan:
- Informasi MPP
- Tabel posisi & dokumen
- Riwayat approval
- Tombol action (submit, approve, reject, delete)

### 4. **vacancy-documents/upload.blade.php**
Form upload dokumen dengan:
- Info status vacancy
- File picker (drag & drop support)
- Preview dokumen yang sudah upload
- Informasi status review
- Tombol approve/reject untuk Team HC

### 5. **proposals/propose-vacancy.blade.php**
Tampilan tabel Propose Vacancy dengan 3 kolom utama:
- **Position MPP** - Nama posisi, departemen, MPP ID
- **Status** - Vacancy status (OSPKWT/OS) dan Proposal status
- **Kelengkapan Dokumen** - Dokumen yang diperlukan dan statusnya

---

## 🔐 Permissions & Roles

### Permissions yang Ditambah:
```
- view-mpp-submissions
- create-mpp-submission
- submit-mpp-submission
- view-mpp-submission-details
- approve-mpp-submission
- reject-mpp-submission
- delete-mpp-submission
- upload-vacancy-document
- download-vacancy-document
- approve-vacancy-document
- reject-vacancy-document
- delete-vacancy-document
```

### Role Permissions:

**Team HC:**
- create-mpp-submission
- submit-mpp-submission
- view-mpp-submissions
- view-mpp-submission-details
- approve-mpp-submission
- reject-mpp-submission
- delete-mpp-submission
- approve-vacancy-document
- reject-vacancy-document
- download-vacancy-document

**Department Head:**
- view-mpp-submissions
- view-mpp-submission-details
- upload-vacancy-document
- download-vacancy-document
- delete-vacancy-document

---

## 🌐 Routes

### MPP Submission Routes:
```
GET    /mpp-submissions              - List MPP
GET    /mpp-submissions/create       - Create form
POST   /mpp-submissions              - Store
GET    /mpp-submissions/{id}         - Show detail
POST   /mpp-submissions/{id}/submit  - Submit
POST   /mpp-submissions/{id}/approve - Approve
POST   /mpp-submissions/{id}/reject  - Reject
DELETE /mpp-submissions/{id}         - Delete
```

### Vacancy Document Routes:
```
GET    /vacancies/{vacancy}/documents                           - Upload form
POST   /vacancies/{vacancy}/documents                           - Store
GET    /vacancies/{vacancy}/documents/{document}/download       - Download
POST   /vacancies/{vacancy}/documents/{document}/approve        - Approve
POST   /vacancies/{vacancy}/documents/{document}/reject         - Reject
DELETE /vacancies/{vacancy}/documents/{document}                - Delete
```

### Propose Vacancy Routes:
```
GET    /propose-vacancy              - Tampilan tabel baru
```

---

## 📊 Alur Sistem

### 1️⃣ Team HC Membuat Pengajuan MPP
- Akses: `/mpp-submissions/create`
- Pilih departemen → Pilih posisi → Set status (OSPKWT/OS)
- Submit pengajuan ke sistem

### 2️⃣ MPP Dikirim ke Department Head
- Department Head menerima notifikasi
- Data MPP tersimpan dengan status `submitted`
- Department Head dapat lihat di `/mpp-submissions`

### 3️⃣ Department Head Upload Dokumen
- Akses halaman detail MPP
- Klik "Lihat Dokumen" untuk setiap posisi
- Upload dokumen sesuai status (A1 untuk OSPKWT, B1 untuk OS)
- Dokumen masuk status `pending` review

### 4️⃣ Team HC Review & Approve
- Lihat dokumen di `/propose-vacancy` (tabel baru)
- Approve/Reject dokumen dengan catatan
- Dokumen berubah status ke `approved` atau `rejected`

### 5️⃣ Tampilan Akhir
- **Propose Vacancy (Tabel)**: Menampilkan semua vacancy dengan status & kelengkapan dokumen
- **MPP Submissions**: List pengajuan MPP dengan progress
- **Posisi & Pelamar**: Update untuk menampilkan dokumen yang sudah approve

---

## 🔄 Workflow Diagram

```
Team HC                          Department Head                  System
   |                                   |                            |
   |---> Create MPP (1)  ---------->   |                            |
   |                                   |                            |
   |                                   | <--- Upload Dokumen -----> |
   |                                   |                            |
   |---> Review Dokumen <----------    |                            |
   |                                   |                            |
   |---> Approve/Reject (2) ---------> | [Status Updated]           |
   |                                   |                            |
   |<--- Notification (3) -----------  |                            |
   |                                   |                            |
   |---> Final Review (4) ---------->  |                            |
   |                                   |                            |
   | [Vacancy Approved & Recruited]    |                            |
   |                                   |                            |
```

---

## 📝 Database Seed Command

Jalankan seeder untuk permissions:
```bash
php artisan db:seed --class=MPPPermissionSeeder
```

---

## 🚀 Cara Menggunakan

### Setup Database:
```bash
# Run migration
php artisan migrate

# Run seeder untuk permissions
php artisan db:seed --class=MPPPermissionSeeder
```

### Akses Fitur:

1. **Team HC - Buat Pengajuan MPP:**
   - Dashboard → Sidebar → "Pengajuan MPP" → Create
   - Fill form dengan posisi dan status
   - Submit

2. **Department Head - Upload Dokumen:**
   - Dashboard → Sidebar → "Pengajuan MPP"
   - Pilih MPP → "Lihat Dokumen"
   - Upload dokumen sesuai status

3. **Team HC - Review Dokumen:**
   - Dashboard → Sidebar → "Propose Vacancy (Tabel)" atau "Propose Vacancy (Legacy)"
   - Lihat status dokumen
   - Approve/Reject dengan catatan

---

## 📌 Notes Penting

1. **Dokumen Type Validation**: Sistem secara otomatis mengecek apakah dokumen yang diupload sesuai dengan status vacancy:
   - OSPKWT → Wajib Dokumen A1
   - OS → Wajib Dokumen B1

2. **File Storage**: Dokumen disimpan di storage private untuk keamanan

3. **Audit Trail**: Semua action (create, submit, approve, reject) tercatat di `mpp_approval_histories`

4. **Soft Delete**: Kedua table menggunakan soft delete untuk data integrity

5. **Cascade Deletes**: Ketika MPP dihapus, semua vacancy documents juga dihapus (cascade)

---

## ✅ Checklist Implementasi

- ✅ Database migration
- ✅ Models (MPPSubmission, VacancyDocument, MPPApprovalHistory)
- ✅ Controllers (MPPSubmissionController, VacancyDocumentController)
- ✅ Blade Templates untuk semua halaman
- ✅ Routes dan URL structure
- ✅ Permissions dan role assignment
- ✅ Sidebar menu update
- ✅ Propose Vacancy redesign (tabel dengan 3 kolom)
- ✅ Document upload functionality
- ✅ Approval history tracking
- ✅ Validation dan error handling

---

## 🔧 Troubleshooting

### Dokumen tidak bisa diupload:
- Check permission `upload-vacancy-document`
- Pastikan vacancy_status sudah diset (OSPKWT atau OS)
- Check storage permissions

### MPP tidak muncul di sidebar:
- Check permission `view-mpp-submissions`
- Pastikan user memiliki role yang tepat

### Dokumen tidak muncul di Propose Vacancy:
- Check apakah vacancy sudah link ke mpp_submission
- Check apakah vacancy_documents sudah dibuat
- Verify vacancy_status tidak null

---

Generated: 2026-01-19
Last Updated: 2026-01-19
