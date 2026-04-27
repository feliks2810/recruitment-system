import requests
import re
from urllib.parse import urljoin

BASE_URL = "http://localhost/recruitment-system/public"
session = requests.Session()

def print_result(title, status, detail):
    print(f"\n[{'PASSED' if status else 'FAILED'}] {title}")
    print(f"   => {detail}")

def get_csrf_token():
    try:
        r = session.get(f"{BASE_URL}/login")
        match = re.search(r'name="_token" value="([^"]+)"', r.text)
        return match.group(1) if match else None
    except:
        return None

def test_sqli():
    """ Menguji SQL Injection di halaman Login """
    csrf = get_csrf_token()
    payload = {
        "_token": csrf,
        "email": "admin@airsys.com' OR 1=1 --", # SQL Injection payload
        "password": "password123"
    }
    r = session.post(f"{BASE_URL}/login", data=payload)
    
    # Jika SQL Injection berhasil, maka akan tembus login (masuk ke dashboard)
    # Jika gagal (aman), Laravel akan mendeteksi kredensial invalid dan mengembalikan kembali ke form login
    if "Dashboard" in r.text and "/login" not in r.url:
        print_result("Uji SQL Injection (SQLi)", False, "Celah Terbuka! SQLi menembus login.")
    else:
        print_result("Uji SQL Injection (SQLi)", True, "Sistem kebal. Laravel memblokir karakter kutip (Active Binding).")

def test_csrf():
    """ Menguji perlindungan CSRF (Cross-Site Request Forgery) dengan mengirim request POST TANPA token """
    test_session = requests.Session()
    payload = {
        "email": "admin@airsys.com",
        "password": "password"
    }
    # Tanpa menyertakan _token
    r = test_session.post(f"{BASE_URL}/login", data=payload)
    
    # Jika Laravel melempar code 419 (Page Expired), artinya CSRF protection bekerja
    if r.status_code == 419:
        print_result("Uji Perlindungan Form (CSRF)", True, f"Aman! Request tanpa token ditolak otomatis dengan kode {r.status_code}.")
    else:
        print_result("Uji Perlindungan Form (CSRF)", False, f"Rentant! Bisa request tanpa token (Kode {r.status_code}).")

def test_rbac():
    """ Menguji Akses Ilegal (RBAC) dengan mencoba membuka halaman khusus admin menggunakan akun biasa """
    test_session = requests.Session()
    # Login dulu pakai akun Team HC (bukan admin)
    r_get = test_session.get(f"{BASE_URL}/login")
    match = re.search(r'name="_token" value="([^"]+)"', r_get.text)
    csrf = match.group(1) if match else ""
    
    test_session.post(f"{BASE_URL}/login", data={
        "_token": csrf,
        "email": "hc1@airsys.com",
        "password": "password"
    })
    
    # Mencoba akses paksa halaman /vacancies yang hanya untuk admin
    r_vacancies = test_session.get(f"{BASE_URL}/vacancies")
    
    if r_vacancies.status_code == 403:
        print_result("Uji Broken Access Control (RBAC)", True, "Aman! Akun Non-Admin dilarang masuk dan mendapat kode 403 Forbidden.")
    else:
        print_result("Uji Broken Access Control (RBAC)", False, f"Rentant! Akses lolos dengan kode {r_vacancies.status_code}.")

def test_idor():
    """ Menguji akses langsung ke file privat (Insecure Direct Object Reference) secara Anonim """
    test_session = requests.Session()
    # Asumsikan ada file CV yg valid
    test_url = f"{BASE_URL}/private-files/documents/rahasia.pdf"
    r = test_session.get(test_url)
    
    # Karena tidak login, user harusnya dilempar ke login (302) atau tidak diizinkan
    if "login" in r.url:
        print_result("Uji File Proteksi (IDOR)", True, "Aman! Orang tidak dikenal / tidak login dicegat saat akses file rahasia.")
    else:
        print_result("Uji File Proteksi (IDOR)", False, "Rentant! File pribadi bisa diakses tanpa otentikasi.")

print("Memulai simulasi Serangan (Security Testing) secara live...")
test_sqli()
test_csrf()
test_rbac()
test_idor()
print("\nPengujian selesai.")
