# 📋 Recruitment System - Clean Setup

## ✅ Status: PRODUCTION READY

Sistem sudah dibersihkan dan dioptimasi untuk production.

---

## 🔐 Login Credentials

### Admin
- **Email**: `admin@airsys.com`
- **Password**: `password`
- **Role**: admin

### Team HC (Main)
- **Email**: `hc1@airsys.com`
- **Password**: `password`
- **Role**: team_hc

### Team HC (Secondary)
- **Email**: `hc2@airsys.com`
- **Password**: `password`
- **Role**: team_hc_2

### Department Heads
- **Email Format**: `head-{department-slug}@airsys.com`
- **Password**: `password` (all)
- **Role**: department_head
- **Examples**:
  - `head-batam-production@airsys.com`
  - `head-engineering@airsys.com`
  - `head-finance-accounting@airsys.com`

---

## 📊 Data Structure

### 4 Roles
- `admin` - System administrator
- `team_hc` - Main HC team (full access)
- `team_hc_2` - Secondary HC team (limited access)
- `department_head` - Department head (own dept only)

### 11 Departments
1. Batam Production
2. Batam QA & QC
3. Engineering
4. Finance & Accounting
5. HCGAESRIT
6. Strategic Planning Function
7. Procurement & Subcontractor
8. Production Control
9. PE & Facility
10. Warehouse & Inventory
11. Marketing, Business Development & Sales Ship Building

### 48 Vacancies
Distributed across all 11 departments with specific positions per department.

---

## 🛠️ Seeder Files

**Active Seeders** (5 files):
1. `RolesAndPermissionsSeeder.php` - 4 roles + 52 permissions
2. `DepartmentSeeder.php` - 11 departments
3. `UserSeeder.php` - Users for all roles + department heads
4. `VacancySeeder.php` - 48 vacancies
5. `MPPPermissionSeeder.php` - MPP-related permissions

**Optional Seeders**:
- `DepartmentUsersSeeder.php` - Can create additional staff users
- `CandidateSeeder.php` - Sample candidates
- `ApplicationStageSeeder.php` - Application stages

---

## 🚀 Quick Commands

```bash
# Fresh setup (migrate + seed)
php artisan migrate:fresh --seed

# Only seed
php artisan db:seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset

# Seed specific seeder
php artisan db:seed --class=VacancySeeder
```

---

## ✨ Key Features

✅ Clean role-based permission system  
✅ Automatic department head creation  
✅ Complete vacancy mapping per department  
✅ MPP (Manpower Planning) module with permissions  
✅ Multi-level access control  
✅ Production-ready data structure  

---

## 📁 Project Structure

```
recruitment-system/
├── app/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   │   ├── RolesAndPermissionsSeeder.php ✨
│   │   ├── DepartmentSeeder.php
│   │   ├── UserSeeder.php
│   │   ├── VacancySeeder.php
│   │   ├── MPPPermissionSeeder.php
│   │   └── ...
│   └── factories/
├── resources/
├── routes/
├── storage/
├── tests/
└── README.md
```

---

**Last Updated**: January 20, 2026  
**Maintained by**: AI Assistant  
✅ **Status**: Production Ready
