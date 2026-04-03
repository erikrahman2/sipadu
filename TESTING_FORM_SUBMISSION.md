# 🧪 PANDUAN TESTING FORM SUBMISSION

## ⚡ QUICK TEST (5 MENIT)

### Langkah 1: Persiapan
```
1. Buka browser Chrome/Firefox
2. Pergi ke: http://localhost/pengajuan-publik
3. Tekan F12 untuk buka Developer Tools
4. Pilih tab: Console
```

### Langkah 2: Isi Form dengan Data Valid
```
Data Suami:
  - NIK: 3171012611950029 (16 digit, valid format)
  - Nama: Ahmad Riyanto
  - Alamat: Jl. Sudirman No 123
  - RT/RW: 01/02
  - Kelurahan: Cilandak
  - Kecamatan: Cilandak
  
Data Istri:
  - NIK: 3171012808900001 (16 digit, BERBEDA dengan Suami!)
  - Nama: Siti Nurhaliza
  - Alamat: Jl. Sudirman No 123
  - RT/RW: 01/02
  - Kelurahan: Cilandak
  - Kecamatan: Cilandak

Lainnya:
  - Nomor WhatsApp: 081234567890 (atau +6281234567890)
  - Institusi: (pilih dari dropdown)
  - Upload KTP: Pilih file gambar (JPG/PNG < 5 MB)
  - Agreement: CENTANG
```

### Langkah 3: Submit Form
```
1. Klik "Kirim Pengajuan"
2. Amati:
   - Button berubah jadi "Mengirim..." ✅ (typo sudah diperbaiki)
   - Console tidak ada error ✅
   - Loading spinner tampil
3. Tunggu 2-3 detik
```

### Langkah 4: Verifikasi Hasil
```
JIKA SUKSES ✅:
  - Redirect ke halaman success
  - Token ditampilkan
  - Pesan: "Pengajuan berhasil dikirim"
  - Data tersimpan di database
  
JIKA GAGAL ❌:
  - Error message muncul
  - Form tidak di-submit
  - Lihat step 5 untuk debugging
```

### Langkah 5: Baca Error Messages
Di Console (F12 → Console):
```javascript
// Contoh: NIK format error
"NIK Suami harus 16 digit"

// Contoh: Rate limit
"Sudah mencapai batas 3 pengajuan dalam 15 hari"

// Contoh: File size
"File tidak boleh lebih dari 5 MB"

// Contoh: No error (form OK)
// Console kosong = BAGUS!
```

---

## 🔍 DETAILED TESTING

### Test Case 1: Valid Submission ✅

**Setup**:
```
Semua field terisi dengan valid
NIK Suami ≠ NIK Istri
Phone: 081234567890
Files: KTP_Suami.jpg (500KB), KTP_Istri.png (400KB)
Agreement: Checked
```

**Expected Result**:
```
Status: 302 Redirect
Location: /pengajuan/success?token=xxx
Body: Halaman success dengan token
Database: Row baru di public_submissions
```

**Check in Developer Tools (F12 → Network)**:
```
1. Click "Kirim Pengajuan"
2. Lihat request: POST /pengajuan
3. Status harus: 302 (atau 200 + redirect)
4. Headers Response harus: Location: /pengajuan/success?token=...
5. Tidak ada error dalam Console
```

---

### Test Case 2: Invalid NIK ❌

**Setup**:
```
NIK Suami: 123 (hanya 3 digit, SALAH!)
NIK Istri: 3171012808900001
Phone: 081234567890
Files: OK
Agreement: Checked
```

**Expected Result**:
```
Form tidak submit
Error message: "NIK Suami harus 16 digit"
Console: Tidak ada fetch error
Data tidak tersimpan
```

**Debugging**:
- [ ] Form memiliki required attribute pada NIK fields
- [ ] Validation error ditampilkan di atas form
- [ ] Submit button tetap enabled (siap retry)

---

### Test Case 3: Duplicate NIK ❌

**Setup**:
```
NIK Suami: 3171012611950029
NIK Istri: 3171012611950029 (SAMA dengan Suami, SALAH!)
Phone: 081234567890
Files: OK
Agreement: Checked
```

**Expected Result**:
```
Form submit ke backend (HTML5 validation pass)
Backend validation fail: "NIK Suami dan Istri harus berbeda"
Error message ditampilkan
Data tidak tersimpan
```

**Debugging**:
- [ ] Console: Lihat response dari POST /pengajuan
- [ ] Status: 422 (Validation Error)
- [ ] Response body: { errors: { ... } }

---

### Test Case 4: Invalid Phone Number ❌

**Setup**:
```
Semua data OK
Phone: "abc123def" (SALAH format!)
```

**Expected Result**:
```
Form tidak submit (HTML5 validation fail)
atau
Browser menampilkan: "Please match the requested format"
```

**Debugging**:
- [ ] Phone input memiliki pattern HTML5: `[0-9\-\+\s]{9,15}`
- [ ] JavaScript auto-clean: hanya angka, space, -, + yang diterima

---

### Test Case 5: File Size Limit ❌

**Setup**:
```
KTP file: 10 MB (TERLALU BESAR!)
```

**Expected Result**:
```
Form validation fail
Error: "File tidak boleh lebih dari 5 MB"
File tidak terupload
```

**Debugging**:
- [ ] File input memiliki accept: ".jpg,.jpeg,.png,.pdf"
- [ ] Max file size: 5 MB (5242880 bytes)
- [ ] Check kapan validasi terjadi: client-side atau server-side

---

### Test Case 6: Missing Agreement ❌

