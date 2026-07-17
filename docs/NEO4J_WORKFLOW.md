# Neo4j Workflow Visualizations - SiPadu System

## Visualisasi Alur Data Lengkap

### Query 1: Semua User dan Institution dengan Relasi

```cypher
// Menampilkan semua user, institution, dan relasi WORKS_AT
MATCH (u:User)-[r:WORKS_AT]->(i:Institution)
RETURN u, r, i
ORDER BY i.type, u.name
```

**Jika diklik satu User (misal PA Assistant):**
```cypher
// Ganti 'pa_assistant@example.com' dengan email user yang diklik
MATCH (u:User {email: 'pa_assistant@example.com'})-[:WORKS_AT]->(i:Institution)
OPTIONAL MATCH (u)-[r]->(c:Case)
RETURN u, r, c, i
ORDER BY c.created_at DESC
```

---

### Query 2: PA Institution dan Staff-nya

```cypher
// PA Institution dengan PA Assistant, PA Management, PA Staff
MATCH (u:User)-[:WORKS_AT]->(i:Institution {name: 'PENGADILAN AGAMA'})
RETURN u, i
ORDER BY u.name
```

**Klik PA Assistant → Tampilkan Draft & Submitted:**
```cypher
// Jika PA Assistant diklik, tampilkan:
// - DRAFT cases (kasus draft)
// - SUBMITTED cases (kasus yang sudah dikirim)
// - REJECTED cases (kasus yang ditolak)
MATCH (pa:User {email: 'pa_assistant@example.com'})-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[r]->(c:Case)
RETURN pa, r, c, i
ORDER BY c.created_at DESC
```

---

### Query 3: Disdukcapil Institution dan Staff

```cypher
// Disdukcapil Institution dengan Disdukcapil Staff
MATCH (u:User)-[:WORKS_AT]->(i:Institution {name: 'DISDUKCAPIL'})
RETURN u, i
ORDER BY u.name
```

**Klik Disdukcapil Staff → Tampilkan Validasi:**
```cypher
// Jika Disdukcapil Staff diklik
MATCH (ds:User {email: 'disdukcapil@example.com'})-[:WORKS_AT]->(i:Institution)
MATCH (c:Case)
WHERE c.assigned_disdukcapil_user_id IS NOT NULL
  OR c.status IN ['DISDUKCAPIL_VALIDATION', 'COMPLETED']
RETURN ds, c, i
ORDER BY c.updated_at DESC
```

---

### Query 4: Super Admin (bekerja dengan PA & Disdukcapil)

```cypher
// Super Admin yang terhubung ke kedua institution
MATCH (sa:User)-[:WORKS_AT]->(i:Institution)
WHERE sa.hasRole('super_admin') = true
RETURN sa, collect(i) as institutions
```

---

## Alur PA Assistant → PA Management

### Draft Cases (PA Assistant)
```cypher
// Kasus DRAFT oleh PA Assistant
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:DRAFT]->(c:Case)
WHERE c.status = 'DRAFT'
RETURN pa, c, i
ORDER BY c.created_at DESC
```

**Jika filter DRAFT diklik:**
```
- Tampilkan hanya kasus dengan status: DRAFT
- Label: "Kasus Draft - Belum Dikirim"
```

### Submitted Cases (PA Assistant kirim ke PA Management)
```cypher
// Kasus SUBMITTED oleh PA Assistant
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:SUBMITTED]->(c:Case)
WHERE c.status IN ['SUBMITTED', 'OCR_PROCESSED', 'PA_REVIEW']
RETURN pa, c, i
ORDER BY c.submitted_at DESC
```

**Jika filter SUBMITTED diklik:**
```
- Tampilkan: Submitted, OCR Processed, PA Review
- Label: "Kasus Terkirim - Menunggu Review"
```

### Rejected Cases (PA Assistant diperbaiki)
```cypher
// Kasus REJECTED - bisa diedit ulang
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:SUBMITTED|DRAFT]->(c:Case)
WHERE c.status = 'REJECTED'
RETURN pa, c, i
ORDER BY c.updated_at DESC
```

**Jika filter REJECTED diklik:**
```
- Tampilkan: REJECTED
- Label: "Kasus Ditolak - Perlu Perbaikan"
```

---

## Alur PA Management

### Pengajuan Public & PA Submission
```cypher
// Semua kasus yang terkait PA Management
MATCH (pm:User)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (pm)-[r]->(c:Case)
RETURN pm, r, c, i
ORDER BY c.created_at DESC
```

**Filter options:**
```
- Public Submission → c.source_type = 'public'
- PA Submission → c.source_type = 'internal' atau 'manual'
```

---

## Alur PA Management → Disdukcapil

