# ⚡ QUICK REFERENCE - PUBLIC SUBMISSION FEATURE

> Ringkasan cepat untuk status, debugging, dan deployment

---

## 📊 STATUS KESELURUHAN

| Item | Status | Next Step |
|------|--------|-----------|
| Form Frontend | ✅ FIXED | Deploy |
| Backend Logic | ✅ OK | Deploy |
| Database Schema | ✅ OK | Migrate |
| OCR Service | ✅ Enhanced | Running |
| Testing | ✅ Complete | QA approval |
| Documentation | ✅ Complete | Training |
| **OVERALL** | **✅ READY** | **DEPLOY NOW** |

---

## 🔧 WHAT CHANGED

### 1. JavaScript Bug Fixed ✅
```javascript
❌ BEFORE: const nikField = document.getElementById('nik');  // NO SUCH ELEMENT
✅ AFTER:  Removed entire broken NIK quota check function
```

### 2. Form Handler Fixed ✅
```javascript
✅ Added event parameter (e)
✅ Added validity check: this.checkValidity()
✅ Fixed typo: "Mengirun" → "Mengirim"
```

### 3. Everything Else ✅
Backend, database, and OCR were already correct - no changes needed.

---

## 🧪 QUICK TEST

```bash
# 1. Refresh halaman
Ctrl+F5

# 2. Isi form dengan data valid:
   - NIK Suami: 3171012611950029 (16 digit)
   - NIK Istri: 3171012808900001 (BERBEDA!)
   - Phone: 081234567890
   - Upload KTP: file JPG/PNG/PDF < 5MB
   - Check agreement checkbox

# 3. Klik "Kirim Pengajuan"
   - Button jadi "Mengirim..." ✅
   - Console kosong (F12) ✅
   - Redirect ke success page ✅
   - Token ditampilkan ✅

# 4. SUCCESS! ✅
```

---

## 🐛 DEBUGGING QUICK GUIDE

### Jika form tidak submit:
```
F12 → Console
  • Lihat error merah
  • Screenshot & track error

F12 → Network
  • Mulai record (click circle)
  • Submit form
  • Lihat POST request ke /pengajuan
  • Check status code:
    - 302 = SUCCESS ✅
    - 422 = Validation error ❌
    - 500 = Server error ❌
```

### Jika ada error di console:
```
Error: "Cannot read properties of null"
  → Refresh page (Ctrl+F5)

Error: "POST /pengajuan 422"
  → Form data invalid
  → Check: NIK format, phone, file size, checkbox

Error: "POST /pengajuan 500"
  → Server error
  → Check Laravel logs: php artisan log:tail
```

### Jika file tidak terupload:
```
Cek:
  ✓ Format: JPG, PNG, PDF?
  ✓ Ukuran: < 5 MB?
  ✓ Both files selected (KTP Suami & Istri)?
  
Jika OK, coba:
  - Refresh page
  - Coba browser lain
  - Cek disk space storage/app/
```

---

## 🚀 DEPLOYMENT

### Quick Deployment
```bash
cd d:\ProyekTA

# 1. Pull latest code
git pull

# 2. Migrate database (if first time)
php artisan migrate

# 3. Clear caches (IMPORTANT!)
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 4. Start queue worker
php artisan queue:work --timeout=0 &

# 5. Test form
# Open: http://localhost/pengajuan-publik
```

### Verify Deployment
```bash
# 1. Routes registered?
php artisan route:list | grep pengajuan

# 2. Database ready?
php artisan migrate:status

# 3. Queue worker running?
php artisan queue:failed

# 4. Storage linked?
ls -la public/storage  # Should see symlink
```

### Rollback (if needed)
```bash
# Revert last migration
php artisan migrate:rollback --step=1

# Revert code
git revert HEAD

# Restart
php artisan cache:clear
php artisan queue:restart
```

---

## 📱 FORM FIELDS REFERENCE

### Data Suami
```
NIK: [16 digit required]
Nama: [string, max 100 chars]
Alamat: [string, max 255 chars]
RT/RW: [format: NN/NN]
Kelurahan: [string, max 100 chars]
Kecamatan: [string, max 100 chars]
```

### Data Istri
```
Same as Suami (but must be DIFFERENT data!)
Important: NIK Istri ≠ NIK Suami
```

### Lainnya
```
Phone WA: [9-15 digits, accepts +62, 0, spaces, dashes]
          [auto-cleaned to digits only]

Institusi: [dropdown, required]

Documents: [JPG/PNG/PDF, max 5 MB each, required]

Agreement: [checkbox, required]
```

---

## ✅ VALIDATION RULES

| Field | Validation | Error Message |
|-------|----------|----------------|
| nik_suami | regex `^\d{16}$` | "NIK Suami harus 16 digit" |
| nik_istri | regex `^\d{16}$` | "NIK Istri harus 16 digit" |
| nik_suami ≠ nik_istri | Custom | "NIK Suami dan Istri harus berbeda" |
| phone_wa | regex `^(\+62\|62\|0)?[0-9]{9,15}$` | "Format nomor tidak valid" |
| documents | mimes:jpeg,png,pdf | "Format file harus JPG/PNG/PDF" |
| documents | max:5120 KB | "File tidak boleh lebih dari 5 MB" |
| agreement | required | "Anda harus setuju dengan syarat" |
| Rate limit | 3 per 15 days | "Sudah mencapai batas 3 pengajuan" |

---

## 🔐 RATE LIMITING

```
Maximum: 3 submissions per 15 days

Example:
  Jan 5  → Submission 1 ✅
  Jan 6  → Submission 2 ✅
  Jan 7  → Submission 3 ✅
  Jan 8  → Submission 4 ❌ (Error: limit reached)
  
  Jan 20 → Submission 4 ✅ (15 days after submission 1)

Rule: Per NIK Suami, checked at submission time
```

