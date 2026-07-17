# Neo4j SiPadu - Struktur Visualisasi yang Diinginkan

## Struktur yang Diinginkan

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              SUPER ADMIN                                                │
│                    Bekerja dengan PA & DISDUKCAPIL                                     │
└─────────────────────────────────────────────────────────────────────────────────────────┘
                                          │
              ┌───────────────────────────┼───────────────────────────┐
              │                           │                           │
              ▼                           ▼                           ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│      PA INSTITUTION      │     │   DISDUKCAPIL INSTITUTION│
│  (Pengadilan Agama)      │     │ (Dinas Kependudukan)      │
└─────────────────────────┘     └─────────────────────────┘
              │                           │
    ┌─────────┼─────────┐                 │
    │         │         │                 │
    ▼         ▼         ▼                 ▼
┌─────────┐ ┌─────────┐ ┌─────────┐ ┌───────────────┐
│PA ASSIST│ │PA MGMT  │ │PA STAFF │ │DISDUKCAPIL    │
│         │ │         │ │         │ │STAFF          │
└─────────┘ └─────────┘ └─────────┘ └───────────────┘
    │           │           │           │
    │           │           │           │
    ▼           ▼           │           │
┌─────────┐ ┌─────────┐     │           │
│  DRAFT  │ │ HAS    │     │           │
│(Kasus   │ │DOCUMENT│     │           │
│ Draft)  │ │        │     │           │
└─────────┘ └─────────┘     │           │
    │           │           │           │
    │     ┌─────┴─────┐     │           │
    │     │           │     │           │
    ▼     ▼           ▼     │           │
┌───────────────┐ ┌───────────────┐   │
│  SUBMITTED    │ │  PENGGAJIAN   │   │
│  (Dikirim ke  │ │  PUBLIC & PA  │   │
│  PA Management)│ │  (Pengajuan) │   │
└───────────────┘ └───────────────┘   │
    │                   │               │
    │                   └───────┬───────┘
    │                           │
    │                           ▼
    │           ┌─────────────────────────┐
    │           │  DISDUKCAPIL VALIDATION │
    │           │  (BAST + Dokumen Digital)│
    │           └─────────────────────────┘
    │                           │
    │                           ▼
    │           ┌─────────────────────────┐
    │           │       COMPLETED          │
    │           │   (Selesai Validasi)     │
    │           └─────────────────────────┘
    │                           │
    │                           ▼
    │           ┌─────────────────────────┐
    │           │        ARCHIVED          │
    │           │      (Diarsipkan)        │
    │           └─────────────────────────┘
    │
    ▼
┌─────────────────┐
│     REJECTED     │
│   (Ditolak,      │
│  Perlu Perbaikan)│
└─────────────────┘
```

## Query untuk Neo4j Desktop

### 1. STRUKTUR UTAMA - Institution + Users + Cases

```cypher
// Tampilkan struktur lengkap Institution -> User -> Case
MATCH (sa:User)-[:WORKS_AT]->(i1:Institution)
WHERE sa.name = 'Administrator' OR sa.hasRole = 'super_admin'

MATCH (pa_inst:Institution {type: 'PA'})
MATCH (disc_inst:Institution {type: 'DISDUKCAPIL'})

MATCH (pa_inst)<-[:WORKS_AT]-(pa_user:User)
MATCH (disc_inst)<-[:WORKS_AT]-(disc_user:User)

OPTIONAL MATCH (pa_user)-[r1]->(c:Case)
OPTIONAL MATCH (c)-[r2]->(d:Document)

RETURN sa as SuperAdmin, pa_inst, pa_user, disc_inst, disc_user, r1, c, r2, d
LIMIT 50
```

### 2. Klik Institution PA - Tampilkan Semua Staff dan Cases

```cypher
// Klik Institution PA -> Tampilkan semua user dan kasus mereka
MATCH (i:Institution {type: 'PA'})
MATCH (u:User)-[:WORKS_AT]->(i)
OPTIONAL MATCH (u)-[r]->(c:Case)
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN i.name as Institution,
       u.name as User,
       u.hasRole as Role,
       type(r) as Relationship,
       c.case_number as CaseNumber,
       c.status as Status,
       d.document_type as DocumentType
