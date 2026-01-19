# 📁 File Structure - Sistem MPP Feature

## 🆕 File Baru yang Dibuat

### Database
```
database/
├── migrations/
│   └── 2026_01_19_create_mpp_feature.php          ✨ Migration MPP
└── seeders/
    └── MPPPermissionSeeder.php                    ✨ Permissions seeder
```

### Models
```
app/Models/
├── MPPSubmission.php                              ✨ Model untuk MPP
├── VacancyDocument.php                            ✨ Model untuk dokumen vacancy
└── MPPApprovalHistory.php                         ✨ Model untuk audit trail
```

### Controllers
```
app/Http/Controllers/
├── MPPSubmissionController.php                    ✨ Controller untuk MPP CRUD
└── VacancyDocumentController.php                  ✨ Controller untuk dokumen
```

### Views (Blade Templates)
```
resources/views/
├── mpp-submissions/
│   ├── index.blade.php                           ✨ List MPP
│   ├── create.blade.php                          ✨ Form buat MPP
│   └── show.blade.php                            ✨ Detail MPP
├── vacancy-documents/
│   └── upload.blade.php                          ✨ Form upload dokumen
└── proposals/
    └── propose-vacancy.blade.php                 ✨ Tabel Propose Vacancy baru
```

### Documentation
```
root/
├── IMPLEMENTASI_MPP_FEATURE.md                    ✨ Dokumentasi lengkap
└── QUICK_START_MPP.md                            ✨ Quick start guide
```

---

## ✏️ File yang Dimodifikasi

### Routes
```
routes/
└── web.php                                        📝 Tambah MPP routes
```

### Controllers
```
app/Http/Controllers/
└── VacancyProposalController.php                  📝 Tambah method proposeVacancy()
```

### Models
```
app/Models/
├── Vacancy.php                                    📝 Tambah relationship ke MPPSubmission
└── User.php                                       (no changes needed)
```

### Views
```
resources/views/layouts/
└── sidebar.blade.php                             📝 Tambah menu "Pengajuan MPP" & "Propose Vacancy (Tabel)"
```

---

## 🗂️ Complete File Tree

```
recruitment-system/
│
├── 📁 app/
│   ├── 📁 Http/
│   │   └── 📁 Controllers/
│   │       ├── MPPSubmissionController.php        ✨ NEW
│   │       ├── VacancyDocumentController.php      ✨ NEW
│   │       ├── VacancyProposalController.php      📝 MODIFIED
│   │       └── ... (other controllers)
│   │
│   └── 📁 Models/
│       ├── MPPSubmission.php                      ✨ NEW
│       ├── VacancyDocument.php                    ✨ NEW
│       ├── MPPApprovalHistory.php                 ✨ NEW
│       ├── Vacancy.php                            📝 MODIFIED
│       └── ... (other models)
│
├── 📁 database/
│   ├── 📁 migrations/
│   │   ├── 2026_01_19_create_mpp_feature.php     ✨ NEW
│   │   └── ... (other migrations)
│   │
│   └── 📁 seeders/
│       ├── MPPPermissionSeeder.php                ✨ NEW
│       └── ... (other seeders)
│
├── 📁 resources/
│   └── 📁 views/
│       ├── 📁 mpp-submissions/
│       │   ├── index.blade.php                   ✨ NEW
│       │   ├── create.blade.php                  ✨ NEW
│       │   └── show.blade.php                    ✨ NEW
│       │
│       ├── 📁 vacancy-documents/
│       │   └── upload.blade.php                  ✨ NEW
│       │
│       ├── 📁 proposals/
│       │   └── propose-vacancy.blade.php         ✨ NEW
│       │
│       └── 📁 layouts/
│           └── sidebar.blade.php                 📝 MODIFIED
│
├── 📁 routes/
│   └── web.php                                   📝 MODIFIED
│
├── IMPLEMENTASI_MPP_FEATURE.md                   ✨ NEW
├── QUICK_START_MPP.md                            ✨ NEW
└── ... (other files)
```

---

## 📊 Database Schema Summary

### Tables Created

