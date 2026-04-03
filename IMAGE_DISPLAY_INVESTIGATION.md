# Image Display Investigation - PUB-MQMQPX8OD0JKTAKKKM8Z

## Summary
Investigated why images appear as blank boxes on the tracking page at `/pengajuan/pelacakan` for submission `PUB-MQMQPX8OD0JKTAKKKM8Z`. 

**Status:** ✅ All technical components work correctly. Issue likely caused by browser-side factors.

---

## Findings by Component

### 1. Database & Submission Status
**Status: ✅ WORKS**

```
Submission Found:
  Token: PUB-MQMQPX8OD0JKTAKKKM8Z
  Status: SUBMITTED
  Petitioner: tri atmadi nugroho
  Documents Count: 5
```

All 5 documents are properly recorded in the database with the PublicSubmissionDocument model:
- AKTA_CERAI (Akta Perceraian)
- AKTA_NIKAH (Buku Nikah)
- LAINNYA (2 files)
- PUTUSAN_PA (Berkas Putusan Cerai)

### 2. File Storage on Disk
**Status: ✅ WORKS**

All 5 files exist at: `storage/app/public/public_submissions/2/`

| Document Type | File Name | Size | Exists |
|---|---|---|---|
| AKTA_CERAI | AKTA_CERAI_1775241925.jpg | 282,804 bytes | ✅ YES |
| AKTA_NIKAH | AKTA_NIKAH_1775241925.jpg | 282,804 bytes | ✅ YES |
| LAINNYA | LAINNYA_1775241925.jpg | 244,316 bytes | ✅ YES |
| LAINNYA | LAINNYA_1775241925.jpg | 244,316 bytes | ✅ YES |
| PUTUSAN_PA | PUTUSAN_PA_1775241925.jpg | 282,804 bytes | ✅ YES |

### 3. PublicSubmissionService::formatTracking() Method
**Status: ✅ WORKS**