**Setup**:
```
Semua data OK
Agreement checkbox: TIDAK DICENTANG (SALAH!)
```

**Expected Result**:
```
Form tidak submit
Browser: "Please check this box if you want to proceed"
```

**Debugging**:
- [ ] Agreement input memiliki required attribute
- [ ] Checkbox value: 1 (when checked)

---

### Test Case 7: Rate Limiting ❌

**Setup**:
```
Submit form 3 kali dalam 15 hari dengan NIK Suami yang SAMA
Submission ke-4: Coba submit lagi
```

**Expected Result**:
```
Submission 1-3: Sukses ✅
Submission 4: GAGAL ❌
Error: "Sudah mencapai batas 3 pengajuan dalam 15 hari"
```

**Debugging**:
- [ ] Database: Lihat created_at timestamp di public_submissions table
- [ ] Query: SELECT COUNT(*) FROM public_submissions WHERE nik_suami = 'xxx' AND created_at >= DATE_SUB(NOW(), INTERVAL 15 DAY);
- [ ] Count harus ≤ 3 untuk sukses

---

## 🛠️ TROUBLESHOOTING

### Console Error: "TypeError: Cannot read properties of null"

**Penyebab**:
```javascript
document.getElementById('submissionForm') === null
// Form element tidak ada atau ID salah
```

**Solusi**:
1. Buka tab Elements (F12 → Elements)
2. Cari: `<form id="submissionForm">`
3. Jika tidak ada → Halaman bladenya salah atau tidak loading
4. Refresh halaman: Ctrl+Shift+R (hard refresh)

---

### Console Error: "Failed to fetch"

**Penyebab**:
```
AJAX request ke server gagal
Bisa karena:
1. Server tidak running
2. Route tidak terdaftar
3. CORS issue
```

**Solusi**:
1. Cek server: `php artisan serve` running?
2. Cek route: `php artisan route:list | grep pengajuan`
3. Cek CORS config di `config/cors.php`
4. Network tab: Lihat full URL dan method

---

### Form Tidak Submit Sama Sekali

**Debugging Checklist**:
```
[ ] F12 → Console: Ada error?
[ ] F12 → Network: Ada request POST /pengajuan?
[ ] Developer Tools Elements: Form ID = "submissionForm"?
[ ] Button onclick: Tidak ada JS error?
[ ] Accept checkbox: Sudah dicentang?
[ ] Required fields: Semua terisi?
```

**Step-by-Step**:
```javascript
// Di Console, paste:
console.log('Form:', document.getElementById('submissionForm'));
console.log('Button:', document.getElementById('submitBtn'));
console.log('Form valid?:', document.getElementById('submissionForm')?.checkValidity());

// Expected output:
// Form: <form id="submissionForm"> ... </form>
// Button: <button id="submitBtn"> ... </button>
// Form valid?: true
```

---

### Database Not Saving

**Debugging**:
```
1. Check if form submitted:
   - F12 → Network → POST /pengajuan status: 302?
   - Success page ditampilkan?
   
2. Check database:
   - Connect ke MySQL/SQLite
   - SELECT * FROM public_submissions;
   - Ada data baru?
   
3. Check logs:
   - php artisan log:tail
   - Ada error message?
   
4. Check migration:
   - Table public_submissions exists?
   - Columns: nik_suami, nik_istri, etc?
   - php artisan migrate:status
```

---

## 📊 VALIDATION RULES REFERENCE

```php
// Backend Validation (Laravel)
'nik_suami' => 'required|regex:/^\d{16}$/',
'nik_istri' => 'required|regex:/^\d{16}$/',
'nama_suami' => 'required|string|max:100',
'nama_istri' => 'required|string|max:100',
'phone_wa' => 'required|regex:/^(\+62|62|0)?[0-9]{9,15}$/',
'documents.*' => 'required|file|mimes:jpeg,png,pdf|max:5120',
'agreement' => 'required|accepted',
```

```javascript
// Frontend Validation (HTML5)
<input type="text" name="nik_suami" pattern="^\d{16}$" required>
<input type="tel" name="phone_wa" pattern="[0-9\-\+\s]{9,15}" required>
<input type="file" name="documents[]" accept=".jpg,.jpeg,.png,.pdf" required>
<input type="checkbox" name="agreement" value="1" required>
```

---

## ✅ FINAL CHECKLIST

Sebelum declare "FIXED":

- [ ] Form load tanpa error (Console kosong)
- [ ] Valid NIK format accepted
- [ ] Invalid NIK format rejected (error message)
- [ ] Duplicate NIK rejected (server-side error)
- [ ] Rate limit working (after 3 submissions)
- [ ] Phone number auto-cleaned
- [ ] Files upload OK (size/format check)
- [ ] Agreement required + working
- [ ] Form submit → redirect to success page
- [ ] Data saved to public_submissions table
- [ ] Token generated dan ditampilkan
- [ ] WhatsApp notification sent (if WA gateway running)
- [ ] Database has correct data
- [ ] No "Mengirun..." typo (should be "Mengirim...")
- [ ] No JavaScript errors in Console

---

## 📞 SUPPORT

Jika masih ada error setelah semua test di atas:

1. **Check Laravel logs**:
   ```bash
   php artisan log:tail
   ```

2. **Check database connection**:
   ```bash
   php artisan db
   MySQL [sipadu]> SELECT 1;
   ```

3. **Check routes**:
   ```bash
   php artisan route:list | grep -i pengajuan
   ```

4. **Rebuild everything**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   composer dump-autoload
   ```

---

**Semoga berhasil testing! 🚀**