#### mpp_submissions
```sql
CREATE TABLE mpp_submissions (
    id BIGINT PRIMARY KEY,
    created_by_user_id BIGINT FOREIGN KEY,
    department_id BIGINT FOREIGN KEY,
    status VARCHAR (255),
    submitted_at TIMESTAMP,
    approved_at TIMESTAMP,
    rejected_at TIMESTAMP,
    rejection_reason TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

#### vacancy_documents
```sql
CREATE TABLE vacancy_documents (
    id BIGINT PRIMARY KEY,
    vacancy_id BIGINT FOREIGN KEY,
    uploaded_by_user_id BIGINT FOREIGN KEY,
    document_type VARCHAR (255),
    file_path VARCHAR (255),
    original_filename VARCHAR (255),
    status VARCHAR (255),
    review_notes TEXT,
    reviewed_by_user_id BIGINT FOREIGN KEY,
    reviewed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

#### mpp_approval_histories
```sql
CREATE TABLE mpp_approval_histories (
    id BIGINT PRIMARY KEY,
    mpp_submission_id BIGINT FOREIGN KEY,
    user_id BIGINT FOREIGN KEY,
    action VARCHAR (255),
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### vacancies (columns added)
```sql
ALTER TABLE vacancies ADD COLUMN vacancy_status VARCHAR (255);
ALTER TABLE vacancies ADD COLUMN mpp_submission_id BIGINT FOREIGN KEY;
```

---

## 🔗 Relationships

```
User (1) ──────→ (many) MPPSubmission
         
Department (1) ──────→ (many) MPPSubmission

MPPSubmission (1) ──────→ (many) Vacancy
              (1) ──────→ (many) MPPApprovalHistory

Vacancy (1) ──────→ (many) VacancyDocument
        (1) ──────→ (many) MPPApprovalHistory (via history)

User (1) ──────→ (many) VacancyDocument (uploaded_by)
     (1) ──────→ (many) VacancyDocument (reviewed_by)
     (1) ──────→ (many) MPPApprovalHistory
```

---

## 🎨 Route Structure

```
/mpp-submissions                           GET    List MPP
/mpp-submissions/create                   GET    Create form
/mpp-submissions                          POST   Store
/mpp-submissions/{id}                     GET    Show detail
/mpp-submissions/{id}/submit              POST   Submit
/mpp-submissions/{id}/approve             POST   Approve
/mpp-submissions/{id}/reject              POST   Reject
/mpp-submissions/{id}                     DELETE Delete

/vacancies/{vacancy}/documents            GET    Upload form
/vacancies/{vacancy}/documents            POST   Store upload
/vacancies/{vacancy}/documents/{id}/download     GET    Download
/vacancies/{vacancy}/documents/{id}/approve     POST   Approve
/vacancies/{vacancy}/documents/{id}/reject      POST   Reject
/vacancies/{vacancy}/documents/{id}            DELETE Delete

/propose-vacancy                           GET    Tabel view baru
```

---

## 🔐 Permissions Structure

```
Permissions:
├── view-mpp-submissions
├── create-mpp-submission
├── submit-mpp-submission
├── view-mpp-submission-details
├── approve-mpp-submission
├── reject-mpp-submission
├── delete-mpp-submission
├── upload-vacancy-document
├── download-vacancy-document
├── approve-vacancy-document
├── reject-vacancy-document
└── delete-vacancy-document

Roles:
├── team_hc
│   ├── view-mpp-submissions
│   ├── create-mpp-submission
│   ├── submit-mpp-submission
│   ├── view-mpp-submission-details
│   ├── approve-mpp-submission
│   ├── reject-mpp-submission
│   ├── delete-mpp-submission
│   ├── approve-vacancy-document
│   ├── reject-vacancy-document
│   └── download-vacancy-document
│
└── department_head
    ├── view-mpp-submissions
    ├── view-mpp-submission-details
    ├── upload-vacancy-document
    ├── download-vacancy-document
    └── delete-vacancy-document
```

---

## 📦 Dependencies

### Bawaan Laravel:
- Eloquent ORM
- Blade Templating
- File Storage
- Validation

### Tambahan (sudah di composer.json):
- spatie/laravel-permission (untuk role & permission)
- maatwebsite/excel (jika diperlukan)

---

## 🚀 Deployment Checklist

- [x] Migration file dibuat
- [x] Models dibuat dengan relationships
- [x] Controllers dibuat dengan methods lengkap
- [x] Routes diupdate
- [x] Blade templates dibuat
- [x] Permissions seeder dibuat
- [x] Sidebar menu diupdate
- [x] Documentation dibuat
- [x] Quick start guide dibuat
- [x] Validation rules implemented
- [x] Error handling implemented
- [x] File storage configured
- [x] Soft deletes implemented
- [x] Audit trail tracking
- [x] Permission checks added

---

## 📝 Notes

1. **File Storage**: Menggunakan storage private untuk security
2. **Soft Deletes**: Data bisa di-restore jika perlu
3. **Cascade Delete**: Hapus MPP → hapus semua documents otomatis
4. **Validation**: Input validation di setiap endpoint
5. **Permission Checks**: Role-based access control di semua methods

---

**Last Updated: 2026-01-19**
**Status: ✅ Complete**
