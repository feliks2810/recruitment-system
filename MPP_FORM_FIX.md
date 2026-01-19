# MPP Submission Form - Fix untuk Duplikasi Posisi

## Masalah
Ketika user membuat pengajuan MPP dan terjadi validation error, halaman kembali ke form create dengan banyak posisi yang muncul berulang-ulang (duplicate rows).

## Penyebab
Ada bug di JavaScript ketika menangani `old('positions')` dari form submission yang gagal validasi:

```javascript
// LAMA - BUG: setTimeout dijalankan untuk setiap oldPosition
oldPositions.forEach((oldPosition, index) => {
    addPosition();
    
    // MASALAH: setTimeout ini dijalankan multiple times sekaligus
    // Semua setTimeout menunjuk ke "lastRow" yang sama
    setTimeout(() => {
        const rows = document.querySelectorAll('.position-row');
        const lastRow = rows[rows.length - 1];  // Ini selalu sama untuk semua!
        // ...
    }, 50);
});
```

Akibatnya:
1. Jika ada 2 posisi lama, `addPosition()` dipanggil 2x (benar)
2. Tapi setTimeout untuk mengisi values juga dijalankan 2x
3. Semua setTimeout menunjuk ke row terakhir (lastRow)
4. Hasilnya data tercampur dan banyak row kosong muncul

## Solusi
Ubah logika untuk mengumpulkan semua `addPosition()` dulu, baru kemudian populate values dengan benar:

```javascript
// BARU - FIX: Buat semua rows dulu, baru populate dengan logic yg benar
let rowIndex = 0;
oldPositions.forEach((oldPosition) => {
    addPosition();
    rowIndex++;
});

// Wait for ALL DOM updates, then populate values to correct rows
setTimeout(() => {
    const rows = document.querySelectorAll('.position-row');
    rows.forEach((row, index) => {
        if (index < oldPositions.length) {
            const oldPosition = oldPositions[index];
            
            // Setiap row populate dengan data yang sesuai indexnya
            const vacancySelect = row.querySelector('[name="positions[][vacancy_id]"]');
            if (vacancySelect && oldPosition.vacancy_id) {
                vacancySelect.value = oldPosition.vacancy_id;
            }
            // ... lainnya
        }
    });
}, 100);
```

## Improvement Tambahan

### 1. Validasi Form Client-Side
Ditambahkan function `validateForm()` yang checks sebelum submit:
- ✅ Department sudah dipilih
- ✅ Ada minimal 1 posisi
- ✅ Semua posisi sudah diisi dengan data lengkap
- ✅ Jumlah kebutuhan valid (≥ 1)

Jika ada yang kurang, muncul alert yang jelas ke user.

### 2. Submit Button Improvement
Tombol submit sekarang:
- Memanggil `validateForm()` sebelum submit
- Menampilkan pesan error yang jelas jika ada masalah
- Disabled state untuk feedback visual

## File yang Diubah

- `resources/views/mpp-submissions/create.blade.php` - ✅ Fixed JavaScript logic

## Testing

1. **Test 1: Buat form normal**
   - Pilih departemen
   - Klik "+ Tambah Posisi"
   - Isi data posisi
   - Klik "Simpan & Kirim" → ✅ Seharusnya submit sukses

2. **Test 2: Test validasi client-side**
   - Klik "Simpan & Kirim" tanpa data → Alert: "Pilih departemen"
   - Pilih departemen, klik simpan → Alert: "Tambahkan posisi"
   - Tambah posisi tapi kosong, klik simpan → Alert: "Pilih posisi"

3. **Test 3: Validation error dari server**
   - Isi form tapi ada error dari server (misal: vacancy_id tidak valid)
   - Page refresh → Seharusnya hanya rows yang sesuai dengan jumlah `oldPositions`
   - Data terisi dengan benar ke setiap row (NO DUPLICATES)

## Keterangan Status

| Feature | Status |
|---------|--------|
| Create MPP | ✅ Fixed |
| Form tidak duplikasi | ✅ Fixed |
| Client-side validasi | ✅ Added |
| Database tables | ✅ Created |
| Submit form | ✅ Ready to test |

