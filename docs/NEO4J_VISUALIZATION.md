# Neo4j Desktop Visual Guide - SiPadu System

## Gambaran Alur Data

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           SUPER ADMIN                                       │
│                    (Bekerja di PA & DISDUKCAPIL)                           │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
        ┌───────────────────────────┼───────────────────────────┐
        │                           │                           │
        ▼                           ▼                           │
┌───────────────────┐     ┌───────────────────┐                │
│   PA INSTITUTION  │     │ DISDUKCAPIL       │                │
│   (Pengadilan     │     │ INSTITUTION       │                │
│    Agama)         │     │ (Dinas Kependudukan│                │
└───────────────────┘     │ & Pencatatan Sipil)│                │
        │                 └───────────────────┘                │
        │                                                   │
        ├───────────────┬───────────────┬───────────────────┤
        │               │               │                   │
        ▼               ▼               ▼                   ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐     ┌─────────────┐
│ PA ASSISTANT│ │PA MANAGEMENT│ │  PA STAFF   │     │DISDUKCAPIL  │
│             │ │             │ │             │     │   STAFF     │
└─────────────┘ └─────────────┘ └─────────────┘     └─────────────┘
        │               │               │                   │
        │               │               │                   │
        ▼               ▼               │                   │
┌─────────────┐ ┌─────────────┐       │                   │
│   DRAFT    │ │ SUBMITTED    │       │                   │
│  (kasus    │ │ (kasus yang │       │                   │
│   draft)   │ │  disubmit)  │       │                   │
└─────────────┘ └─────────────┘       │                   │
        │               │               │                   │
        │               ▼               │                   │
        │        ┌─────────────┐         │                   │
        │        │  DOCUMENTS  │         │                   │
        │        │ (HAS_DOC)    │         │                   │
        │        └─────────────┘         │                   │
        │               │               │                   │
        │               └───────┬─────────┘                   │
        │                       │                             │
        │                       ▼                             │
        │        ┌──────────────────────────────┐            │
        │        │      VALIDASI DISDUKCAPIL    │            │
        │        │   (BAST + DOKUMEN DIGITAL)   │◄───────────┤
        │        └──────────────────────────────┘            │
        │                       │                             │
        │                       ▼                             │
        │        ┌──────────────────────────────┐            │
        │        │         COMPLETED             │            │
        │        │  (Selesai Validasi)          │            │
        │        └──────────────────────────────┘            │
        │                       │                             │
        │                       ▼                             │
        │        ┌──────────────────────────────┐            │
        │        │         ARCHIVED               │───────────┘
        │        │       (Diarsipkan)             │
        │        └──────────────────────────────┘
        │
        ▼
┌─────────────┐
│  REJECTED   │
│ (Ditolak,   │
│  perlu      │
│  perbaikan) │
└─────────────┘
        │
        ▼
   Kembali ke DRAFT
