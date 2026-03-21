# SiPadu - Dokumentasi Index

> **Sistem Pembaruan Dokumen Pasca Perceraian**  
> Laravel 11 · MySQL 8 · Neo4j 5 · Python OCR · ReBAC  
> Update: 11 Maret 2026

---

## 📚 Dokumentasi Utama

### 1. [PANDUAN.md](PANDUAN.md) 
**Panduan lengkap menjalankan & pengujian sistem**
- ✅ Setup Docker & Lokal
- ✅ Kredensial default
- ✅ API endpoints
- ✅ Testing PHPUnit
- ✅ Troubleshooting
- ✅ Neo4j configuration
- ✅ OCR system overview
- ✅ Public submission flow
- 📖 **PRIMARY DOCUMENTATION** - Start here!

### 2. [QUICK_START.md](QUICK_START.md)
**Quick reference untuk memulai development**
- ⚡ Fast setup (5 menit)
- ⚡ Key commands
- ⚡ Common tasks

### 3. [DASHBOARD_GUIDE.md](DASHBOARD_GUIDE.md)
**Panduan lengkap dashboard per role**
- 👤 Super Admin
- 👤 PA Management
- 👤 PA Assistant
- 👤 PA Staff
- 👤 Disdukcapil Staff

### 4. [PENGAJUAN_PUBLIK_GUIDE.md](PENGAJUAN_PUBLIK_GUIDE.md)
**Fitur pengajuan tanpa akun untuk warga**
- 📝 Form pengajuan publik
- 📱 WhatsApp notification
- 🔍 Status tracking (token PUB-)
- ⚙️ Rate limiting per NIK

---

## 🔐 ReBAC (Relationship-Based Access Control)

### 5. [SKEMA_VALIDASI_NIK.md](SKEMA_VALIDASI_NIK.md)
**Validasi dan policy enforcement NIK**
- ✅ NIK format validation
- ✅ Duplicate prevention
- ✅ Graph-based access control

---

## 🤖 OCR System

### 6. [OCR_DESIGN.md](OCR_DESIGN.md) ⭐
**Dokumentasi lengkap sistem OCR (70+ halaman)**
- 🏗️ Architecture & components
- 🔧 Tesseract configuration
- 🖼️ Preprocessing pipeline (OpenCV)
- 📄 Field extraction algorithms
- 📊 Confidence scoring
- 🔄 Queue & async processing
- ⚡ Performance optimization
- 🐛 Troubleshooting guide

### 7. [OCR_STATUS.md](OCR_STATUS.md)
**Status checklist & setup guide**
- ✅ Installation checklist
- ✅ Component status
- ✅ Testing procedures

### 8. [OCR_VALIDATION_DESIGN.md](OCR_VALIDATION_DESIGN.md) 🆕
**Sistem validasi otomatis OCR vs Input Manual (42 KB)**
- 🔄 Auto-comparison workflow
- 📊 Levenshtein similarity algorithm
- 🎯 Field-by-field comparison
- 🖥️ PA Management dashboard
- ✅ Approve/Reject/Request Correction
- 📝 Database schema (ocr_validations)
- 🔧 Service layer implementation
- 🎨 Blade view templates

### 9. [OCR_VALIDATION_QUICKSTART.md](OCR_VALIDATION_QUICKSTART.md) 🆕
**Quick implementation guide (1 jam setup)**
- ⚡ Step-by-step implementation
- ⚡ Code snippets ready to use
- ⚡ Testing scenarios
- ⚡ Troubleshooting tips

### 10. [OCR_VALIDATION_SUMMARY.md](OCR_VALIDATION_SUMMARY.md) 🆕
**Implementation summary & checklist**
- 📋 Technical architecture overview
- 📋 Implementation phases
- 📋 File structure
- 📋 Monitoring & debugging

---

## 🚀 Migration & Deployment

### 11. [MIGRASI_LARAGON.md](MIGRASI_LARAGON.md)
**Panduan migrasi dari Docker ke Laragon (Windows)**
- 🪟 Laragon setup
- 🪟 MySQL, Redis, Neo4j local
- 🪟 OCR service configuration
- 🪟 Performance comparison

---

## 📊 Project Structure

```
d:\ProyekTA\
│
├── 📘 PANDUAN.md                          ← Start here!
├── ⚡ QUICK_START.md                      ← Quick reference
├── 🖥️ DASHBOARD_GUIDE.md                 ← Role-based dashboard guide
├── 📝 PENGAJUAN_PUBLIK_GUIDE.md          ← Public submission feature
├── 🔐 SKEMA_VALIDASI_NIK.md              ← NIK validation & ReBAC
│
├── 🤖 OCR System Documentation
│   ├── OCR_DESIGN.md                     ← Full OCR documentation (70+ pages)
│   ├── OCR_STATUS.md                     ← Setup checklist
│   ├── 🆕 OCR_VALIDATION_DESIGN.md       ← Auto-validation system (42 KB)
│   ├── 🆕 OCR_VALIDATION_QUICKSTART.md   ← Quick setup (1 hour)
│   └── 🆕 OCR_VALIDATION_SUMMARY.md      ← Implementation checklist
│
├── 🚀 MIGRASI_LARAGON.md                 ← Docker to Laragon migration
│
├── app/                                   ← Laravel application
│   ├── Models/
│   ├── Services/
│   ├── Http/Controllers/
│   └── Jobs/
│
├── ocr-service/                           ← Python OCR microservice
│   ├── app.py
│   ├── requirements.txt
│   └── Dockerfile
│
├── docker-compose.yml                     ← Docker orchestration
├── .env                                   ← Environment configuration
└── composer.json                          ← PHP dependencies
```