ORDER BY User, Status
```

### 3. Klik Institution Disdukcapil - Tampilkan Staff dan Cases

```cypher
// Klik Institution Disdukcapil
MATCH (i:Institution {type: 'DISDUKCAPIL'})
MATCH (u:User)-[:WORKS_AT]->(i)
OPTIONAL MATCH (c:Case)<-[r]-(u)
WHERE c.status IN ['DISDUKCAPIL_VALIDATION', 'COMPLETED']
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)
RETURN i.name as Institution,
       u.name as User,
       type(r) as Relationship,
       c.case_number as CaseNumber,
       c.status as Status,
       collect(DISTINCT d.document_type) as Documents
ORDER BY Status
```

### 4. Klik PA ASSISTANT - Tampilkan Draft, Rejected, Submitted

```cypher
// Klik PA Assistant -> Tampilkan Draft & Submitted cases
MATCH (pa:User {name: 'PA Assistant'})-[:WORKS_AT]->(i:Institution {type: 'PA'})
OPTIONAL MATCH (pa)-[r]->(c:Case)

// Kategorikan berdasarkan status
RETURN pa.name as User,
       type(r) as Relationship,
       CASE
         WHEN c.status = 'DRAFT' THEN '📝 DRAFT'
         WHEN c.status = 'REJECTED' THEN '❌ DITOLAK'
         WHEN c.status IN ['SUBMITTED', 'OCR_PROCESSED', 'PA_REVIEW'] THEN '📤 SUBMITTED'
         ELSE c.status
       END as Kategori,
       c.case_number as NomorKasus,
       c.status as Status,
       c.updated_at as TerakhirDiperbarui
ORDER BY Kategori, c.updated_at DESC
```

### 5. Klik PA MANAGEMENT - Tampilkan Pengajuan Public & PA

```cypher
// Klik PA Management -> Tampilkan semua pengajuan
MATCH (pm:User {name: 'PA Management'})-[:WORKS_AT]->(i:Institution {type: 'PA'})
OPTIONAL MATCH (pm)-[r]->(c:Case)
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)

RETURN pm.name as User,
       type(r) as Relationship,
       CASE
         WHEN c.source_type = 'public' THEN '🌐 PENGGAJIAN PUBLIC'
         WHEN c.source_type IN ['internal', 'manual'] THEN '🏛️ PENGGAJIAN PA'
         WHEN c.status = 'DISDUKCAPIL_VALIDATION' THEN '📋 MENUNGGU DISDUKCAPIL'
         WHEN c.status = 'COMPLETED' THEN '✅ SELESAI'
         ELSE c.status
       END as Kategori,
       c.case_number as NomorKasus,
       c.status as Status,
       collect(DISTINCT d.document_type) as Dokumen
ORDER BY Kategori, c.updated_at DESC
```

### 6. Klik DISDUKCAPIL STAFF - Tampilkan Validasi & BAST

```cypher
// Klik Disdukcapil Staff -> Tampilkan kasus untuk divalidasi
MATCH (ds:User {name: 'Disdukcapil Staff'})-[:WORKS_AT]->(i:Institution {type: 'DISDUKCAPIL'})
OPTIONAL MATCH (ds)-[r:VERIFY_OPERATOR]->(c:Case)
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)

RETURN ds.name as User,
       type(r) as Relationship,
       CASE
         WHEN c.status = 'DISDUKCAPIL_VALIDATION' THEN '⏳ MENUNGGU VALIDASI'
         WHEN c.status = 'COMPLETED' THEN '✅ VALIDASI SELESAI'
         ELSE c.status
       END as Kategori,
       c.case_number as NomorKasus,
       c.status as Status,
       collect(CASE WHEN d.document_type = 'BAST' THEN '📄 BAST' ELSE d.document_type END) as Dokumen,
       c.updated_at as TanggalUpdate
ORDER BY Kategori, c.updated_at DESC
```

### 7. Klik PA STAFF - Tampilkan Arsip

```cypher
// Klik PA Staff -> Tampilkan archived cases
MATCH (ps:User {name: 'PA Staff'})-[:WORKS_AT]->(i:Institution {type: 'PA'})
OPTIONAL MATCH (c:Case)
WHERE c.status IN ['COMPLETED', 'ARCHIVED']
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)

RETURN ps.name as User,
       '📁 ARSIP' as Kategori,
       c.case_number as NomorKasus,
       c.status as Status,
       collect(DISTINCT d.document_type) as Dokumen,
       c.updated_at as TanggalArsip
