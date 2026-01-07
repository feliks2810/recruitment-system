# Queue Worker Setup Guide

## Overview
Panduan untuk menjalankan Laravel Queue Worker secara otomatis di server lokal dan production.

---

## Opsi 1: Windows Local (XAMPP) - Menggunakan Batch File

### Cara Tercepat (Tanpa Service):

1. **Jalankan manual setiap kali server start:**
   ```bash
   php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=1200
   ```

2. **Atau gunakan batch file yang sudah disediakan:**
   - Klik 2x `scripts/start-queue-worker.bat`
   - Queue worker akan berjalan di window baru

### Cara dengan Auto-Start (Task Scheduler):

1. **Buat shortcut batch file di Startup folder:**
   - Win + R → `shell:startup`
   - Copy shortcut dari `scripts/start-queue-worker.bat` ke folder tersebut
   - Queue worker akan otomatis start saat Windows boot

---

## Opsi 2: Windows Local - Menggunakan NSSM (Recommended)

NSSM (Non-Sucking Service Manager) membuat queue worker berjalan sebagai Windows Service.

### Setup:

1. **Install NSSM via Composer:**
   ```bash
   composer require winbinder/nssm --dev
   ```

2. **Jalankan installer (sebagai Administrator):**
   ```bash
   scripts/start-queue-worker-nssm.bat
   ```

3. **Service akan otomatis:**
   - ✅ Auto-start saat Windows boot
   - ✅ Auto-restart jika crash
   - ✅ Logging di `storage/logs/queue-worker*.log`

### Management Commands:

```bash
# Lihat status
net start | find "RecruitmentQueueWorker"

# Stop service
net stop RecruitmentQueueWorker

# Start service
net start RecruitmentQueueWorker

# Remove service (jika mau uninstall)
# Jalankan: scripts/remove-queue-service.bat
```

---

## Opsi 3: Linux Production Server - Menggunakan Supervisor

Untuk server production Linux (seperti Ubuntu, CentOS).

### Setup:

1. **Install Supervisor:**
   ```bash
   sudo apt-get install supervisor  # Ubuntu/Debian
   sudo yum install supervisor      # CentOS/RedHat
   ```

2. **Copy config file:**
   ```bash
   sudo cp supervisor/recruitment-queue.conf /etc/supervisor/conf.d/recruitment-queue.conf
   ```

3. **Edit path sesuai server Anda:**
   ```bash
   sudo nano /etc/supervisor/conf.d/recruitment-queue.conf
   ```

4. **Update config dan start:**
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start recruitment-queue-worker:*
   ```

### Management:

```bash
# Lihat status semua processes
sudo supervisorctl status

# Stop
sudo supervisorctl stop recruitment-queue-worker:*

# Start
sudo supervisorctl start recruitment-queue-worker:*

# Restart
sudo supervisorctl restart recruitment-queue-worker:*

# Lihat logs
tail -f /var/log/recruitment-queue.log
```

---

## Queue Configuration

File: `config/queue.php`

Saat ini menggunakan:
```php
'default' => env('QUEUE_CONNECTION', 'database'),  // Database queue
'sync' => false  // Non-blocking
```

### Available Queues:
- `database` - Simpan jobs di database (current)
- `sync` - Jalankan langsung (untuk testing, jangan di production)
- `redis` - Gunakan jika ada Redis server
- `sqs` - AWS SQS (jika di cloud)

---

## Troubleshooting

### Queue worker tidak berjalan?
```bash
# Cek jobs yang tertunda
php artisan queue:work --help

# Cek failed jobs
php artisan queue:failed

# Cek database connections
php artisan tinker
> DB::table('jobs')->count()  // Jumlah jobs
```

### Jobs stuck/tidak diproses?
```bash
# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all

# Monitor real-time
php artisan queue:work database --verbose
```

### Logs
- Windows: `storage/logs/queue-worker*.log`
- Linux: `/var/log/recruitment-queue.log`
- Laravel general: `storage/logs/laravel.log`

---

## Performance Tips

1. **Increase workers jika banyak jobs:**
   ```conf
   # Supervisor: ubah numprocs dari 1 ke 3-4
   numprocs=4
   ```

2. **Adjust sleep time:**
   ```bash
   php artisan queue:work --sleep=3  # Check every 3 seconds
   ```

3. **Monitor memory:**
   - Queue worker auto-restart setelah 1200 seconds (20 menit)
   - Prevent memory leaks dari long-running job

---

## Verification

Cara cek apakah queue worker berjalan:

1. **Windows Task Manager:**
   - Lihat processes `php.exe` atau service name

2. **Command line:**
   ```bash
   # Windows
   tasklist | find "php"
   
   # Linux
   ps aux | grep "queue:work"
   ```

3. **Monitor logs real-time:**
   ```bash
   tail -f storage/logs/laravel.log | grep "ProcessCandidateImport"
   ```

4. **Cek database queue status:**
   ```bash
   php artisan tinker
   > DB::table('jobs')->count()  // Should decrease over time
   ```

---

## Next Steps

- [ ] Pilih opsi setup (Windows Batch, NSSM, atau Supervisor)
- [ ] Follow setup instructions
- [ ] Test dengan upload file import kecil
- [ ] Monitor logs
- [ ] Setup auto-start untuk boot persistence