### Kirim ke Disdukcapil (HAS_DOCUMENT)
```cypher
// Kasus yang dikirim ke Disdukcapil
MATCH (pm:User)-[:WORKS_AT]->(i1:Institution {type: 'PA'})
MATCH (ds:User)-[:WORKS_AT]->(i2:Institution {type: 'DISDUKCAPIL'})
MATCH (pm)-[:SUBMITTED]->(c:Case)
WHERE c.status IN ['DISDUKCAPIL_VALIDATION', 'COMPLETED']
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN pm, c, collect(d) as documents, ds, i1, i2
ORDER BY c.updated_at DESC
```

---

## Alur Disdukcapil Staff

### Validasi Cases
```cypher
// Kasus yang menunggu validasi Disdukcapil
MATCH (ds:User)-[:WORKS_AT]->(i:Institution {type: 'DISDUKCAPIL'})
MATCH (c:Case)
WHERE c.status = 'DISDUKCAPIL_VALIDATION'
  AND c.assigned_disdukcapil_user_id IS NOT NULL
RETURN ds, c, i
ORDER BY c.updated_at DESC
```

### Completed with BAST & Digital Docs
```cypher
// Kasus COMPLETED dengan BAST dan dokumen digital
MATCH (c:Case)
WHERE c.status = 'COMPLETED'
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN c, collect(d) as all_documents
ORDER BY c.updated_at DESC
```

---

## Alur PA Staff - Arsip

### Completed & Archived
```cypher
// Kasus COMPLETED dan ARCHIVED untuk PA Staff
MATCH (ps:User)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (c:Case)
WHERE c.status IN ['COMPLETED', 'ARCHIVED']
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN ps, c, collect(d) as documents, i
ORDER BY c.completed_at DESC
```

---

## Filter Status untuk Setiap User

### PA Assistant Filters:
```cypher
// DRAFT
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:DRAFT]->(c:Case)
WHERE c.status = 'DRAFT'
RETURN 'DRAFT' as filter_type, count(c) as jumlah

// SUBMITTED
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:SUBMITTED]->(c:Case)
WHERE c.status IN ['SUBMITTED', 'OCR_PROCESSED', 'PA_REVIEW']
RETURN 'SUBMITTED' as filter_type, count(c) as jumlah

// REJECTED
MATCH (pa:User)-[:WORKS_AT]->(i:Institution)
MATCH (pa)-[:SUBMITTED|DRAFT]->(c:Case)
WHERE c.status = 'REJECTED'
RETURN 'REJECTED' as filter_type, count(c) as jumlah
```

### PA Management Filters:
```cypher
// Menunggu Review
MATCH (pm:User)-[:WORKS_AT]->(i:Institution)
MATCH (pm)-[:SUBMITTED]->(c:Case)
WHERE c.status IN ['SUBMITTED', 'OCR_PROCESSED', 'PA_REVIEW']
RETURN 'MENUNGGU_REVIEW' as filter_type, count(c) as jumlah

// Dikirim ke Disdukcapil
MATCH (pm:User)-[:WORKS_AT]->(i:Institution)
MATCH (pm)-[:SUBMITTED]->(c:Case)
WHERE c.status IN ['DISDUKCAPIL_VALIDATION', 'COMPLETED']
RETURN 'DISDUKCAPIL' as filter_type, count(c) as jumlah
```

### Disdukcapil Staff Filters:
```cypher
// Menunggu Validasi
MATCH (ds:User)-[:WORKS_AT]->(i:Institution)
MATCH (c:Case)
WHERE c.status = 'DISDUKCAPIL_VALIDATION'
  AND c.assigned_disdukcapil_user_id IS NOT NULL
RETURN 'MENUNGGU_VALIDASI' as filter_type, count(c) as jumlah

// Selesai
MATCH (ds:User)-[:WORKS_AT]->(i:Institution)
MATCH (c:Case)
WHERE c.status = 'COMPLETED'
  AND c.assigned_disdukcapil_user_id IS NOT NULL
RETURN 'SELESAI' as filter_type, count(c) as jumlah
```

### PA Staff Filters:
```cypher
// Completed
MATCH (ps:User)-[:WORKS_AT]->(i:Institution)
MATCH (c:Case)
WHERE c.status = 'COMPLETED'
RETURN 'COMPLETED' as filter_type, count(c) as jumlah

// Archived
MATCH (ps:User)-[:WORKS_AT]->(i:Institution)
MATCH (c:Case)
WHERE c.status = 'ARCHIVED'
RETURN 'ARCHIVED' as filter_type, count(c) as jumlah
```

---

## Dashboard Summary Query

```cypher
// Ringkasan untuk semua status
MATCH (c:Case)
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN c.status as Status,
       count(DISTINCT c) as JumlahKasus,
       collect(DISTINCT d.document_type) as TipeDokumen
GROUP BY c.status
ORDER BY JumlahKasus DESC
```