ORDER BY c.updated_at DESC
```

### 8. Klik SUPER ADMIN - Tampilkan Semua

```cypher
// Klik Super Admin -> Tampilkan akses ke semua
MATCH (sa:User)-[:WORKS_AT]->(i:Institution)
WHERE sa.hasRole = 'super_admin' OR sa.name = 'Administrator'

OPTIONAL MATCH (pa_inst:Institution {type: 'PA'})<-[:WORKS_AT]-(pa_user:User)
OPTIONAL MATCH (disc_inst:Institution {type: 'DISDUKCAPIL'})<-[:WORKS_AT]-(disc_user:User)

OPTIONAL MATCH (pa_user)-[r1]->(c1:Case)
OPTIONAL MATCH (disc_user)-[r2]->(c2:Case)

RETURN sa.name as SuperAdmin,
       pa_inst.name as PAInstitution,
       collect(DISTINCT pa_user.name) as PAStaff,
       disc_inst.name as DisdukcapilInstitution,
       collect(DISTINCT disc_user.name) as DisdukcapilStaff,
       count(DISTINCT c1) + count(DISTINCT c2) as TotalCases
```

### 9. FULL VISUALIZATION - Semua dalam satu graph

```cypher
// Tampilkan semua node dan relasi dengan hierarki
MATCH (i:Institution)
OPTIONAL MATCH (u:User)-[:WORKS_AT]->(i)
OPTIONAL MATCH (u)-[r]->(c:Case)
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)

RETURN i,
       collect(DISTINCT u) as Users,
       collect(DISTINCT {rel: r, case: c, doc: d}) as Relationships
LIMIT 100
```

### 10. GROUP BY KATEGORI - Tampilan Ringkasan

```cypher
// Ringkasan berdasarkan kategori
MATCH (u:User)-[:WORKS_AT]->(i:Institution)
OPTIONAL MATCH (u)-[r]->(c:Case)
OPTIONAL MATCH (c)-[:HAS_DOCUMENT]->(d:Document)

RETURN i.name as Institution,
       i.type as Tipe,
       u.name as User,
       collect(DISTINCT CASE
         WHEN c.status = 'DRAFT' THEN '📝 Draft'
         WHEN c.status = 'REJECTED' THEN '❌ Ditolak'
         WHEN c.status IN ['SUBMITTED', 'OCR_PROCESSED'] THEN '📤 Submitted'
         WHEN c.status = 'PA_REVIEW' THEN '🔍 PA Review'
         WHEN c.status = 'DISDUKCAPIL_VALIDATION' THEN '📋 Disdukcapil'
         WHEN c.status = 'COMPLETED' THEN '✅ Completed'
         WHEN c.status = 'ARCHIVED' THEN '📁 Archived'
         ELSE '—'
       END) as KategoriKasus,
       count(DISTINCT c) as TotalKasus
ORDER BY Institution, User
```

## Tabel Ringkasan Relasi

| Klik Node | Relasi | Menampilkan |
|-----------|--------|-------------|
| **Institution PA** | HAS | User PA Assistant, PA Management, PA Staff |
| **Institution Disdukcapil** | HAS | User Disdukcapil Staff |
| **PA Assistant** | SUBMITTED, DRAFT, REJECTED | Kasus Draft, Ditolak, Submitted |
| **PA Management** | HAS_DOCUMENT | Pengajuan Public, Pengajuan PA |
| **PA Management → Disdukcapil** | VERIFY_OPERATOR | Kasus untuk validasi |
| **Disdukcapil Staff** | VERIFY_OPERATOR | Validasi, BAST, Dokumen Digital |
| **PA Staff** | RELATED_TO | Arsip Completed, Archived |

## Warna untuk Neo4j Browser

Set warna manual di Neo4j Browser:

- **Institution PA**: 🔵 Biru
- **Institution Disdukcapil**: 🟢 Hijau
- **User PA Assistant**: 🟠 Orange
- **User PA Management**: 🔷 Biru Tua
- **User PA Staff**: 🔹 Cyan
- **User Disdukcapil Staff**: 🟢 Hijau Muda
- **User Super Admin**: 🟡 Kuning
- **Case DRAFT**: 🟠 Orange
- **Case REJECTED**: 🔴 Merah
- **Case SUBMITTED**: 🔵 Biru
- **Case DISDUKCAPIL_VALIDATION**: 🟣 Ungu
- **Case COMPLETED**: 🟢 Hijau
- **Case ARCHIVED**: ⚫ Abu-abu
- **Document**: ⚪ Putih