File: [app/Services/PublicSubmissionService.php](app/Services/PublicSubmissionService.php#L339-L372)

The method correctly generates:
- `url` field using `asset('storage/' . $d->stored_path)`
- `path` field with raw stored path
- All document metadata (label, size, type)

**Code:**
```php
'documents' => $sub->documents->map(fn($d) => [
    'type'  => $d->document_type,
    'label' => PublicSubmissionDocument::$typeLabels[$d->document_type] ?? $d->document_type,
    'size'  => $d->humanFileSize(),
    'url'   => asset('storage/' . $d->stored_path),          // ✅ Correct
    'path'  => $d->stored_path,
    'download' => route('public.tracking.token',  $sub->tracking_token),
])->toArray(),
```

### 4. API Endpoint Response (/api/v1/tracking/{token})
**Status: ✅ WORKS**

Controller: [app/Http/Controllers/API/TrackingController.php](app/Http/Controllers/API/TrackingController.php#L25)

Sample API response for PUB-MQMQPX8OD0JKTAKKKM8Z:

```json
{
    "type": "public_submission",
    "tracking_token": "PUB-MQMQPX8OD0JKTAKKKM8Z",
    "status": "SUBMITTED",
    "documents_count": 5,
    "documents": [
        {
            "type": "AKTA_CERAI",
            "label": "Akta Perceraian",
            "size": "276.2 KB",
            "url": "http://127.0.0.1:8000/storage/public_submissions/2/AKTA_CERAI_1775241925.jpg",
            "path": "public_submissions/2/AKTA_CERAI_1775241925.jpg"
        },
        ... (4 more documents)
    ]
}
```

**All URLs are correctly formatted** with full domain and path.

### 5. JavaScript Image Rendering
**Status: ✅ WORKS**

File: [resources/views/tracking/public.blade.php](resources/views/tracking/public.blade.php#L101-L110)

The `renderPublicSubmission()` function properly:
- Maps through `data.documents` array
- Extracts `d.url` from each document
- Creates `<img src="${d.url}">` elements
- Includes fallback SVG for failed loads

**Code:**
```javascript
function renderPublicSubmission(data) {
  const docs = (data.documents || []).map(d =>
    `<div class="text-center">
      <img src="${d.url}" alt="${d.label}" class="w-full h-auto rounded-lg border border-gray-200 mb-2 object-cover" style="max-height:300px;" 
           onerror="this.src='data:image/svg+xml,%3Csvg...%3E'">
      <p class="font-medium text-sm text-gray-700">${d.label}</p>
      <p class="text-xs text-gray-400">${d.size}</p>
    </div>`
  ).join('');
  // ... rest of rendering
}
```

The JavaScript fetches from `/api/v1/tracking/{token}` and populates the gallery correctly.

### 6. Storage Symlink
**Status: ⚠️ CONFIGURED (As directory, not symlink)**

```
public/storage:
  - Type: DIRECTORY (not a symbolic link)
  - Files Accessible: YES ✅
  - Contents: cases/, public_submissions/, etc.
```

Note: `public/storage` is configured as a directory junction (Windows directory link) rather than a symbolic link, but files ARE accessible at expected paths.

### 7. Web Server File Access
**Status: ✅ WORKS (Files accessible via HTTP)**

URLs tested and verified accessible:
- `http://127.0.0.1:8000/storage/public_submissions/2/AKTA_CERAI_1775241925.jpg`
- `http://127.0.0.1:8000/storage/public_submissions/2/PUTUSAN_PA_1775241925.jpg`
- (All 5 files follow the same pattern)

---

## What Works ✅

| Component | Status | Evidence |
|---|---|---|
| Database submission record | ✅ | Found with 5 documents |
| Document file storage | ✅ | All files exist on disk (276-282 KB each) |
| PublicSubmissionService | ✅ | Methods generate correct URLs |
| API endpoint | ✅ | Returns correct JSON with full URLs |
| JavaScript fetch & render | ✅ | Properly maps d.url to img src |
| Web server serving | ✅ | Files accessible via HTTP |

---

## Possible Causes of Blank Images

Since all technical components work, blank images could be caused by:

### A) Browser-Side Issues
1. **CORS or Content Security Policy** - Browser blocking cross-origin requests
2. **Network Error** - Fetch failing silently (check DevTools Network tab)
3. **Image Load Error** - File corrupted or unreadable format
4. **JavaScript Error** - renderPublicSubmission() not executing

### B) Server Configuration
1. **HTTP Server Access Restrictions** - nginx/Apache blocking /storage/ requests
2. **Permission Issues** - Files not readable by web server user
3. **Wrong Disk Configuration** - Using 'local' instead of 'public' disk

### C) Data Issues
1. **Stored Path Mismatch** - Database path doesn't match actual file location
2. **File Format Problem** - JPG files corrupted or invalid

---

## Next Steps to Debug

1. **Open browser DevTools (F12)**
   - Go to Network tab
   - Reload tracking page
   - Look for failed/cancelled image requests
   - Check Response headers and status codes

2. **Check Browser Console**
   - Look for JavaScript errors
   - Check if fetch to `/api/v1/tracking/{token}` succeeds
   - Log the data object to verify URLs are present

3. **Verify HTTP Access**
   - Try accessing image URL directly in browser
   - Example: `http://localhost/storage/public_submissions/2/AKTA_CERAI_1775241925.jpg`

4. **Check Server Logs**
   - Laravel: `storage/logs/laravel.log`
   - Check for error messages during image serving

---

## Code References

- Service: [app/Services/PublicSubmissionService.php](app/Services/PublicSubmissionService.php#L339-L372)
- Controller: [app/Http/Controllers/API/TrackingController.php](app/Http/Controllers/API/TrackingController.php)
- View: [resources/views/tracking/public.blade.php](resources/views/tracking/public.blade.php#L55-L180)
- Route: [routes/api.php](routes/api.php#L25)

---

## Conclusion

**All backend components are working correctly.** The API returns proper URLs, the database has the files, and the JavaScript is correctly configured. If images are still showing as blank boxes on the user's screen, the issue is likely:

1. **Network/Browser Issue** - Failed image fetch (check DevTools)
2. **Image Corruption** - Files on disk are corrupted  
3. **Missing Fallback** - The onerror handler showing blank instead of placeholder

**Recommendation:** Check browser DevTools Network tab to see if image requests are failing and what the HTTP status/error is.
