@extends('layouts.admin')

@section('title', 'Detail Kasus ' . $case->case_number)

@section('content')
<div class="max-w-6xl mx-auto" x-data="caseDetail({{ $case->id }})">

  {{-- Breadcrumb --}}
  <nav class="text-xs text-gray-500 mb-3">
    <span class="text-gray-600">Kotak Masuk</span>
    <span class="mx-1">/</span>
    <span class="font-mono text-gray-800 font-medium">{{ $case->case_number }}</span>
  </nav>

  {{-- Notifikasi --}}
  @if(session('success'))
    <div class="mb-3 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 text-emerald-700 text-xs flex items-center gap-2">
      <i class="fas fa-check-circle"></i>
      <span>{{ session('success') }}</span>
    </div>
  @endif
  @if(session('error'))
    <div class="mb-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-red-700 text-xs flex items-center gap-2">
      <i class="fas fa-exclamation-circle"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  {{-- Header Card (Compact) --}}
  <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex-1">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Token Tracking</div>
        <div class="font-mono text-sm font-bold text-blue-700 mb-2">{{ $case->tracking_token }}</div>
        @php
          $statusColors = [
            'DRAFT' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Draft'],
            'SUBMITTED' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Dikirim'],
            'OCR_PROCESSED' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'label' => 'OCR Selesai'],
            'PA_REVIEW' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Review PA'],
            'DISDUKCAPIL_VALIDATION' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => 'Validasi Disdukcapil'],
            'COMPLETED' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Selesai'],
            'REJECTED' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Ditolak'],
          ];
          $status = $statusColors[$case->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => $case->status];
        @endphp
        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $status['bg'] }} {{ $status['text'] }}">
          {{ $status['label'] }}
        </span>
      </div>
      <div class="text-right text-xs text-gray-600">
        <div><strong>Dibuat:</strong> {{ $case->created_at->format('d M Y, H:i') }}</div>
        @if($case->submitted_at)
        <div><strong>Dikirim:</strong> {{ $case->submitted_at->format('d M Y, H:i') }}</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ACTION BUTTONS: Draft Management --}}
  @if($case->status === 'DRAFT' && ($case->submitter_id === auth()->id() || auth()->user()->hasRole('super_admin')))
  <div class="flex gap-2 mb-4 flex-wrap">
    <a href="{{ route('dashboard.cases.edit-draft', $case->id) }}" 
       class="px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition flex items-center gap-2">
      <i class="fas fa-edit"></i>
      Edit Draft
    </a>
    <a href="{{ route('dashboard.cases.edit-draft', $case->id) }}#submit" 
       class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition flex items-center gap-2">
      <i class="fas fa-paper-plane"></i>
      Kirim Pengajuan
    </a>
    <a href="{{ route('dashboard.cases') }}" 
       class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition">
      Kembali
    </a>
  </div>
  @endif

  {{-- Info Grid Ringkas --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
      <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">No. Kasus</div>
      <div class="font-mono text-sm font-bold text-gray-900">{{ $case->case_number }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
      <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Pemohon</div>
      <div class="text-sm font-medium text-gray-900">{{ $case->submitter?->name ?? '-' }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
      <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Institusi</div>
      <div class="text-sm text-gray-900">{{ $case->institution?->name ?? '-' }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
      <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Tanggal Cerai</div>
      <div class="text-sm font-mono text-gray-900">{{ $case->divorce_date ? $case->divorce_date->format('d M Y') : '-' }}</div>
    </div>
  </div>

  {{-- Data Suami & Istri (Bersih) --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">
    {{-- Data Suami --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-blue-50 px-4 py-2 border-b border-blue-200 flex items-center gap-2">
        <i class="fas fa-male text-blue-600 text-sm"></i>
        <h3 class="font-semibold text-gray-800 text-sm">Data Suami</h3>
        @if($suami_ocr['is_available'] && $suami_ocr['is_reviewed'])
        <span class="ml-auto text-xs px-2 py-1 rounded bg-green-100 text-green-700">✓ Sudah Divalidasi</span>
        @elseif($suami_ocr['is_available'])
        <span class="ml-auto text-xs px-2 py-1 rounded bg-amber-100 text-amber-700">OCR</span>
        @endif
      </div>
      <div class="p-3 space-y-3 text-xs">
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">NIK</div>
          <div class="font-mono font-bold text-gray-900">{{ $suami_ocr['is_available'] ? ($suami_ocr['nik'] ?? '-') : ($case->petitioner_nik ?? '-') }}</div>
        </div>
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">Nama</div>
          <div class="font-medium text-gray-900">{{ $suami_ocr['is_available'] ? ($suami_ocr['nama'] ?? '-') : ($case->petitioner_name ?? '-') }}</div>
        </div>
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">Telepon</div>
          <div class="font-mono text-gray-900">{{ $case->petitioner_phone ?? '-' }}</div>
        </div>
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">Alamat</div>
          <div class="text-gray-900">{{ $suami_ocr['is_available'] ? ($suami_ocr['alamat'] ?? '-') : ($case->petitioner_alamat ?? '-') }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <div class="text-gray-500 uppercase tracking-wide mb-0.5">RT/RW</div>
            <div class="font-mono text-gray-900">{{ $suami_ocr['is_available'] ? ($suami_ocr['rt_rw'] ?? '-') : ($case->petitioner_rt_rw ?? '-') }}</div>
          </div>
          <div>
            <div class="text-gray-500 uppercase tracking-wide mb-0.5">Kelurahan</div>
            <div class="text-gray-900">{{ $suami_ocr['is_available'] ? ($suami_ocr['kelurahan'] ?? '-') : ($case->petitioner_kelurahan ?? '-') }}</div>
          </div>
        </div>
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">Kecamatan</div>
          <div class="text-gray-900">{{ $suami_ocr['is_available'] ? ($suami_ocr['kecamatan'] ?? '-') : ($case->petitioner_kecamatan ?? '-') }}</div>
        </div>
      </div>
    </div>

    {{-- Data Istri --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-teal-50 px-4 py-2 border-b border-teal-200 flex items-center gap-2">
        <i class="fas fa-female text-teal-600 text-sm"></i>
        <h3 class="font-semibold text-gray-800 text-sm">Data Istri</h3>
        @if($istri_ocr['is_available'] && $istri_ocr['is_reviewed'])
        <span class="ml-auto text-xs px-2 py-1 rounded bg-green-100 text-green-700">✓ Sudah Divalidasi</span>
        @elseif($istri_ocr['is_available'])
        <span class="ml-auto text-xs px-2 py-1 rounded bg-amber-100 text-amber-700">OCR</span>
        @endif
      </div>
      <div class="p-3 space-y-3 text-xs">
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">NIK</div>
          <div class="font-mono font-bold text-gray-900">{{ $istri_ocr['is_available'] ? ($istri_ocr['nik'] ?? '-') : ($case->spouse_nik ?? '-') }}</div>
        </div>
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">Nama</div>
          <div class="font-medium text-gray-900">{{ $istri_ocr['is_available'] ? ($istri_ocr['nama'] ?? '-') : ($case->spouse_name ?? '-') }}</div>
        </div>
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">Alamat</div>
          <div class="text-gray-900">{{ $istri_ocr['is_available'] ? ($istri_ocr['alamat'] ?? '-') : ($case->spouse_alamat ?? '-') }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <div class="text-gray-500 uppercase tracking-wide mb-0.5">RT/RW</div>
            <div class="font-mono text-gray-900">{{ $istri_ocr['is_available'] ? ($istri_ocr['rt_rw'] ?? '-') : ($case->spouse_rt_rw ?? '-') }}</div>
          </div>
          <div>
            <div class="text-gray-500 uppercase tracking-wide mb-0.5">Kelurahan</div>
            <div class="text-gray-900">{{ $istri_ocr['is_available'] ? ($istri_ocr['kelurahan'] ?? '-') : ($case->spouse_kelurahan ?? '-') }}</div>
          </div>
        </div>
        <div>
          <div class="text-gray-500 uppercase tracking-wide mb-0.5">Kecamatan</div>
          <div class="text-gray-900">{{ $istri_ocr['is_available'] ? ($istri_ocr['kecamatan'] ?? '-') : ($case->spouse_kecamatan ?? '-') }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Data Perceraian --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
      <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Tanggal Cerai</div>
      <div class="text-sm font-mono font-bold text-gray-900">{{ $case->divorce_date ? $case->divorce_date->format('d M Y') : '-' }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
      <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">No. Putusan PA</div>
      <div class="text-sm font-mono text-gray-900">{{ $case->verdict_number ?? '-' }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
      <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Catatan</div>
      <div class="text-sm text-gray-900">{{ $case->notes ? substr($case->notes, 0, 30) . '...' : '-' }}</div>
    </div>
  </div>

  {{-- Dokumen Diunggah (Compact) --}}
  @if($case->documents->isNotEmpty())
  <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mb-4">
    <div class="bg-emerald-50 px-4 py-2 border-b border-emerald-200 flex items-center gap-2">
      <i class="fas fa-file-pdf text-emerald-600 text-sm"></i>
      <h3 class="font-semibold text-gray-800 text-sm">Dokumen Diunggah ({{ $case->documents->count() }})</h3>
    </div>
    <div class="p-3">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach($case->documents as $doc)
        @php
          $isImage = in_array($doc->mime_type, ['image/jpeg', 'image/png', 'image/tiff', 'image/webp']);
          $storageUrl = asset('storage/' . $doc->path);
        @endphp
        <div class="rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition cursor-pointer group"
             onclick="openDocPreview('{{ $doc->id }}', '{{ $doc->document_type }}', '{{ $storageUrl }}')">
          @if($isImage)
          <div class="w-full h-32 bg-gray-100 overflow-hidden">
            <img src="{{ $storageUrl }}" alt="{{ $doc->document_type }}" 
                 class="w-full h-full object-cover group-hover:scale-105 transition">
          </div>
          @else
          <div class="w-full h-32 bg-gradient-to-br from-red-100 to-red-50 flex items-center justify-center">
            <i class="fas fa-file-pdf text-red-500 text-4xl"></i>
          </div>
          @endif
          <div class="p-2 bg-white">
            <div class="font-medium text-gray-900 text-xs truncate">{{ $doc->document_type }}</div>
            <div class="text-gray-500 text-xs">{{ number_format($doc->size_bytes / 1024, 1) }}KB</div>
            <a href="{{ $storageUrl }}" target="_blank" 
               class="text-xs text-emerald-600 hover:underline mt-1 inline-block"
               onclick="event.stopPropagation()">
              <i class="fas fa-download mr-1"></i>Download
            </a>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Document Preview Modal --}}
  <div id="docPreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4" onclick="closeDocPreview()">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900 text-lg" id="previewTitle">Document Preview</h3>
        <button onclick="closeDocPreview()" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      <div class="p-6 flex items-center justify-center">
        <img id="previewImage" src="" alt="Document preview" class="max-w-full max-h-[70vh] rounded-lg">
      </div>
      <div class="border-t border-gray-200 px-6 py-4 flex gap-2 justify-end">
        <button onclick="closeDocPreview()" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition">Tutup</button>
        <a id="previewDownloadBtn" href="#" target="_blank" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
          <i class="fas fa-download mr-1"></i>Download
        </a>
      </div>
    </div>
  </div>

  <script>
  function openDocPreview(docId, docType, url) {
    document.getElementById('previewTitle').textContent = 'Preview: ' + docType;
    document.getElementById('previewImage').src = url;
    document.getElementById('previewDownloadBtn').href = url;
    document.getElementById('docPreviewModal').classList.remove('hidden');
  }

  function closeDocPreview() {
    document.getElementById('docPreviewModal').classList.add('hidden');
  }

  // Close modal on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDocPreview();
  });
  </script>
  @endif

  {{-- OCR Validation Summary --}}
  @if(auth()->user()->hasAnyRole(['pa_management', 'super_admin']) && $case->ocrValidations->isNotEmpty())
  <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <i class="fas fa-microscope text-indigo-600 text-lg"></i>
        <h3 class="font-semibold text-gray-800">OCR Validation ({{ $case->ocrValidations->count() }})</h3>
      </div>
      <a href="{{ route('dashboard.review.show', $case->id) }}" 
         class="text-xs bg-indigo-100 text-indigo-700 px-3 py-1 rounded-lg font-medium hover:bg-indigo-200">
        Review Detail →
      </a>
    </div>
    
    <div class="space-y-2">
      @foreach($case->ocrValidations as $v)
      <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg text-xs">
        <div class="flex items-center gap-2 flex-1">
          @php
            $badge = match($v->validation_status) {
              'MATCH' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => '✓ Match'],
              'PARTIAL_MATCH' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => '~ Partial'],
              'MISMATCH' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => '✗ Mismatch'],
              default => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Review'],
            };
          @endphp
          <span class="font-medium text-gray-900">{{ $v->document->document_type }}</span>
          <span class="px-2 py-0.5 rounded {{ $badge['bg'] }} {{ $badge['text'] }} font-medium">
            {{ $badge['label'] }}
          </span>
        </div>
        <div class="font-bold {{ $v->overall_match_score >= 90 ? 'text-green-600' : ($v->overall_match_score >= 75 ? 'text-yellow-600' : 'text-red-600') }}">
          {{ number_format($v->overall_match_score, 0) }}%
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Timeline (Compact) --}}
  @if($case->transitions->isNotEmpty())
  <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-4">
    <h3 class="font-semibold text-gray-800 text-sm mb-3 flex items-center gap-2">
      <i class="fas fa-history text-purple-500 text-sm"></i>Timeline
    </h3>
    <div class="space-y-1 text-xs">
      @foreach($case->transitions->take(5) as $t)
      <div class="flex items-center gap-2 pb-2 border-b border-gray-100 last:border-0">
        <span class="text-gray-500 font-mono">{{ $t->created_at->format('d M H:i') }}</span>
        <span class="text-gray-600">{{ $t->from_state }}</span>
        <i class="fas fa-arrow-right text-xs text-gray-300"></i>
        <span class="font-bold text-blue-700">{{ $t->to_state }}</span>
        @if($t->reason)
        <span class="text-gray-600 italic">{{ substr($t->reason, 0, 20) }}...</span>
        @endif
      </div>
      @endforeach
      @if($case->transitions->count() > 5)
      <div class="text-gray-500 text-center py-2">+{{ $case->transitions->count() - 5 }} lebih</div>
      @endif
    </div>
  </div>
  @endif

  {{-- Actions (Compact) --}}
  <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
    <h3 class="font-semibold text-gray-800 text-sm mb-3">Tindakan</h3>

    @if(in_array($case->status, ['SUBMITTED', 'OCR_PROCESSED', 'PA_REVIEW']))
    <div class="text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 mb-3">
      <i class="fas fa-info-circle mr-1"></i>Kasus sedang diproses otomatis
    </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-2">
      {{-- PA Review --}}
      @if($case->status === 'PA_REVIEW' && auth()->user()->hasAnyRole(['pa_management','pa_staff']))
        <button @click="review('approve')"
          class="flex-1 py-2 text-xs font-semibold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
          <i class="fas fa-check"></i> Setujui
        </button>
        <button @click="showRejectModal = true"
          class="flex-1 py-2 text-xs font-semibold border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition">
          <i class="fas fa-times"></i> Tolak
        </button>
      @endif

      {{-- Disdukcapil Validate --}}
      @if($case->status === 'DISDUKCAPIL_VALIDATION' && auth()->user()->hasRole('disdukcapil_staff'))
        <div x-show="!showUploadForm" class="flex gap-2 w-full">
          <button @click="showUploadForm = true"
            class="flex-1 py-2 text-xs font-semibold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
            <i class="fas fa-stamp"></i> Validasi
          </button>
          <button @click="showRejectModal = true"
            class="flex-1 py-2 text-xs font-semibold border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition">
            <i class="fas fa-times"></i> Tolak
          </button>
        </div>

        {{-- Upload Form (Hidden by default) --}}
        <form x-show="showUploadForm" x-cloak style="display: none" @submit.prevent="submitUpload" class="space-y-3">
          {{-- BAST File --}}
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-2">
              <i class="fas fa-file-pdf mr-1 text-red-600"></i>File BAST
              <span class="text-red-600">*</span>
            </label>
            <input type="file" 
              x-model="uploadForm.bast_file"
              @change="uploadForm.bast_file = $event.target.files[0]"
              accept=".pdf,.doc,.docx"
              required
              class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <div class="text-xs text-gray-500 mt-1">Format: PDF, DOC, DOCX (max 10MB)</div>
          </div>

          {{-- Digital Documents --}}
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-2">
              <i class="fas fa-images mr-1 text-blue-600"></i>Dokumen Digital
              <span class="text-red-600">*</span>
            </label>
            <input type="file" 
              x-ref="digitalFiles"
              @change="uploadForm.digital_files = Array.from($event.target.files)"
              accept=".pdf,.jpg,.jpeg,.png"
              multiple
              required
              class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <div class="text-xs text-gray-500 mt-1">Format: PDF, JPG, PNG (max 10MB per file)</div>
          </div>

          {{-- Notes --}}
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-2">
              <i class="fas fa-sticky-note mr-1 text-amber-600"></i>Catatan (Opsional)
            </label>
            <textarea x-model="uploadForm.notes"
              rows="2"
              class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              placeholder="Tambahkan catatan jika diperlukan..."></textarea>
          </div>

          {{-- Buttons --}}
          <div class="flex gap-2 pt-2">
            <button type="submit" 
              :disabled="isUploading"
              class="flex-1 py-2 text-xs font-semibold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1">
              <span x-show="!isUploading"><i class="fas fa-check"></i> Selesaikan Validasi</span>
              <span x-show="isUploading"><i class="fas fa-spinner fa-spin"></i> Sedang Upload...</span>
            </button>
            <button type="button" 
              @click="showUploadForm = false; resetForm()"
              class="flex-1 py-2 text-xs font-semibold border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
              Batal
            </button>
          </div>
        </form>
      @endif

      @if(!in_array($case->status, ['PA_REVIEW', 'DISDUKCAPIL_VALIDATION']))
        <div class="text-xs text-gray-500 text-center py-2 w-full">
          Status: <span class="font-semibold text-gray-800">{{ $status['label'] }}</span>
        </div>
      @endif
    </div>
  </div>

  {{-- Reject Modal --}}
  <div x-show="showRejectModal" 
       x-cloak
       style="display: none"
       @click.self="showRejectModal = false"
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md" @click.stop>
      <h3 class="text-lg font-bold text-gray-900 mb-4">Alasan Penolakan</h3>
      <textarea x-model="rejectReason" rows="3" placeholder="Jelaskan alasan penolakan..."
                class="w-full px-4 py-2 border border-gray-300 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
      <div class="flex gap-3 mt-4">
        <button @click="rejectCase()" class="flex-1 py-2 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 transition">Tolak</button>
        <button @click="showRejectModal = false" class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition">Batal</button>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
function caseDetail(caseId) {
  return {
    caseId,
    showRejectModal: false,
    rejectReason: '',
    showUploadForm: false,
    isUploading: false,
    uploadForm: {
      bast_file: null,
      digital_files: [],
      notes: ''
    },

    resetForm() {
      this.uploadForm = {
        bast_file: null,
        digital_files: [],
        notes: ''
      };
    },

    async submitUpload() {
      if (!this.uploadForm.bast_file) {
        alert('File BAST harus diupload');
        return;
      }
      if (this.uploadForm.digital_files.length === 0) {
        alert('Minimal satu dokumen digital harus diupload');
        return;
      }

      this.isUploading = true;
      const formData = new FormData();
      formData.append('bast_file', this.uploadForm.bast_file);
      
      this.uploadForm.digital_files.forEach((file, index) => {
        formData.append(`digital_files[${index}]`, file);
      });
      
      if (this.uploadForm.notes) {
        formData.append('notes', this.uploadForm.notes);
      }

      try {
        const res = await fetch(`/dashboard/disdukcapil/cases/${caseId}/process`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
          },
          body: formData
        });

        if (res.ok) {
          const data = await res.json();
          alert('Validasi selesai! Kasus telah dikirim ke PA untuk diproses.');
          location.reload();
        } else {
          const error = await res.json();
          alert('Gagal upload: ' + (error.message || 'Terjadi kesalahan'));
        }
      } catch (err) {
        alert('Gagal upload: ' + err.message);
      } finally {
        this.isUploading = false;
      }
    },

    async review(decision) {
      const res = await this.apiCall('POST', '/api/v1/review/pa', { case_id: caseId, decision });
      if (res.ok) {
        location.reload();
      } else {
        alert('Gagal review kasus');
      }
    },

    async validate(decision) {
      const res = await this.apiCall('POST', '/api/v1/review/disdukcapil', { case_id: caseId, decision });
      if (res.ok) {
        location.reload();
      } else {
        alert('Gagal validasi kasus');
      }
    },

    async rejectCase() {
      if (!this.rejectReason.trim()) {
        alert('Alasan penolakan harus diisi');
        return;
      }
      
      const endpoint = '{{ in_array($case->status, ["PA_REVIEW"]) ? "/api/v1/review/pa" : "/api/v1/review/disdukcapil" }}';
      const body = { case_id: caseId, decision: 'reject', notes: this.rejectReason };
      const res = await this.apiCall('POST', endpoint, body);
      
      if (res.ok) {
        location.reload();
      } else {
        alert('Gagal menolak kasus');
      }
    },

    async apiCall(method, url, body = null) {
      const opts = {
        method,
        headers: { 
          'Content-Type': 'application/json', 
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
        },
      };
      if (body) opts.body = JSON.stringify(body);
      return await fetch(url, opts);
    }
  };
}
</script>
@endpush
@endsection
