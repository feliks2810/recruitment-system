# 🚀 QUICK START - Sistem MPP Feature

## ⚡ Setup Cepat (3 Steps)

### Step 1: Jalankan Migration
```bash
cd c:/xampp/htdocs/recruitment-system
php artisan migrate
```

### Step 2: Setup Permissions
```bash
php artisan db:seed --class=MPPPermissionSeeder
```

### Step 3: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
```

✅ **DONE!** Sistem siap digunakan.

---

## 📱 Fitur Utama

### 1️⃣ PENGAJUAN MPP (Team HC)
**URL:** `/mpp-submissions/create`

**Steps:**
1. Pilih Departemen
2. Tambah Posisi (bisa multiple)
3. Set Status Vacancy (OSPKWT atau OS)
4. Submit

**Hasil:**
- MPP tersimpan dengan status `draft`
- Department Head dapat upload dokumen

---

### 2️⃣ UPLOAD DOKUMEN (Department Head)
**URL:** `/mpp-submissions` → Klik Detail → "Lihat Dokumen"

**Steps:**
1. Buka detail MPP
2. Klik "Lihat Dokumen" di setiap posisi
3. Upload dokumen sesuai status:
   - OSPKWT → Upload Dokumen A1
   - OS → Upload Dokumen B1
4. Submit

**Hasil:**
- Dokumen masuk status `pending`
- Tunggu review Team HC

---

### 3️⃣ REVIEW DOKUMEN (Team HC)
**URL:** `/propose-vacancy`

**Features:**
- Tabel dengan 3 kolom utama:
  - Position MPP
  - Status
  - Kelengkapan Dokumen
- Tombol Approve/Reject dokumen
- View dokumen yang sudah upload

**Steps:**
1. Buka halaman Propose Vacancy
2. Lihat status dokumen di kolom "Kelengkapan Dokumen"
3. Klik "Lihat Dokumen" untuk review
4. Approve atau Reject dengan catatan

---

## 🎯 Dashboard Menu

### Sidebar - Untuk Team HC:
```
Dashboard
├── Kandidat
├── Propose Vacancy (Tabel)  ← NEW
├── Vacancy Proposals (Legacy)
├── Pengajuan MPP             ← NEW
├── Import Excel
├── Statistik
├── Manajemen Akun
├── Manajemen Departemen
├── Pengelolaan Posisi
├── Posisi & Pelamar
└── Dokumen
```

### Sidebar - Untuk Department Head:
```
Dashboard
├── Kandidat
├── Pengajuan MPP             ← NEW
└── (terbatas akses)
```

---

## 🔗 URL Mapping

| Fitur | URL | Role |
|-------|-----|------|
| List MPP | `/mpp-submissions` | Team HC, Dept Head |
| Buat MPP | `/mpp-submissions/create` | Team HC |
| Detail MPP | `/mpp-submissions/{id}` | Team HC, Dept Head |
| Upload Dokumen | `/vacancies/{id}/documents` | Dept Head |
| Propose Vacancy (Tabel) | `/propose-vacancy` | Team HC |
| Download Dokumen | `/vacancies/{vacancy}/documents/{id}/download` | Both |

---

## 📊 Status Tracking

### MPP Status:
- 🟦 **Draft** - Baru dibuat, belum submit
- 🟨 **Submitted** - Sudah submit, menunggu Dept Head upload dokumen
- 🟩 **Approved** - Disetujui, siap proses
- 🟥 **Rejected** - Ditolak

### Dokumen Status:
- 🟨 **Pending** - Menunggu review
- 🟩 **Approved** - Disetujui
- 🟥 **Rejected** - Ditolak

---

## ✨ Fitur Unggulan

✅ **Validasi Dokumen Otomatis**
- OSPKWT hanya terima dokumen A1
- OS hanya terima dokumen B1
- Notifikasi error jika tidak sesuai

✅ **Upload Multiple Posisi**
- 1 MPP bisa berisi banyak posisi
- Setiap posisi bisa status berbeda
- Dokumen tracked per posisi

✅ **Approval History**
- Semua action tercatat
- Siapa yang do apa, kapan
- Catatan untuk setiap action

✅ **File Management**
- Storage private (aman)
- Download dengan kontrol akses
- Soft delete (recover bisa)

---

## 🔒 Permission Management

### Untuk mengubah permission:

Edit file: `database/seeders/MPPPermissionSeeder.php`

```php
// Contoh: kasih permission ke role baru
$customRole = Role::firstOrCreate(['name' => 'custom_role']);
$customRole->givePermissionTo([
    'view-mpp-submissions',
    'upload-vacancy-document',
]);
```

Kemudian jalankan:
```bash
php artisan db:seed --class=MPPPermissionSeeder
```

---

## 🐛 Common Issues & Solutions

### ❌ "Unauthorized" saat buat MPP
**Solusi:** User perlu permission `create-mpp-submission`
```bash
# Check permission di database
SELECT * FROM permissions WHERE name LIKE '%mpp%';
```

### ❌ Dokumen tidak bisa upload
**Solusi:** Pastikan:
1. User adalah Department Head
2. Vacancy sudah punya status (OSPKWT/OS)
3. Storage write permission OK

### ❌ Sidebar tidak muncul menu MPP
**Solusi:** 
1. Clear cache: `php artisan cache:clear`
2. Check permission di user
3. Reload browser (Ctrl+F5)

---

## 📞 File Penting Untuk Referensi

| File | Lokasi | Tujuan |
|------|--------|--------|
| Migration | `database/migrations/2026_01_19_create_mpp_feature.php` | Struktur DB |
| Models | `app/Models/MPPSubmission.php` etc | Logic bisnis |
| Controllers | `app/Http/Controllers/MPPSubmissionController.php` | Request handling |
| Routes | `routes/web.php` | URL mapping |
| Permissions | `database/seeders/MPPPermissionSeeder.php` | Akses control |
| Blade Templates | `resources/views/mpp-submissions/` | Tampilan UI |

---

## 📋 Checklist Deployment

- [ ] Run migration
- [ ] Run seeder
- [ ] Clear cache
- [ ] Test create MPP (Team HC)
- [ ] Test upload dokumen (Dept Head)
- [ ] Test approve dokumen (Team HC)
- [ ] Test Propose Vacancy tabel
- [ ] Verify sidebar menu muncul
- [ ] Check all permissions working
- [ ] Test edge cases (reject, delete, etc)

---

## 🎓 Learning Path

1. **Pahami Alur**: Baca `IMPLEMENTASI_MPP_FEATURE.md`
2. **Setup Database**: Jalankan migration + seeder
3. **Test Manual**: Coba buat MPP → Upload dokumen → Approve
4. **Debug jika perlu**: Check logs di `storage/logs/`
5. **Customize**: Edit templates/permissions sesuai kebutuhan

---

## 📞 Support

**Jika ada masalah:**

1. Cek error di console browser (F12)
2. Cek error di `storage/logs/laravel.log`
3. Cek database dengan SQL query
4. Baca dokumentasi lengkap di `IMPLEMENTASI_MPP_FEATURE.md`

**Format error log yang berguna:**
```
[2026-01-19 10:30:00] local.ERROR: Unauthorized upload attempt
[2026-01-19 10:30:01] local.DEBUG: User ID: 5, Permission: upload-vacancy-document
```

---

**Status: ✅ PRODUCTION READY**
**Last Update: 2026-01-19**
**Version: 1.0.0**