```

## Query untuk Neo4j Desktop

### 1. Query Utama: Tampilkan Semua Struktur

```cypher
// Tampilkan semua node dan relasi
MATCH (n) RETURN n
```

### 2. Institutions dan Users dengan WORKS_AT

```cypher
// Institution dengan semua staff
MATCH (u:User)-[r:WORKS_AT]->(i:Institution)
RETURN u, r, i
ORDER BY i.name, u.name
```

### 3. PA Institution (Pengadilan Agama)

```cypher
// PA Institution dan staff-nya
MATCH (u:User)-[:WORKS_AT]->(i:Institution)
WHERE i.name CONTAINS 'AGAMA' OR i.type = 'PA'
RETURN u, i
ORDER BY u.name
```

**Hasil:**
- PA Assistant: melihat DRAFT, SUBMITTED, REJECTED
- PA Management: melihat SUBMITTED, COMPLETED
- PA Staff: melihat ARCHIVED

### 4. Disdukcapil Institution

```cypher
// Disdukcapil Institution dan staff
MATCH (u:User)-[:WORKS_AT]->(i:Institution)
WHERE i.name CONTAINS 'DISDUKCAPIL' OR i.type = 'DISDUKCAPIL'
RETURN u, i
ORDER BY u.name
```

**Hasil:**
- Disdukcapil Staff: melihat DISDUKCAPIL_VALIDATION, COMPLETED

### 5. Super Admin (bekerja di kedua institution)

```cypher
// Super Admin yang terhubung ke PA dan Disdukcapil
MATCH (sa:User)-[:WORKS_AT]->(i:Institution)
WHERE sa.email CONTAINS 'admin' OR sa.name CONTAINS 'Admin'
RETURN sa, collect(i.name) as institutions
ORDER BY sa.name
```

---

## Query Berdasarkan Klik User

### Klik PA Assistant → Tampilkan Cases-nya

```cypher
// Ganti 'pa_assistant@example.com' dengan email user
MATCH (pa:User {email: 'pa_assistant@example.com'})-[:WORKS_AT]->(i:Institution)
OPTIONAL MATCH (pa)-[r]->(c:Case)
RETURN pa, i, r, c
ORDER BY c.created_at DESC
```

**Filter berdasarkan status:**
```cypher
// DRAFT cases
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:DRAFT]->(c:Case)
WHERE c.status = 'DRAFT'
RETURN pa, c, i
ORDER BY c.created_at DESC

// SUBMITTED cases
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:SUBMITTED]->(c:Case)
WHERE c.status IN ['SUBMITTED', 'OCR_PROCESSED', 'PA_REVIEW']
RETURN pa, c, i
ORDER BY c.submitted_at DESC

// REJECTED cases (perlu perbaikan)
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:DRAFT|SUBMITTED]->(c:Case)
WHERE c.status = 'REJECTED'
RETURN pa, c, i
ORDER BY c.updated_at DESC
```

### Klik PA Management → Tampilkan Pengajuan

```cypher
// Ganti dengan email PA Management
MATCH (pm:User {email: 'pa_management@example.com'})-[:WORKS_AT]->(i:Institution)
OPTIONAL MATCH (pm)-[r]->(c:Case)
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN pm, c, collect(d) as documents, i
ORDER BY c.created_at DESC
```

**Filter:**
```cypher
// Public Submission
MATCH (pm:User)-[:WORKS_AT]->(i:Institution)
MATCH (pm)-[:SUBMITTED]->(c:Case)
WHERE c.source_type = 'public'
RETURN pm, c, i
ORDER BY c.created_at DESC

// PA Submission (Manual)
MATCH (pm:User)-[:WORKS_AT]->(i:Institution)
MATCH (pm)-[:SUBMITTED]->(c:Case)
WHERE c.source_type IN ['internal', 'manual']
RETURN pm, c, i
ORDER BY c.created_at DESC
```

### Klik Disdukcapil Staff → Tampilkan Validasi

```cypher
// Ganti dengan email Disdukcapil Staff
MATCH (ds:User {email: 'disdukcapil@example.com'})-[:WORKS_AT]->(i:Institution)
MATCH (ds)-[:VERIFY_OPERATOR]->(c:Case)
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN ds, c, collect(d) as documents, i
ORDER BY c.updated_at DESC
```

**Filter:**
```cypher
// Menunggu Validasi
MATCH (ds:User)-[:WORKS_AT]->(i:Institution)
MATCH (ds)-[:VERIFY_OPERATOR]->(c:Case)
WHERE c.status = 'DISDUKCAPIL_VALIDATION'
RETURN ds, c, i
ORDER BY c.updated_at DESC

