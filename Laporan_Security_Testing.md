# Laporan Security Testing PMP Recruitment System

## 1. Tujuan Pengujian
Pengujian keamanan (_Security Testing_) ini bertujuan untuk mengevaluasi kerentanan aplikasi Recruitment System terhadap ancaman eksploitasi peretas yang paling umum didasari pada pedoman standar **OWASP (Open Web Application Security Project) Top 10**. Pengujian difokuskan pada keutuhan autentikasi, mitigasi injeksi, dan perlindungan data sensitif perusahaan serta kandidat pelamar.

## 2. Metodologi dan Environment
- **Metode Pengujian**: _Manual Vulnerability Assessment_ & _Authorization Logic Testing_
- **Platform/Framework**: PHP Laravel (Web Application)
- **Role Teruji**: Anonim (Guest), Admin, Team HC, Kepala Departemen

---

## 3. Skenario & Rekapitulasi Pengujian

### A. Uji Injeksi (SQL Injection - SQLi)
**Deskripsi**: Menguji apakah celah input aplikasi bisa dimanipulasi untuk membocorkan isi database.
*   **Skenario Uji**: 
    1. Memasukkan karakter kutip tunggal (`' OR 1=1 --`) ke dalam form Login (Email/Password).
    2. Memasukkan syntak SQL pada kolom pencarian nama kandidat.
*   **Ekspektasi**: Aplikasi menolak *query* tersebut dan hanya memprosesnya sebagai string teks biasa.
*   **Status / Hasil**: **[ PASSED ]**
*   **Analisis**: Laravel menggunakan *PDO Parameter Binding* melalui koneksi *Eloquent ORM*. Semua masukkan teks otomatis diamankan (di-_escape_) sebelum menyentuh _database_.

### B. Uji Cross-Site Scripting (XSS)
**Deskripsi**: Menguji apakah input berupa _script_ Javascript berbahaya dapat dijalankan oleh _browser_ pengguna lain ketika mengakses halaman.
*   **Skenario Uji**: 
    1. Melakukan _input_ data kandidat atau nama _Event_ kalender dengan syntak: `<script>alert('Terhack!')</script>`
    2. Menampilkan/merender kembali nama tersebut di halaman Dashboard.
*   **Ekspektasi**: Teks dirender apa adanya (sebagai karakter biasa) tanpa memicu kotak peringatan JS (alert).
*   **Status / Hasil**: **[ PASSED ]**
*   **Analisis**: Modul *templating Blade* bawaan Laravel (menggunakan _tag_ `{{ }}`) secara otomatis memanggil fungsi `htmlspecialchars()`, sehingga elemen HTML yang diinput berubah menjadi bentuk entitas yang aman (tidak tereksekusi oleh _browser_).

### C. Uji Cross-Site Request Forgery (CSRF)
**Deskripsi**: Memastikan pihak ketiga/bot tidak bisa me-_submit_ form layaknya user yang sedang _login_ tanpa token persetujuan rahasia.
*   **Skenario Uji**: 
    1. Melakukan inspeksi (_inspect element_) pada halaman Login atau pembuatan Kandidat.
    2. Menyisipkan form HTML dari luar (_local file_) yang diarahkan ke aksi POST sistem.
*   **Ekspektasi**: Seluruh _submit_ modifikasi data *wajib* memiliki _Hidden Value_ `_token`.
*   **Status / Hasil**: **[ PASSED ]**
*   **Analisis**: Setiap _Route POST/PUT/DELETE_ dilindungi oleh *CSRF Verify Middleware*. Jika _Request_ tidak menyertakan arahan direktif `@csrf`, server akan memblokir dan memberikan halaman error `419 Page Expired` secara otomatis. (Contoh konkrit: Hal ini tertangkap juga saat ujicoba _Locust Testing_ tahap modifikasi bot).

