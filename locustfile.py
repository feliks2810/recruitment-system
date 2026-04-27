from locust import HttpUser, task, between
import re
import random

class RecruitmentSystemUser(HttpUser):
    # Waktu jeda antar request (1 - 3 detik) untuk mensimulasikan perilaku pengguna asli
    wait_time = between(1, 3)

    def on_start(self):
        """
        Dijalankan setiap kali agen/user simulasi mulai.
        Sistem akan secara acak mengambil satu profil kredensial (admin, hc, atau departemen)
        """
        # Daftar kredensial dari ketiga role yang berbeda:
        users = [
            {"email": "admin@airsys.com", "password": "password", "role": "admin"},
            {"email": "hc1@airsys.com", "password": "password", "role": "team_hc"},
            {"email": "head-engineering@airsys.com", "password": "password", "role": "kepala_departemen"}
        ]
        
        # Pilih satu akun secara acak untuk user simulasi ini
        self.user_profile = random.choice(users)
        
        # 1. Buka halaman login untuk mendapatkan form
        response = self.client.get("/login", name="/login (GET)")
        
        # 2. Ekstrak CSRF token dari halaman HTML
        match = re.search(r'name="_token" value="([^"]+)"', response.text)
        if match:
            csrf_token = match.group(1)
        else:
            print("Gagal menemukan CSRF token di halaman login")
            return
            
        # 3. Kirim request POST ke endpoint login
        response = self.client.post("/login", data={
            "_token": csrf_token,
            "email": self.user_profile["email"],
            "password": self.user_profile["password"]
        }, name="/login (POST)")
        
        if response.status_code == 200:
            print(f"Login berhasil sebagai {self.user_profile['role']} ({self.user_profile['email']})")
        else:
            print(f"Login response status: {response.status_code}")

    @task(5)
    def view_dashboard(self):
        """ Semua role bisa melihat dashboard """
        self.client.get("/dashboard", name="/dashboard")
        
    @task(3)
    def view_candidates(self):
        """ Semua role (meskipun dengan data berbeda) bisa mengunjungi halaman daftar kandidat """
        self.client.get("/candidates", name="/candidates")
        
    @task(2)
    def view_calendar(self):
        """ Mensimulasikan user mengecek kalender (tersedia untuk banyak role) """
        self.client.get("/events/calendar", name="/events/calendar")
        
    @task(2)
    def view_vacancies(self):
        """ Hanya Admin/Manajer yang mengatur lowongan. """
        if self.user_profile["role"] == "admin":
            self.client.get("/vacancies", name="/vacancies")
        
    @task(2)
    def view_statistics(self):
        """ Halaman statistik. Admin TIDAK BISA (karena tidak ada permission), tapi Team HC dan Kepala Departemen BISA """
        if self.user_profile["role"] in ["team_hc", "kepala_departemen"]:
            self.client.get("/statistics", name="/statistics")