// Selesai (dengan BAST)
MATCH (ds:User)-[:WORKS_AT]->(i:Institution)
MATCH (ds)-[:VERIFY_OPERATOR]->(c:Case)
WHERE c.status = 'COMPLETED'
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document {document_type: 'BAST'})
RETURN ds, c, d, i
ORDER BY c.updated_at DESC
```

### Klik PA Staff → Tampilkan Arsip

```cypher
// Ganti dengan email PA Staff
MATCH (ps:User {email: 'pa_staff@example.com'})-[:WORKS_AT]->(i:Institution)
MATCH (c:Case)
WHERE c.status IN ['COMPLETED', 'ARCHIVED']
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN ps, c, collect(d) as documents, i
ORDER BY c.completed_at DESC
```

---

## Query untuk Relasi Spesifik

### Semua Cases dengan Relasi User

```cypher
// Semua cases dan user yang terkait
MATCH (u:User)-[r]->(c:Case)
RETURN u.name as User, type(r) as Relasi, c.case_number as Case, c.status as Status
ORDER BY User, c.created_at DESC
```

### Documents dalam Cases

```cypher
// Semua documents dan cases-nya
MATCH (c:Case)-[:HAS_DOCUMENT]->(d:Document)
RETURN c.case_number as Case, c.status as Status,
       d.document_type as TipeDokumen, d.original_name as NamaFile
ORDER BY Case, TipeDokumen
```

### BAST Documents

```cypher
// Semua BAST (Berita Acara Serah Terima)
MATCH (c:Case)-[:HAS_DOCUMENT]->(d:Document {document_type: 'BAST'})
RETURN c.case_number as Case, c.status as Status, d.original_name as BAST_File
ORDER BY c.updated_at DESC
```

---

## Statistik dengan Cypher

### Jumlah Cases per Status

```cypher
MATCH (c:Case)
RETURN c.status as Status,
       count(c) as Jumlah,
       collect(c.case_number)[..3] as ContohKasus
ORDER BY Jumlah DESC
```

### Jumlah Cases per User

```cypher
MATCH (u:User)-[r]->(c:Case)
RETURN u.name as User, type(r) as Aktivitas,
       count(c) as JumlahKasus
ORDER BY JumlahKasus DESC
```

### Document Count per Tipe

```cypher
MATCH (c:Case)-[:HAS_DOCUMENT]->(d:Document)
RETURN d.document_type as Tipe,
       count(d) as Jumlah,
       count(DISTINCT c) as JumlahKasus
ORDER BY Jumlah DESC
```

---

## Workflow Status dalam Graph

```
┌─────────┐    ┌───────────┐    ┌─────────────┐    ┌──────────────┐    ┌──────────────────┐    ┌───────────┐    ┌───────────┐
│  DRAFT │───▶│ SUBMITTED │───▶│OCR_PROCESSED│───▶│   PA_REVIEW  │───▶│DISDUKCAPIL_VAL  │───▶│ COMPLETED │───▶│ ARCHIVED │
└─────────┘    └───────────┘    └─────────────┘    └──────────────┘    └──────────────────┘    └───────────┘    └───────────┘
     ▲              │                 │                  │                     │                      │
     │              │                 │                  │                     │                      │
     │              │                 │                  │                     │                      │
     └──────────────┴─────────────────┴──────────────────┘                     │
              (REJECTED - perlu perbaikan)                                       │
                      │                                                          │
                      └──────────────────────────────────────────────────────────┘
```

---

## Relasi dalam Neo4j

| Dari Node | Relasi | Ke Node | Deskripsi |
|-----------|--------|---------|-----------|
| User | WORKS_AT | Institution | User bekerja di institution |
| User | DRAFT | Case | PA Assistant punya draft |
| User | SUBMITTED | Case | User submit kasus |
| User | VERIFY_OPERATOR | Case | Disdukcapil staff validasi |
| User | WORKS_ON | Case | PA Management/Staff works on case |
| User | RELATED_TO | Case | User terkait dengan case |
| Institution | HAS | Case | Institution punya kasus |
| Case | HAS_DOCUMENT | Document | Case punya dokumen |

---

## Tips untuk Neo4j Desktop

1. **Klik node User** → Tampilkan semua relasi
2. **Klik relasi** → Tampilkan detail transisi
3. **Use filter** → Filter berdasarkan type/role
4. **Zoom** → Mouse wheel untuk zoom
5. **Pan** → Drag untuk geser view
