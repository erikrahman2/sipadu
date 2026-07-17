# Neo4j Query Guide - SiPadu System

## Relasi dalam Sistem

### 1. Struktur Dasar (Institutions & Users)

```cypher
// Tampilkan semua Institutions dan Users yang bekerja di dalamnya
MATCH (u:User)-[r:WORKS_AT]->(i:Institution)
RETURN u, r, i
ORDER BY i.type, u.name
```

```cypher
// Tampilkan hanya Institutions
MATCH (i:Institution)
RETURN i
ORDER BY i.type
```

### 2. PA Institution dengan Staff-nya

```cypher
// PA Institution dan semua staff (PA Assistant, PA Management, PA Staff)
MATCH (u:User)-[:WORKS_AT]->(i:Institution {type: 'PA'})
RETURN u, i
ORDER BY u.name
```

**Hasil yang ditampilkan:**
- PA Assistant(s) - membuat draft, submit kasus
- PA Management(s) - review & approve/reject
- PA Staff(s) - arsip kasus completed

### 3. Disdukcapil Institution dengan Staff-nya

```cypher
// Disdukcapil Institution dan staff-nya
MATCH (u:User)-[:WORKS_AT]->(i:Institution {type: 'DISDUKCAPIL'})
RETURN u, i
ORDER BY u.name
```

**Hasil yang ditampilkan:**
- Disdukcapil Staff(s) - validasi & upload BAST

### 4. Super Admin (bekerja di kedua institution)

```cypher
// Super Admin yang terhubung ke kedua institution
MATCH (sa:User)-[:WORKS_AT]->(i:Institution)
WHERE sa.name CONTAINS 'Admin' OR sa.email CONTAINS 'admin'
RETURN sa, i
ORDER BY i.type
```

### 5. Alur Kasus (Cases) dalam Sistem

```cypher
// Semua Cases dan statusnya
MATCH (c:Case)
RETURN c
ORDER BY c.created_at DESC
```

### 6. PA Assistant - Draft Cases

```cypher
// Kasus dengan status DRAFT yang dibuat oleh PA Assistant
MATCH (pa:User)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (c:Case)
WHERE c.status = 'DRAFT'
RETURN pa, c, i
ORDER BY c.created_at DESC
```

**Filter tambahan - hanya kasus PA Assistant tertentu:**
```cypher
// Ganti 'nama_user' dengan nama PA Assistant
MATCH (pa:User)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (pa)-[:SUBMITTED|DRAFT]->(c:Case)
WHERE pa.name = 'Nama PA Assistant'
RETURN pa, c, i
ORDER BY c.created_at DESC
```

### 7. PA Assistant - Submitted Cases

```cypher
// Semua kasus SUBMITTED dari PA Assistant
MATCH (pa:User)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (c:Case)
WHERE c.status = 'SUBMITTED'
RETURN pa, c, i
ORDER BY c.created_at DESC
```

### 8. PA Management - Pengajuan Public & PA

```cypher
// Semua kasus dari PA Management (Public Submission + PA Submission)
MATCH (pm:User)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (pm)-[r]->(c:Case)
RETURN pm, r, c, i
ORDER BY c.created_at DESC
```

### 9. REJECTED Cases (Ditolak)

```cypher
// Kasus yang ditolak (REJECTED) - bisa diedit ulang oleh PA Assistant
MATCH (c:Case)
WHERE c.status = 'REJECTED'
RETURN c
ORDER BY c.updated_at DESC
```

### 10. PA Management -> Disdukcapil (via SUBMITTED)

```cypher
// Alur: PA Management review -> kirim ke Disdukcapil (SUBMITTED)
MATCH (pm:User)-[:WORKS_AT]->(i1:Institution {type: 'PA'})
MATCH (ds:User)-[:WORKS_AT]->(i2:Institution {type: 'DISDUKCAPIL'})
MATCH (pm)-[:SUBMITTED]->(c:Case)
MATCH (c)-[:HAS]->(d:Document)
RETURN pm, c, d, ds, i1, i2
ORDER BY c.created_at DESC
```

### 11. Disdukcapil - Validasi (DISDUKCAPIL_VALIDATION)

```cypher
// Kasus yang sedang divalidasi Disdukcapil
MATCH (ds:User)-[:WORKS_AT]->(i:Institution {type: 'DISDUKCAPIL'})
MATCH (c:Case)
WHERE c.status = 'DISDUKCAPIL_VALIDATION'
RETURN ds, c, i
ORDER BY c.updated_at DESC
```

### 12. Disdukcapil - Completed (dengan BAST & Digital Docs)

```cypher
// Kasus yang sudah completed oleh Disdukcapil
MATCH (ds:User)-[:WORKS_AT]->(i:Institution {type: 'DISDUKCAPIL'})
MATCH (c:Case)
WHERE c.status = 'COMPLETED'
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN ds, c, collect(d) as documents, i
ORDER BY c.updated_at DESC
```

