# Pengujian Sistem: Black Box Testing

Pengujian *Black Box* (Black Box Testing) dilakukan untuk menguji fungsionalitas sistem berdasarkan spesifikasi kebutuhan perangkat lunak tanpa melihat struktur logika internal kode. Pengujian difokuskan pada input dan output yang dihasilkan pada saat menjalankan fitur-fitur di dalam aplikasi.

Berikut rancangan skenario pengujian *Black Box* untuk Recruitment & Internship Management System:

## 1. Modul Autentikasi & Akun

| ID Test | Deskripsi Pengujian | Skenario / Langkah Pengujian | Hasil yang Diharapkan (_Expected Result_) | Hasil Aktual | Status |
|---------|---------------------|------------------------------|-----------------------------------------|--------------|--------|
| TC-01   | Login Kredensial Valid | Mengisi email dan password yang benar sesuai data di _database_, lalu klik tombol "Login". | Sistem mengenali kredensial, memberikan akses masuk, dan mengarahkan pengguna ke halaman *Dashboard* sesuai dengan _role_. | Sesuai | **Valid** |
| TC-02   | Login Kredensial Invalid | Mengisi email yang terdaftar tapi memasukkan password yang salah, lalu klik "Login". | Sistem menolak akses masuk dan menampilkan pesan *error* autentikasi. | Sesuai | **Valid** |
| TC-03   | Login Format Email Salah | Mengisi kolom email tanpa format yang benar (misal: `admin123`), lalu klik "Login". | Sistem menolak inputan dan menampilkan peringatan form (wajib menggunakan format `@` email). | Sesuai | **Valid** |
| TC-04   | Fitur Lupa Password | Mengakses menu "Forgot Password", memasukkan email terdaftar, lalu klik "Send Reset Link". | Sistem mengirimkan _link_ ke email pengguna untuk mereset kata sandi dan menampilkan notifikasi sukses. | Sesuai | **Valid** |

## 2. Modul Manajemen Kandidat (_Candidate Management_)

| ID Test | Deskripsi Pengujian | Skenario / Langkah Pengujian | Hasil yang Diharapkan (_Expected Result_) | Hasil Aktual | Status |
|---------|---------------------|------------------------------|-----------------------------------------|--------------|--------|
| TC-05   | Tambah Kandidat Valid | Mengisi form kandidat dengan data dan format lampiran yang tepat, lalu klik "Simpan". | Data kandidat tersimpan di database dan tabel sistem menampilkan _record_ baru beserta pesan sukses. | Sesuai | **Valid** |
| TC-06   | Tambah Data Form Kosong | Membiarkan form wajib dikelompokkan, lalu langsung menekan tombol "Simpan". | Sistem menahan proses *submit* dan memberikan notifikasi *validation error* (*required field*). | Sesuai | **Valid** |
| TC-07   | Ubah Data Kandidat | Menekan tombol "Edit", memodifikasi salah satu atribut (misal: nomor HP), lalu "Simpan". | Data diperbarui di _database_ dan tabel menampilkan perubahan data secara instan. | Sesuai | **Valid** |
| TC-08   | Hapus Data Individual | Menekan tombol "Delete" pada salah satu data, lalu memberikan konfirmasi _alert_. | Data terhapus dari tabel dan tidak dapat diakses lagi. | Sesuai | **Valid** |
| TC-09   | _Bulk Delete_ Kandidat | Melakukan *checklist* pada beberapa baris data sekaligus, lalu klik "Hapus Terpilih". | Semua data yang terpilih terhapus bersamaan dari tabel dan sistem memunculkan notifikasi sukses kolektif. | Sesuai | **Valid** |

## 3. Modul Manajemen Lowongan (_Vacancy Management_)

| ID Test | Deskripsi Pengujian | Skenario / Langkah Pengujian | Hasil yang Diharapkan (_Expected Result_) | Hasil Aktual | Status |
|---------|---------------------|------------------------------|-----------------------------------------|--------------|--------|
| TC-10   | Buat Lowongan Baru | Mengisi jabatan, jumlah kuota, deskripsi, tanggal penutupan, lalu menyimpan lowongan. | Sistem membuat postingan lowongan berstatus *Active*/*Open* di *Jobs Board*. | Sesuai | **Valid** |
| TC-11   | Validasi Tanggal Expired | Mengatur "Tanggal Penutupan" lebih lampau dari tanggal saat pembuatan. | Sistem menolak penginputan tanggal dan memberikan informasi *validation date exception*. | Sesuai | **Valid** |
| TC-12   | Ubah Status Lowongan | Membuka portal admin dan mengubah status _Vacancy_ dari *Open* menjadi *Closed*. | Lowongan ter-*update* menjadi *Closed* dan pelamar baru tidak dapat menemukan atau melamar ke jabatan tersebut. | Sesuai | **Valid** |

## 4. Modul Pengajuan Man Power Planning (MPP)

| ID Test | Deskripsi Pengujian | Skenario / Langkah Pengujian | Hasil yang Diharapkan (_Expected Result_) | Hasil Aktual | Status |
|---------|---------------------|------------------------------|-----------------------------------------|--------------|--------|
| TC-13   | _Submit_ Pengajuan MPP | Mengisi rincian _Manpower Planning_, memilih departemen, lalu melakukan _submit_. | _Draft_ MPP berhasil dikirim dan tersimpan dengan status registrasi *Pending Approval*. | Sesuai | **Valid** |
| TC-14   | Approval MPP oleh Admin | Admin melihat daftar MPP _Pending_, melakukan verifikasi, lalu klik tombol "Approve". | Status MPP berubah menjadi *Approved*, memberi jalan bagi HR untuk membuka loker baru. | Sesuai | **Valid** |
| TC-15   | Penolakan MPP (Reject) | Admin melakukan klik pada tombol "Reject" dan wajib mengisi kolom alasan/keterangan. | Status MPP berubah menjadi *Rejected* dan alasan penolakan dapat dilihat oleh *Departement Head* yang melakukan *request*. | Sesuai | **Valid** |

## 5. Modul Dashboard & Laporan

| ID Test | Deskripsi Pengujian | Skenario / Langkah Pengujian | Hasil yang Diharapkan (_Expected Result_) | Hasil Aktual | Status |
|---------|---------------------|------------------------------|-----------------------------------------|--------------|--------|
| TC-16   | Tampilan Statistik | Membuka halaman visualisasi _Dashboard_ sebagai Administrator (Super Admin). | Sistem secara _real-time_ memuat keseluruhan data statistik seperti total kandidat, MPP terbuka, dsb via *chart*/grafik. | Sesuai | **Valid** |
| TC-17   | Filter Data Laporan | Melakukan filter ekspor laporan karyawan (*Export to PDF/Excel*) berdasarkan tanggal. | File ekspor akan ter-*download* dengan otomatis disesuaikan sesuai rentang filter bulan / tahun yang dimasukkan. | Sesuai | **Valid** |

---

> **Metodologi Pengujian:**  
> Skenario di atas sebagian besar menggunakan metode _Equivalence Partitioning_ (memisahkan *input* data yang valid dan yang tidak valid) untuk memastikan sistem memiliki *handling error* dan *validation rule* yang benar di lapisan presentasi (*Frontend*) maupun pengontrolnya (*Backend*).