---

## 📞 COMMON ISSUES & SOLUTIONS

| Issue | Cause | Solution |
|-------|-------|----------|
| Form not submitting | JavaScript error | Refresh (Ctrl+F5), check Console |
| NIK error | Not 16 digits | Verify NIK from KTP |
| Phone error | Invalid format | Use: 081234567890 or +6281234567890 |
| File too large | > 5 MB | Compress using tinypng.com |
| Rate limit error | 3+ in 15 days | Wait 15 days or use different NIK |
| No WhatsApp | Phone wrong | Check form field, verify active WA |
| Data not saved | Server error | Check logs: php artisan log:tail |

---

## 💾 DATABASE

### Tables
```
public_submissions
├─ id (PRIMARY)
├─ nik_suami (INDEX)
├─ nik_istri (INDEX)
├─ phone_wa
├─ institution_id (FK)
├─ token (UNIQUE)
├─ status (INDEX)
└─ timestamps

public_submission_documents
├─ id
├─ public_submission_id (FK)
├─ path
└─ type
```

### Useful Queries
```sql
-- Total submissions
SELECT COUNT(*) FROM public_submissions;

-- By status
SELECT status, COUNT(*) FROM public_submissions GROUP BY status;

-- Rate limit check
SELECT COUNT(*) FROM public_submissions 
WHERE nik_suami = '3171012611950029' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 15 DAY);

-- Recent submissions
SELECT * FROM public_submissions ORDER BY created_at DESC LIMIT 10;
```

---

## 🔗 FILE LOCATIONS

| File | Purpose |
|------|---------|
| resources/views/pengajuan/publik.blade.php | Form UI |
| app/Http/Controllers/Web/PublicSubmissionController.php | Form handler |
| app/Services/PublicSubmissionService.php | Business logic |
| app/Models/PublicSubmission.php | Database model |
| config/ocr.php | OCR settings |
| ocr-service/app.py | OCR microservice |
| database/migrations/\*_create_public_submissions_table.php | Schema |

---

## 📚 FULL DOCUMENTATION

| Document | Read When |
|----------|-----------|
| FIX_FORM_SUBMISSION_ERRORS.md | Want full overview of fixes |
| RINGKAS_PERUBAHAN_CODE.md | Want to see code changes |
| TESTING_FORM_SUBMISSION.md | Need test cases |
| DEPLOYMENT_STATUS.md | Need deployment checklist |
| USER_GUIDE_PENGAJUAN_PUBLIK.md | Supporting user questions |
| ENGINEERING_SUMMARY.md | Need deep technical details |

---

## ⚙️ CONFIGURATION

### .env
```bash
# Queue
QUEUE_CONNECTION=redis

# Database
DB_CONNECTION=mysql
DB_DATABASE=sipadu
DB_USERNAME=root
DB_PASSWORD=***

# OCR Service
OCR_SERVICE_URL=http://localhost:5000

# WhatsApp
WHATSAPP_GATEWAY=twilio
WHATSAPP_API_KEY=***
```

### config/ocr.php
```php
'profiles' => [
    'KTP_ISTRI' => [
        'contrast_boost' => 1.2,
        'bilateral_filter' => true,
        'extra_upscale' => 1.5,
    ],
]
```

---

## 🎯 CHECKLIST BEFORE GO-LIVE

- [ ] All tests passed
- [ ] Code deployed to production
- [ ] Database migrated
- [ ] Storage linked
- [ ] Queue worker running
- [ ] OCR service running
- [ ] WhatsApp gateway configured
- [ ] Logs configured and monitoring
- [ ] Alerts set up
- [ ] Team trained on feature
- [ ] User documentation ready
- [ ] Support team briefed

---

## 📞 SUPPORT CONTACTS

| Role | Contact | For |
|------|---------|-----|
| Developer | Team chat | Code issues, bugs |
| DevOps | Team chat | Deployment, infrastructure |
| QA | Team chat | Test results, bugs |
| Support | Email | User issues |

---

## 🚨 EMERGENCY

### If form is broken in production:
```bash
# 1. IMMEDIATELY disable form
# In PublicSubmissionController.php:
return abort(503, 'Form temporarily offline. Please try again later.');

# 2. Investigate
php artisan log:tail

# 3. Fix in dev, test thoroughly

# 4. Deploy fix

# 5. Re-enable form
```

### If database is corrupted:
```bash
# 1. Stop queue worker
php artisan queue:stop

# 2. Run migrations repair
php artisan migrate:refresh --seed

# 3. Check data
SELECT COUNT(*) FROM public_submissions;

# 4. Restart
php artisan queue:work &
```

---

## 📊 MONITORING

### Daily Checks
```bash
# Error rate
php artisan log:tail

# Queue status
php artisan queue:failed
php artisan queue:pending

# Database health
SHOW PROCESSLIST;
SHOW TABLE STATUS;

# Form submissions
SELECT COUNT(*) FROM public_submissions WHERE created_at >= CURDATE();
```

### Weekly Report
- Total submissions & success rate
- Error types and frequency
- OCR accuracy metrics
- Queue processing time
- Database performance

---

## 🎉 SUCCESS CRITERIA

✅ Feature deployment is successful when:
- Form loads without errors
- Valid submissions are accepted
- Invalid submissions show error messages
- Data is saved to database
- WhatsApp notifications are sent
- Tracking token works
- No JavaScript errors in console
- Response time < 2 seconds
- Queue jobs complete successfully

---

**Version**: 1.2 | **Date**: January 2025 | **Status**: ✅ READY FOR PRODUCTION

