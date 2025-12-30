## FIX: Statistik "Lulus" yang tidak hidup

### Masalah
Statistik "Lulus" di halaman Dashboard dan Candidates Index tidak menampilkan angka, padahal sudah ada kandidat yang dinyatakan lulus.

### Root Cause
1. **Logic Error di ApplicationStageService**: 
   - Sebelumnya, ketika tahap akhir (hiring) status LULUS, aplikasi di-set ke `overall_status = 'HIRED'`
   - Tapi UI mencari aplikasi dengan `overall_status = 'LULUS'`
   - Hasil: tidak ada yang ditemukan → statistik selalu 0

2. **Status Terminology Issue**:
   - Old status `GAGAL` should be `DITOLAK` untuk konsistensi

### Solusi
1. **Update ApplicationStageService.php**:
   - Tahap akhir (hiring) LULUS → set `overall_status = 'LULUS'` (berarti fully passed)
   - Tahap apapun DITOLAK → set `overall_status = 'DITOLAK'` (rejected at any stage)
   - Tahap lain (tidak akhir) LULUS → set `overall_status = 'PROSES'` (still in process)

2. **Normalize Database**:
   - Ubah `GAGAL` → `DITOLAK`
   - Ubah `HIRED` → `LULUS`

### Logika Akhir
```
Status Aplikasi:
├─ LULUS = Sudah lulus di tahap akhir (hiring) → dinyatakan fully passed ✓
├─ PROSES = Masih dalam tahapan recruitment
├─ DITOLAK = Ditolak di tahap manapun ✗
└─ CANCEL = Dibatalkan
```

### Hasil
- Statistik "Lulus" sekarang menampilkan jumlah kandidat yang sudah fully passed (lulus tahap hiring)
- Dashboard dan Candidates Index sekarang menampilkan angka yang akurat
