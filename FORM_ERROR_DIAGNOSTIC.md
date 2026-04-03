# QUICK DIAGNOSTIC: Form Error Troubleshooting

## 🔍 Langkah-Langkah Diagnosa Error Form

### Step 1: Buka Developer Console (F12)
```
Klik F12 → Tab "Console" 
```

**Errors yang BOLEH DIABAIKAN**:
- ❌ "Unchecked (in promise) i: Failed to connect to MetaMask" - Ini MetaMask extension, tidak affect form
- ❌ "Content Security Policy...tailwindcss" - Styling warning, tidak affect form submission
- ❌ "Loading the stylesheet..." - CSS loading warning, tidak affect form logic

**Errors yang PERLU DIPERBAIKI**:
- 🟡 Validation errors dari server (akan ditampilkan di form langsung)
- 🔴 Network/connection errors (jika backend tidak running)

---

### Step 2: Cek Form Fields - Gunakan Checklist Ini

Sebelum submit, pastikan:**Semua Filled dan Valid**

| Field | Input Anda | Validasi | Status |
|-------|-----------|----------|--------|
| NIK Suami | ? | Harus 16 digit | ☐ |
| Nama Suami | ? | Tidak kosong | ☐ |
| Alamat Suami | ? | Tidak kosong | ☐ |
| RT/RW Suami | ? | Format 000/000 | ☐ |
| Kelurahan Suami | ? | Tidak kosong | ☐ |
| Kecamatan Suami | ? | Tidak kosong | ☐ |
| NIK Istri | ? | Harus 16 digit | ☐ |
| Nama Istri | ? | Tidak kosong | ☐ |
| Alamat Istri | ? | Tidak kosong | ☐ |
| RT/RW Istri | ? | Format 000/000 | ☐ |
| Kelurahan Istri | ? | Tidak kosong | ☐ |
| Kecamatan Istri | ? | Tidak kosong | ☐ |
| Nomor WA | ? | 9-15 digit angka | ☐ |
| Institusi | ? | Pilih dari dropdown | ☐ |
| KTP Suami | ? | JPG/PNG/PDF, <5MB | ☐ |
| KTP Istri | ? | JPG/PNG/PDF, <5MB | ☐ |
| Agreement | ? | Checkbox DICHECK | ☐ |

---

### Step 3: Format Nomor WhatsApp - PALING SERING ERROR

❌ **Format yang SALAH**:
```
+62 08282281394081    ← Ada +62 di depan
(082) 828-2813-9408   ← Ada tanda kurung & dash
082 8282 81394        ← Ada spasi
+62-82-82-28-13940    ← Ada +62 dan dash
```

✅ **Format yang BENAR** (semua ini accept):
```
082828213940          ← Standar dengan 0 di depan
812828213940          ← Tanpa 0 di depan
828282134             ← Hanya angka, tidak ada symbol
```

**UPDATE**: Form sekarang auto-clean nomor! Jadi bisa ketik apapun, akan otomatis dibersihkan.

---

### Step 4: Cek NIK Suami dan NIK Istri BERBEDA

❌ **Jangan**:
```
NIK Suami: 3571011110930001
NIK Istri: 3571011110930001    ← SAMA! Akan error
```

✅ **Benar**:
```
NIK Suami: 3571011110930001
NIK Istri: 3500945912850001    ← BERBEDA ✅
```

---

### Step 5: Cek Rate Limit (NIK tidak sedang frozen)

Jika melihat error:
```
"NIK ini telah mencapai batas maksimal 3 pengajuan dalam 15 hari..."
```

**Artinya**: NIK ini sudah submit 3 kali dalam 15 hari terakhir. Harus tunggu 15 hari.

---

### Step 6: Cek File Upload

Error:
```
"Format dokumen harus JPG, PNG, atau PDF."
```

**Solusi**: Upload hanya JPG, PNG, atau PDF

Error:
```
"Ukuran dokumen maksimal 5 MB."
```

**Solusi**: File terlalu besar. Compress file atau upload versi yang lebih kecil.

---

### Step 7: Cek Agreement Checkbox

Error:
```
"Anda harus menyetujui pernyataan kebenaran data."
```

**Solusi**: Centang checkbox "Saya menyatakan bahwa..." sebelum submit

---

## 🧪 Test Script: Simulasi Form Submission

### Via Browser Console (F12 → Console Tab):

