# ReBAC (Relationship-Based Access Control) - SiPadu System

## Gambaran Umum

ReBAC menentukan akses user ke resource berdasarkan **relasi/relationship** yang ada di Neo4j graph database.

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              NEO4J GRAPH                                        │
│                                                                                 │
│   Institution PA                                                            │
│       │                                                                     │
│       ├── PA Assistant ──[WORKS_AT]──→ Institution PA                         │
│       │                                                                     │
│       ├── PA Management ──[WORKS_AT]──→ Institution PA                        │
│       │                                                                     │
│       └── PA Staff ──[WORKS_AT]──→ Institution PA                              │
│                                                                                 │
│   Institution Disdukcapil                                                    │
│       │                                                                     │
│       └── Disdukcapil Staff ──[WORKS_AT]──→ Institution Disdukcapil            │
│                                                                                 │
│   Administrator ──[SUPER_ADMIN]──→ Institution PA                              │
│        │                                                                     │
│        └──[SUPER_ADMIN]──→ Institution Disdukcapil                             │
│                                                                                 │
│   User ──[SUBMITTED/HAS]──→ Case                                              │
│                                                                                 │
│   Case ──[HAS_DOCUMENT]──→ Document                                           │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## Relasi dan Fungsinya

### 1. WORKS_AT

```
User ──[WORKS_AT]──→ Institution
```

**Fungsi:** Menentukan user bekerja di institution mana.

| User | WORKS_AT |
|------|----------|
| PA Assistant | Institution PA |
| PA Management | Institution PA |
| PA Staff | Institution PA |
| Disdukcapil Staff | Institution Disdukcapil |
| Administrator | Institution PA + Institution Disdukcapil |

**Akses yang ditentukan:**
- User hanya bisa melihat data di institution tempat mereka WORKS_AT
- Query: `MATCH (u:User {id: $userId})-[:WORKS_AT]->(i:Institution) RETURN i`

---

### 2. SUPER_ADMIN

```
Administrator ──[SUPER_ADMIN]──→ Institution
```

**Fungsi:** Super Admin punya akses ke kedua institution.

| User | SUPER_ADMIN |
|------|-------------|
| Administrator | Institution PA + Institution Disdukcapil |

**Akses yang ditentukan:**
- Super Admin bisa melihat SEMUA data dari kedua institution
- Tidak memiliki cases secara langsung

---

### 3. SUBMITTED

```
User ──[SUBMITTED]──→ Case
```

**Fungsi:** User yang SUBMITTED case ini (biasanya PA Assistant).

| User dengan SUBMITTED | Kondisi |
|----------------------|---------|
| PA Assistant | Semua case yang dibuat/dikirim oleh PA Assistant |

**Akses yang ditentukan:**
- PA Assistant bisa melihat/edit semua case yang dia SUBMITTED
- Bisa resubmit setelah REJECTED
- Bisa upload dokumen ke case tersebut

**ReBAC Query:**
```cypher
// Cek apakah user punya akses SUBMITTED ke case
MATCH (u:User {id: $userId})-[:SUBMITTED]->(c:Case {id: $caseId})
RETURN count(c) > 0 AS hasAccess
```

---

### 4. HAS

```
User ──[HAS]──→ Case
```

**Fungsi:** User terkait/berinteraksi dengan case ini (relasi umum).

| User dengan HAS | Kondisi |
|----------------|---------|
| PA Assistant | Semua case yang dia buat/submit |
| PA Management | Case dengan status PA_REVIEW+ |
| Disdukcapil Staff | Case dengan status DISDUKCAPIL_VALIDATION+ |
| PA Staff | Case dengan status COMPLETED+ |

**Akses yang ditentukan:**
- User bisa MELIHAT case yang跟他们 terkait
- Tidak selalu berarti bisa EDIT (tergantung role)

**ReBAC Query:**
```cypher
// Cek apakah user punya akses HAS ke case
MATCH (u:User {id: $userId})-[:HAS]->(c:Case {id: $caseId})
RETURN count(c) > 0 AS hasAccess
```

---

### 5. HAS_DOCUMENT

```
Case ──[HAS_DOCUMENT]──→ Document
```

**Fungsi:** Document milik case tertentu. Relasi ini **TIDAK melibatkan User**.

| Relasi | Arah |
|--------|------|
| Case → HAS_DOCUMENT → Document | Case MEMILIKI Document |

**Akses yang ditentukan:**
- User yang punya akses ke Case juga punya akses ke Document-nya
- Tidak ada akses document langsung ke User

**ReBAC Query:**
```cypher
// Cek apakah user punya akses ke document via case
MATCH (u:User {id: $userId})-[:HAS|SUBMITTED]->(c:Case)-[:HAS_DOCUMENT]->(d:Document {id: $docId})
RETURN count(d) > 0 AS hasAccess
```

---

## Matriks Akses ReBAC