### D. Uji Broken Access Control (Otorisasi Role)
**Deskripsi**: Menguji hak istimewa tiap peran pengguna menggunakan modul perizinan (Spatie Laravel-Permission).
*   **Skenario Uji**: 
    1. _Login_ menggunakan akun `hc1@airsys.com` (Bukan Admin).
    2. Mencoba mengakses URL _endpoint_ admin, yaitu `/vacancies` secara paksa melalui *Address Bar Browser*.
*   **Ekspektasi**: User tanpa kapabilitas ditendang/dilarang dengan halaman error 403.
*   **Status / Hasil**: **[ PASSED ]**
*   **Analisis**: Pengontrolan perizinan dikunci di tingkat *Route* melalui *Middleware* (cth: `->middleware('can:manage-vacancies')`). Hal ini terbukti dari simulasi *Load Testing* di mana peran HC dan Departemen dilempar keluar (mendapat status 403 Forbidden).

### E. Insecure Direct Object Reference (IDOR) & Proteksi File
**Deskripsi**: Memastikan data lampiran sensitif milik kandidat (contoh: CV, Dokumen Vaksin) tidak bisa diakses dan ditebak oleh publik.
*   **Skenario Uji**: 
    1. Me-_log out_ dari sistem (Anonim).
    2. Mencoba menempelkan *URL path file storage* lamaran langsung di _browser_.
*   **Ekspektasi**: Akses terblokir karena dokumen tersimpan bukan di folder publik.
*   **Status / Hasil**: **[ PASSED ]**
*   **Analisis**: Fitur penampilan dokumen memanfaatkan *Route* khusus bermetode tertutup (`/private-files/{filePath}`) melalui `FileController` yang dipagari oleh *middleware* _auth_. Orang yang tidak dalam keadaan _login_ sama sekali tidak bisa mengakses isi folder pribadi pelamar.

### F. Automated Vulnerability Scanning (OWASP ZAP)
**Deskripsi**: Melakukan pemindaian otomatis menggunakan tool _Dynamic Application Security Testing_ (DAST) yaitu **OWASP ZAP (Zed Attack Proxy)** untuk memetakan kerentanan secara masif.
*   **Skenario Uji**: Menjalankan mekanisme _Automated Spidering_ dan _Active Scan_ terhadap URL `http://localhost/recruitment-system/public`.
*   **Status / Hasil**: **[ INFO & LOW ALERTS DETECTED ]**
*   **Analisis Temuan ZAP**:
    Berdasarkan tangkapan layar observasi *Alerts* dari OWASP ZAP, **tidak ditemukan kerentanan level High atau Critical**. Sebagian besar merupakan konfigurasi _HTTP Header_ standar tingkat _Low/Informational_ bawaan server lokal (XAMPP/Apache), di antaranya:
    1. **Content Security Policy (CSP) Header Not Set / X-Content-Type-Options Missing**: Konfigurasi header *security* belum dikuatkan. (Solusi: Menambahkan pengaturan parameter *Header* tersebut pada _middleware_ Laravel di *production*).
    2. **Server Leaks Version Information**: Server Apache mencantumkan versinya di *Response Header*. (Solusi: Mematikan opsi `ServerSignature` dan `ServerTokens` di konfigurasi Apache).
    3. **Information Disclosure - Suspicious Comments**: ZAP mendeteksi ada banyak komentar kode (`//` atau `<!-- -->`) di *source code* HTML/JS akhir yang potensial. (Ini normal untuk *environment* pengembangan).
    4. Secara keseluruhan: Celah inti (*Cross-Site Scripting*, *SQL Injection*) berhasil ditepis.

---

## 4. Kesimpulan Akhir
Berdasarkan keenam metode pengujian sekuritas di atas (termasuk hasil pemindaian ketat dari OWASP ZAP), arsitektur dasar **Recruitment System terbukti aman dari cacat keamanan krusial (High/Critical)**. Implementasi *Framework* Laravel 10/11 secara signifikan telah menangani porsi teknis mitigasi (seperti halau XSS, penangkalan _SQL Injection_, dan _CSRF_), sementara *Developer* tinggal menyempurnakan penambahan _Security HTTP Headers_ di tingkat *Server* saat aplikasi siap dinaikkan ke _Production_.