### 13. PA Staff - Arsip (COMPLETED & ARCHIVED)

```cypher
// Kasus yang diarsipkan untuk PA Staff
MATCH (ps:User)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (c:Case)
WHERE c.status IN ['COMPLETED', 'ARCHIVED']
RETURN ps, c, i
ORDER BY c.completed_at DESC
```

### 14. Dokumen dalam Cases

```cypher
// Semua dokumen dalam sistem
MATCH (c:Case)-[:HAS_DOCUMENT]->(d:Document)
RETURN c, d
ORDER BY c.case_number, d.document_type
```

### 15. Dokumen BAST

```cypher
// Semua dokumen BAST (Berita Acara Serah Terima)
MATCH (c:Case)-[:HAS_DOCUMENT]->(d:Document)
WHERE d.document_type = 'BAST'
RETURN c, d
ORDER BY c.case_number
```

---

## Query Berdasarkan Role/User Tertentu

### Untuk satu PA Assistant tertentu:
```cypher
MATCH (pa:User {name: 'Nama PA Assistant'})
MATCH (pa)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (pa)-[r]->(c:Case)
RETURN pa, r, c, i
ORDER BY c.created_at DESC
```

### Untuk satu PA Management tertentu:
```cypher
MATCH (pm:User {name: 'Nama PA Management'})
MATCH (pm)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (pm)-[r]->(c:Case)
RETURN pm, r, c, i
ORDER BY c.created_at DESC
```

### Untuk satu Disdukcapil Staff tertentu:
```cypher
MATCH (ds:User {name: 'Nama Disdukcapil Staff'})
MATCH (ds)-[:WORKS_AT]->(i:Institution {type: 'DISDUKCAPIL'})
MATCH (c:Case)
WHERE c.assigned_disdukcapil_user_id IS NOT NULL
RETURN ds, c, i
ORDER BY c.updated_at DESC
```

### Untuk satu PA Staff tertentu:
```cypher
MATCH (ps:User {name: 'Nama PA Staff'})
MATCH (ps)-[:WORKS_AT]->(i:Institution {type: 'PA'})
MATCH (c:Case)
WHERE c.status IN ['COMPLETED', 'ARCHIVED']
RETURN ps, c, i
ORDER BY c.completed_at DESC
```

---

## Statistik Sistem

```cypher
// Jumlah Cases per Status
MATCH (c:Case)
RETURN c.status as Status, count(c) as Jumlah
ORDER BY Jumlah DESC
```

```cypher
// Jumlah Cases per Institution
MATCH (i:Institution)<-[:WORKS_AT]-(u:User)
MATCH (u)-[r]->(c:Case)
RETURN i.name as Institution, type(r) as Relasi, count(c) as Jumlah
ORDER BY Institution
```

```cypher
// Jumlah User per Role
MATCH (u:User)-[:WORKS_AT]->(i:Institution)
RETURN i.type as InstitutionType, count(u) as JumlahUser
ORDER BY InstitutionType
```

```cypher
// Dokumen per Tipe
MATCH (c:Case)-[:HAS_DOCUMENT]->(d:Document)
RETURN d.document_type as TipeDokumen, count(d) as Jumlah
ORDER BY Jumlah DESC
```

---

## Visual Style untuk Neo4j Desktop

Untuk memberikan warna berbeda pada setiap node:

**Cases (Kasus):**
- DRAFT: Orange
- SUBMITTED: Blue
- REJECTED: Red
- DISDUKCAPIL_VALIDATION: Purple
- COMPLETED: Green
- ARCHIVED: Gray

**Institutions:**
- PA: Blue
- DISDUKCAPIL: Teal
- Lainnya: Gray

**Users:**
- PA Assistant: Light Blue
- PA Management: Dark Blue
- PA Staff: Cyan
- Disdukcapil Staff: Teal
- Super Admin: Gold/Yellow

**Documents:**
- BAST: Red
- DIGITAL_COPY: Blue
- Lainnya: Gray

---

## Relasi Summary

| Dari | Relasi | Ke | Keterangan |
|------|--------|-----|-------------|
| User | WORKS_AT | Institution | User bekerja di institution |
| User | SUBMITTED | Case | User submit/membuat kasus |
| User | DRAFT | Case | User punya draft kasus |
| User | VERIFY_OPERATOR | Case | User ditugaskan verify |
| Institution | HAS | Case | Institution punya kasus |
| Case | HAS_DOCUMENT | Document | Case punya dokumen |
| Case | RELATED_TO | Case | Kasus terkait |

---

## Workflow Status Cases

```
DRAFT → SUBMITTED → OCR_PROCESSED → PA_REVIEW → DISDUKCAPIL_VALIDATION → COMPLETED → ARCHIVED
         ↘         ↘
         REJECTED ← (bisa kembali ke DRAFT untuk diperbaiki)
```