```javascript
// 1. Cek apakah form element ada
console.log("Form found:", document.getElementById('submissionForm') ? 'YES' : 'NO');

// 2. Cek semua input fields
const form = document.getElementById('submissionForm');
const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
console.log("Required fields count:", inputs.length);

// 3. Log input values
inputs.forEach((input, index) => {
  console.log(`${index + 1}. ${input.name}: "${input.value || '[EMPTY]'}"`);
});

// 4. Cek agreement checkbox
const agreement = form.querySelector('input[name="agreement"]');
console.log("Agreement checked:", agreement.checked ? 'YES' : 'NO');

// 5. Cek file inputs
const files = form.querySelectorAll('input[type="file"][required]');
files.forEach(file => {
  console.log(`${file.name}: ${file.files.length > 0 ? file.files[0].name : '[NO FILE]'}`);
});
```

---

## 📞 Error Messages & Solutions

### Error: "NIK Suami wajib diisi"
**Cause**: Input kosong
**Fix**: Isi dengan 16 digit NIK

### Error: "NIK Suami harus tepat 16 digit angka"
**Cause**: NIK bukan 16 digit angka
**Fix**: Cek ulang, harus persis 16 digit

### Error: "NIK Suami tidak boleh sama dengan NIK Istri"
**Cause**: Kedua NIK sama
**Fix**: Pastikan nik_suami ≠ nik_istri

### Error: "Nomor WhatsApp tidak valid"
**Cause**: Format nomor salah (terlalu pendek/panjang/ada symbol)
**Fix**: Gunakan format 9-15 digit angka, tanpa +62 atau symbol
- Correct examples: 082828213940, 812828213940

### Error: "Dokumen KTP Suami wajib diunggah"
**Cause**: File KTP Suami belum dipilih
**Fix**: Klik "Klik untuk pilih file" di bagian KTP Suami

### Error: "Format dokumen harus JPG, PNG, atau PDF"
**Cause**: File format tidak support
**Fix**: Upload hanya JPG, PNG, atau PDF file

### Error: "Ukuran dokumen maksimal 5 MB"
**Cause**: File terlalu besar
**Fix**: Compress atau gunakan file yang lebih kecil

### Error: "Pilih Pengadilan Agama/Institusi"
**Cause**: Institusi tidak dipilih
**Fix**: Pilih institusi dari dropdown

### Error: "Anda harus menyetujui pernyataan kebenaran data"
**Cause**: Agreement checkbox tidak dicek
**Fix**: Check checkbox sebelum submit

### Error: "NIK ini tidak dapat mengajukan permohonan baru..."
**Cause**: NIK sedang dalam proses atau sudah frozen
**Fix**: Tunggu proses selesai atau hubungi kantor terkait

### Error: "NIK ini telah mencapai batas maksimal 3 pengajuan..."
**Cause**: Rate limit tercapai (3x per 15 hari)
**Fix**: Tunggu hingga 15 hari

---

## 🔧 Backend Status Check

### Cara cek backend running OK:

1. **Open Network Tab (F12 → Network)**
2. **Klik Submit button**
3. **Lihat XHR/Fetch requests**
   - ✅ POST /pengajuan → Status 200/302 = OK
   - ❌ POST /pengajuan → Status 500 = Server error

Jika status 500:
```
Check server logs:
cd d:\ProyekTA
php artisan log:tail
```

---

## 🎯 SUMMARY: Apa yang Sudah Diperbaiki

✅ **Phone number cleaning** - Auto-clean +62, spaces, dashes
✅ **Better error messages** - Lebih jelas & deskriptif  
✅ **Form validation** - Comprehensive checks
✅ **File handling** - Proper validation
✅ **User experience** - Clear instructions

---

## 💡 Tips Akhir

1. **Jika refresh halaman, form data akan hilang** - Copy data dulu ke notepad jika perlu
2. **Gunakan form autofill browser** - Simpan data untuk pengajuan berikutnya
3. **Nomor WA harus AKTIF** - Token akan dikirim via WhatsApp
4. **Jangan submit 2x** - Tunggu halaman selesai loading
5. **Save token tracking** - Copy token dari halaman sukses untuk tracking

---

## 🆘 Jika Masih Error

1. Refresh halaman (F5)
2. Clear browser cache (Ctrl+Shift+Delete)
3. Buka di browser berbeda atau mode Private/Incognito
4. Check semua required fields sekali lagi
5. Contact support dengan screenshot error message