---

## 🎯 Quick Navigation by Task

### Getting Started
- 🚀 **First time setup**: [PANDUAN.md § 1-2](PANDUAN.md#1-prasyarat)
- ⚡ **Quick start**: [QUICK_START.md](QUICK_START.md)
- 🐛 **Troubleshooting**: [PANDUAN.md § 9](PANDUAN.md#9-pemecahan-masalah)

### Development
- 📝 **API Testing**: [PANDUAN.md § 5](PANDUAN.md#5-cara-pengujian-api)
- 🧪 **Unit Testing**: [PANDUAN.md § 6](PANDUAN.md#6-cara-pengujian-unit--fitur-phpunit)
- 🔧 **OCR Testing**: [PANDUAN.md § 7](PANDUAN.md#7-pengujian-ocr-microservice)

### Features
- 📱 **Public Submission**: [PENGAJUAN_PUBLIK_GUIDE.md](PENGAJUAN_PUBLIK_GUIDE.md)
- 🖥️ **Dashboard Usage**: [DASHBOARD_GUIDE.md](DASHBOARD_GUIDE.md)
- 🤖 **OCR System**: [OCR_DESIGN.md](OCR_DESIGN.md)
- 🆕 **OCR Validation**: [OCR_VALIDATION_DESIGN.md](OCR_VALIDATION_DESIGN.md)

### Infrastructure
- 🐳 **Docker Setup**: [PANDUAN.md § 2](PANDUAN.md#2-cara-menjalankan-docker--direkomendasikan)
- 🪟 **Laragon Setup**: [MIGRASI_LARAGON.md](MIGRASI_LARAGON.md)
- 🔐 **Neo4j Config**: [PANDUAN.md § 11](PANDUAN.md#11-konfigurasi-neo4j-lengkap)

---

## 📊 Feature Status

| Feature | Status | Documentation |
|---------|--------|---------------|
| Authentication & Authorization | ✅ Complete | [PANDUAN.md](PANDUAN.md) |
| Case Management | ✅ Complete | [DASHBOARD_GUIDE.md](DASHBOARD_GUIDE.md) |
| Document Upload | ✅ Complete | [PANDUAN.md § 8](PANDUAN.md#8-daftar-endpoint-api) |
| Public Submission | ✅ Complete | [PENGAJUAN_PUBLIK_GUIDE.md](PENGAJUAN_PUBLIK_GUIDE.md) |
| WhatsApp Notification | ✅ Complete | [PENGAJUAN_PUBLIK_GUIDE.md § 5](PENGAJUAN_PUBLIK_GUIDE.md#13-5-konfigurasi-whatsapp-gateway) |
| OCR Processing | ✅ Complete | [OCR_DESIGN.md](OCR_DESIGN.md) |
| 🆕 **OCR Auto-Validation** | ✅ Designed | [OCR_VALIDATION_DESIGN.md](OCR_VALIDATION_DESIGN.md) |
| ReBAC (Neo4j) | ✅ Complete | [SKEMA_VALIDASI_NIK.md](SKEMA_VALIDASI_NIK.md) |
| Status Tracking | ✅ Complete | [PENGAJUAN_PUBLIK_GUIDE.md](PENGAJUAN_PUBLIK_GUIDE.md) |
| Audit Logging | ✅ Complete | [PANDUAN.md](PANDUAN.md) |

---

## 🆕 Latest Updates (11 Maret 2026)

### OCR Auto-Validation System
**NEW**: Sistem validasi otomatis yang membandingkan data input manual dengan hasil OCR

**Key Features:**
- ✅ Auto-trigger OCR saat upload dokumen KTP/KK
- ✅ Field-by-field comparison (NIK, nama, alamat, dll)
- ✅ Levenshtein similarity scoring (0-100%)
- ✅ PA Management dashboard untuk review
- ✅ Approve/Reject/Request Correction workflow
- ✅ Audit trail untuk semua validasi

**Documentation:**
- 📘 Full Design: [OCR_VALIDATION_DESIGN.md](OCR_VALIDATION_DESIGN.md) (42 KB)
- ⚡ Quick Start: [OCR_VALIDATION_QUICKSTART.md](OCR_VALIDATION_QUICKSTART.md) (8 KB)
- 📊 Summary: [OCR_VALIDATION_SUMMARY.md](OCR_VALIDATION_SUMMARY.md) (9 KB)

**Implementation Time:** 2-3 hours

---

## 📞 Support & Contribution

### File Issues
- 🐛 Bug reports
- 💡 Feature requests
- 📝 Documentation improvements

### Development Workflow
1. Read [PANDUAN.md](PANDUAN.md) first
2. Setup local environment ([QUICK_START.md](QUICK_START.md))
3. Run tests (`php artisan test`)
4. Check code style (`./vendor/bin/pint`)
5. Create pull request

---

## 🔗 External Resources

- **Laravel 11 Docs**: https://laravel.com/docs/11.x
- **Tesseract OCR**: https://github.com/tesseract-ocr/tesseract
- **Neo4j**: https://neo4j.com/docs/
- **Docker**: https://docs.docker.com/

---

**Last Updated**: 11 Maret 2026  
**Total Documentation**: 100+ halaman  
**Status**: ✅ Production Ready (OCR Validation in design phase)