| Role | WORKS_AT | SUBMITTED | HAS | HAS_DOCUMENT | SUPER_ADMIN |
|------|----------|-----------|-----|--------------|-------------|
| **PA Assistant** | Institution PA | ✓ Case yang dibuat | ✓ Case terkait | Via Case | ✗ |
| **PA Management** | Institution PA | ✗ | ✓ PA_REVIEW+ | Via Case | ✗ |
| **PA Staff** | Institution PA | ✗ | ✓ COMPLETED+ | Via Case | ✗ |
| **Disdukcapil Staff** | Institution Disdukcapil | ✗ | ✓ DISDUK_VALIDATION+ | Via Case | ✗ |
| **Administrator** | Institution PA + Disdukcapil | ✗ | ✗ | ✗ | ✓ |

---

## Workflow Akses

### Alur Case dalam ReBAC

```
1. PA Assistant membuat case
   PA Assistant ──[SUBMITTED]──→ Case
   PA Assistant ──[HAS]──→ Case

2. Case di-submit ke PA Review
   PA Management ──[HAS]──→ Case
   PA Management ──[MANAGES]──→ Case

3. Case dikirim ke Disdukcapil
   Disdukcapil Staff ──[HAS]──→ Case

4. Case selesai
   PA Staff ──[HAS]──→ Case

5. Arsip
   Case ──[HAS_DOCUMENT]──→ Document
```

---

## Implementasi ReBAC Service

```php
// app/Services/ReBACService.php

public function canAccessCase(User $user, Case $case): bool
{
    // 1. Super Admin bisa akses semua
    if ($user->hasRole('super_admin')) {
        return true;
    }

    // 2. Cek WORKS_AT - user harus bekerja di institution case
    if (!$this->hasWorksAtRelationship($user, $case->institution_id)) {
        return false;
    }

    // 3. Cek SUBMITTED - PA Assistant yang buat case
    if ($this->hasSubmittedRelationship($user->id, $case->id)) {
        return true;
    }

    // 4. Cek HAS - berdasarkan status case dan role
    if ($this->hasHasRelationship($user, $case)) {
        return true;
    }

    return false;
}

private function hasWorksAtRelationship(User $user, int $institutionId): bool
{
    return GraphService::pathExists(
        'User', $user->id,
        'Institution', $institutionId,
        ['WORKS_AT']
    );
}

private function hasSubmittedRelationship(int $userId, int $caseId): bool
{
    return GraphService::pathExists(
        'User', $userId,
        'Case', $caseId,
        ['SUBMITTED']
    );
}

private function hasHasRelationship(User $user, Case $case): bool
{
    // PA Management: PA_REVIEW+
    if ($user->hasRole('pa_management') &&
        in_array($case->status, ['PA_REVIEW', 'DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'])) {
        return true;
    }

    // Disdukcapil Staff: DISDUKCAPIL_VALIDATION+
    if ($user->hasRole('disdukcapil_staff') &&
        in_array($case->status, ['DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED'])) {
        return true;
    }

    // PA Staff: COMPLETED+
    if ($user->hasRole('pa_staff') &&
        in_array($case->status, ['COMPLETED', 'ARCHIVED'])) {
        return true;
    }

    return false;
}
```

---

## Query Neo4j untuk ReBAC

### Cek Akses User ke Case

```cypher
// Apakah user punya akses ke case?
MATCH (u:User {email: 'asisten@pa-painan.go.id'})-[:SUBMITTED|HAS]->(c:Case {case_number: 'CASE-20260704-XXXX'})
RETURN u.name as User, c.case_number as Case, 'ACCESS' as Status
```

### Cek Akses User ke Document

```cypher
// Apakah user punya akses ke document?
MATCH (u:User {email: 'asisten@pa-painan.go.id'})-[:SUBMITTED|HAS]->(c:Case)-[:HAS_DOCUMENT]->(d:Document)
RETURN u.name as User, c.case_number as Case, d.document_type as Document
```

### Cek Semua Case yang Bisa Diakses User

```cypher
// Case yang bisa diakses PA Assistant
MATCH (u:User {name: 'PA Assistant'})-[:SUBMITTED|HAS]->(c:Case)
RETURN c.case_number as Case, c.status as Status, collect(type(relationships((u)-[:SUBMITTED|HAS]->(c)))[0]) as Relationship
ORDER BY c.status
```

### Cek User-Institution WORKS_AT

```cypher
// Institution yang bisa diakses user
MATCH (u:User {name: 'PA Assistant'})-[:WORKS_AT]->(i:Institution)
RETURN u.name as User, i.name as Institution, i.type as Tipe
```

---

## Ringkasan

| Relasi | Arah | Fungsi |
|--------|------|--------|
| `WORKS_AT` | User → Institution | User bekerja di institution mana |
| `SUPER_ADMIN` | Admin → Institution | Admin akses ke institution mana |
| `SUBMITTED` | User → Case | User submit case ini |
| `HAS` | User → Case | User terkait dengan case ini |
| `MANAGES` | User → Case | User mengelola case ini (PA Management) |
| `HAS_DOCUMENT` | Case → Document | Document milik case ini |

**Prinsip ReBAC:**
1. **WORKS_AT** = Akses dasar ke institution
2. **SUBMITTED/HAS** = Akses ke case berdasarkan role dan status
3. **HAS_DOCUMENT** = Akses ke document via case ownership
4. **SUPER_ADMIN** = Akses penuh ke semua institution
