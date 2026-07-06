@extends('layouts.admin')

@section('title', 'Proses Validasi')
@section('page-title', 'Proses Validasi')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.disdukcapil.index') }}" class="hover:text-primary">Validasi</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Proses</span>
@endsection

@section('content')
<div class="space-y-6">

  {{-- Header Card --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-white">Proses Validasi Disdukcapil</h1>
          <p class="text-emerald-200 text-sm mt-1">{{ $case->case_number }}</p>
        </div>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-white/20 text-white">
          <i class="fas fa-clock mr-1"></i> {{ $case->status }}
        </span>
      </div>
    </div>
  </div>

  {{-- Back Button --}}
  <div>
    <a href="{{ route('dashboard.disdukcapil.show', $case->id) }}"
       class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition">
      <i class="fas fa-arrow-left"></i>
      Kembali ke Detail
    </a>
  </div>

  {{-- Case Info Grid --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Pemohon --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-amber-50 px-6 py-3 border-b border-amber-100">
        <h3 class="font-semibold text-amber-800 flex items-center gap-2">
          <i class="fas fa-user"></i> Informasi Pemohon
        </h3>
      </div>
      <div class="p-6 space-y-4">
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">Nama</span>
          <span class="font-medium text-gray-900">{{ $case->petitioner_name ?? '-' }}</span>
        </div>
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">NIK</span>
          <code class="bg-gray-100 px-2 py-0.5 rounded text-sm">{{ $case->petitioner_nik ?? '-' }}</code>
        </div>
        <div class="flex justify-between items-start">
          <span class="text-sm text-gray-500">Alamat</span>
          <span class="font-medium text-gray-900 text-right text-sm max-w-xs">{{ $case->petitioner_alamat ?? '-' }}</span>
        </div>
      </div>
    </div>

    {{-- Pasangan --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-teal-50 px-6 py-3 border-b border-teal-100">
        <h3 class="font-semibold text-teal-800 flex items-center gap-2">
          <i class="fas fa-users"></i> Informasi Pasangan
        </h3>
      </div>
      <div class="p-6 space-y-4">
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">Nama</span>
          <span class="font-medium text-gray-900">{{ $case->spouse_name ?? '-' }}</span>
        </div>
        <div class="flex justify-between items-start border-b border-gray-100 pb-3">
          <span class="text-sm text-gray-500">NIK</span>
          <code class="bg-gray-100 px-2 py-0.5 rounded text-sm">{{ $case->spouse_nik ?? '-' }}</code>
        </div>
        <div class="flex justify-between items-start">
          <span class="text-sm text-gray-500">Alamat</span>
          <span class="font-medium text-gray-900 text-right text-sm max-w-xs">{{ $case->spouse_alamat ?? '-' }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- OCR Validation Results Summary --}}
  @if($case->ocrValidations && $case->ocrValidations->count() > 0)
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-indigo-50 px-6 py-3 border-b border-indigo-100">
      <h3 class="font-semibold text-indigo-800 flex items-center gap-2">
        <i class="fas fa-robot"></i> Hasil Validasi OCR (dari PA Management)
      </h3>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dokumen</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama (OCR)</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Confidence</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach($case->ocrValidations as $validation)
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">{{ $validation->document->document_type ?? '-' }}</td>
            <td class="px-6 py-4 text-gray-600">{{ $validation->ocr_nama ?? '-' }}</td>
            <td class="px-6 py-4">
              @if($validation->review_action === 'approve')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                  <i class="fas fa-check mr-1"></i> APPROVED
                </span>
              @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                  {{ strtoupper($validation->review_action ?? 'PENDING') }}
                </span>
              @endif
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <div class="w-16 bg-gray-200 rounded-full h-1.5">
                  <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ round(($validation->ocrResult->overall_confidence ?? $validation->overall_match_score ?? 0) * 100) }}%"></div>
                </div>
                <span class="text-xs text-gray-600">{{ round(($validation->ocrResult->overall_confidence ?? $validation->overall_match_score ?? 0) * 100, 1) }}%</span>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- Dokumen yang sudah ada --}}
  @php
      $bastDoc = $case->documents->where('document_type', 'BAST')->first();
      $digitalDocs = $case->documents->where('document_type', 'DIGITAL_COPY');
  @endphp

  @if($bastDoc || $digitalDocs->count() > 0)
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-purple-50 px-6 py-3 border-b border-purple-100">
      <h3 class="font-semibold text-purple-800 flex items-center gap-2">
        <i class="fas fa-folder-open"></i> Dokumen yang Sudah Diupload
      </h3>
    </div>
    <div class="p-6 space-y-4">
      @if($bastDoc)
      <div class="flex items-center gap-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
        <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
          <i class="fas fa-file-pdf text-2xl text-emerald-600"></i>
        </div>
        <div class="flex-1">
          <p class="font-medium text-gray-900">BAST (Berita Acara Serah Terima)</p>
          <p class="text-sm text-gray-500">{{ $bastDoc->original_name }} ({{ number_format($bastDoc->size_bytes / 1024, 1) }} KB)</p>
        </div>
        <a href="{{ route('dashboard.disdukcapil.document.download', ['id' => $case->id, 'docId' => $bastDoc->id]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-emerald-300 text-emerald-700 rounded-lg hover:bg-emerald-50 transition font-medium text-sm">
          <i class="fas fa-download"></i> Download
        </a>
      </div>
      @endif

      @if($digitalDocs->count() > 0)
      @foreach($digitalDocs as $doc)
      <div class="flex items-center gap-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
          <i class="fas fa-file-alt text-2xl text-blue-600"></i>
        </div>
        <div class="flex-1">
          <p class="font-medium text-gray-900">Dokumen Digital</p>
          <p class="text-sm text-gray-500">{{ $doc->original_name }} ({{ number_format($doc->size_bytes / 1024, 1) }} KB)</p>
        </div>
        <a href="{{ route('dashboard.disdukcapil.document.download', ['id' => $case->id, 'docId' => $doc->id]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 transition font-medium text-sm">
          <i class="fas fa-download"></i> Download
        </a>
      </div>
      @endforeach
      @endif
    </div>
  </div>
  @endif

  {{-- Upload Forms --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
      <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
        <i class="fas fa-cloud-upload-alt text-primary"></i>
        Upload Dokumen Serah Terima
      </h2>
    </div>
    <div class="p-6">
      <form action="{{ route('dashboard.disdukcapil.process.submit', $case->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- BAST Upload --}}
        <div class="border-b border-gray-200 pb-6">
          <label class="block text-base font-semibold text-gray-900 mb-2">
            <i class="fas fa-file-pdf text-red-500 mr-2"></i>Berita Acara Serah Terima (BAST)
          </label>
          <p class="text-sm text-gray-600 mb-4">
            Upload dokumen BAST dari Disdukcapil sebagai bukti penerimaan dokumen
          </p>

          <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-primary transition cursor-pointer"
               onclick="document.getElementById('bast_input').click()">
            <input type="file" name="bast_file" accept=".pdf,.doc,.docx" class="hidden" id="bast_input" onchange="handleFileSelect(this, 'bast_preview')">
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-600 font-medium">Klik untuk pilih file BAST</p>
            <p class="text-xs text-gray-400 mt-1">PDF, DOC, atau DOCX (Max 10MB)</p>
          </div>

          @if($bastDoc)
          <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
            <p class="text-emerald-800 flex items-center gap-2">
              <i class="fas fa-check-circle"></i>
              BAST sudah diupload: <strong>{{ $bastDoc->original_name }}</strong>
            </p>
          </div>
          @endif

          <div id="bast_preview" class="mt-3 space-y-2"></div>
          @error('bast_file')
          <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Digital Documents Upload --}}
        <div class="border-b border-gray-200 pb-6">
          <label class="block text-base font-semibold text-gray-900 mb-2">
            <i class="fas fa-file-alt text-blue-500 mr-2"></i>Dokumen Digital (Scan Dokumen Asli)
          </label>
          <p class="text-sm text-gray-600 mb-4">
            Upload scan dokumen asli (KTP, Akta Cerai, Putusan PA) sebagai dokumen digital
          </p>

          <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-primary transition cursor-pointer"
               onclick="document.getElementById('digital_input').click()">
            <input type="file" name="digital_files[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="digital_input" onchange="handleDigitalFiles(this)">
            <i class="fas fa-images text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-600 font-medium">Klik untuk pilih file dokumen (multiple)</p>
            <p class="text-xs text-gray-400 mt-1">PDF, JPG, atau PNG (Max 10MB per file)</p>
          </div>

          @if($digitalDocs->count() > 0)
          <div class="mt-4 space-y-2">
            @foreach($digitalDocs as $doc)
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
              <p class="text-blue-800 text-sm flex items-center gap-2">
                <i class="fas fa-check-circle text-blue-500"></i>
                {{ $doc->original_name }}
              </p>
            </div>
            @endforeach
          </div>
          @endif

          <div id="digital_preview" class="mt-3 space-y-2"></div>
          @error('digital_files')
          <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Notes --}}
        <div>
          <label class="block text-base font-semibold text-gray-900 mb-2">
            <i class="fas fa-sticky-note text-amber-500 mr-2"></i>Catatan
          </label>
          <textarea name="notes" rows="3"
            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="Tambahkan catatan atau komentar jika diperlukan...">{{ old('notes') }}</textarea>
          @error('notes')
          <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Error Messages --}}
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
          <ul class="list-disc list-inside text-red-800 text-sm space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
          <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition shadow-sm">
            <i class="fas fa-check-circle"></i>
            Selesaikan Validasi & Kirim ke PA
          </button>
          <button type="button" onclick="openRejectModal()" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-xl transition shadow-sm">
            <i class="fas fa-arrow-left"></i>
            Kirim Kembali ke PA
          </button>
          <a href="{{ route('dashboard.disdukcapil.index') }}" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-xl transition">
            Batal
          </a>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection

{{-- Modal Konfirmasi Kirim Kembali ke PA (Alpine.js) --}}
<div x-data="{ show: false, reason: '', loading: false }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @open-reject-modal.window="show = true; reason = ''"
     @keydown.escape.window="show = false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display: none"
     x-cloak>
  <div class="fixed inset-0 bg-black/50" @click="show = false"></div>
  <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden z-10"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 scale-95"
       x-transition:enter-end="opacity-100 scale-100">
    <div class="bg-red-600 px-6 py-4 flex items-center justify-between">
      <h3 class="text-lg font-bold text-white flex items-center gap-2">
        <i class="fas fa-exclamation-triangle"></i>
        Kirim Kembali ke PA
      </h3>
      <button @click="show = false" class="text-white/80 hover:text-white transition">
        <i class="fas fa-times text-lg"></i>
      </button>
    </div>
    <div class="p-6">
      <p class="text-gray-600 mb-4">Kasus akan dikembalikan ke <strong>PA Management</strong> untuk diperbaiki.</p>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">
          Alasan <span class="text-red-500">*</span>
        </label>
        <textarea x-model="reason" rows="4"
          class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
          placeholder="Contoh: Dokumen tidak lengkap. Mohon lengkapi dokumen pendukung." maxlength="1000"></textarea>
        <small class="text-gray-500 float-end"><span x-text="reason.length">0</span>/1000 karakter</small>
      </div>
    </div>
    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
      <button @click="show = false" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition font-medium">
        Batal
      </button>
      <button @click="submitReject()" :disabled="loading"
        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
        <template x-if="loading">
          <i class="fas fa-spinner fa-spin"></i>
        </template>
        <template x-if="!loading">
          <i class="fas fa-arrow-left"></i>
        </template>
        <span x-text="loading ? 'Memproses...' : 'Kirim Kembali'"></span>
      </button>
    </div>
  </div>
</div>

@push('scripts')
<script>
function openRejectModal() {
  window.dispatchEvent(new CustomEvent('open-reject-modal'));
}

function submitReject() {
  const modal = document.querySelector('[x-data]');
  const reason = modal.__x.$data.reason;

  if (!reason.trim()) {
    alert('Mohon isi alasan pengembalian.');
    return;
  }

  modal.__x.$data.loading = true;
  const caseId = {{ $case->id }};

  const formData = new FormData();
  formData.append('reject_reason', reason);
  formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

  fetch(`/dashboard/disdukcapil/cases/${caseId}/send-to-pa`, {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    }
  })
  .then(response => response.json())
  .then(data => {
    modal.__x.$data.show = false;
    alert('Kasus berhasil dikembalikan ke PA Management!');
    window.location.href = '{{ route('dashboard.disdukcapil.index') }}';
  })
  .catch(error => {
    console.error('Error:', error);
    modal.__x.$data.loading = false;
    alert('Gagal mengirim kasus kembali. Silakan coba lagi.');
  });
}

function handleFileSelect(input, previewId) {
  const preview = document.getElementById(previewId);
  preview.innerHTML = '';

  if (input.files && input.files[0]) {
    const file = input.files[0];
    preview.innerHTML = `
      <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-3">
          <i class="fas fa-file text-blue-500 text-xl"></i>
          <div>
            <p class="font-medium text-gray-900">${file.name}</p>
            <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
          </div>
        </div>
        <button type="button" onclick="clearFileInput('${input.id}', '${previewId}')" class="text-red-500 hover:text-red-700">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
  }
}

function handleDigitalFiles(input) {
  const preview = document.getElementById('digital_preview');
  preview.innerHTML = '';

  if (input.files && input.files.length > 0) {
    Array.from(input.files).forEach((file, index) => {
      preview.innerHTML += `
        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center gap-3">
          <i class="fas fa-image text-blue-500"></i>
          <div class="flex-1">
            <p class="font-medium text-gray-900 text-sm">${file.name}</p>
            <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
          </div>
        </div>
      `;
    });
  }
}

function clearFileInput(inputId, previewId) {
  document.getElementById(inputId).value = '';
  document.getElementById(previewId).innerHTML = '';
}
</script>
@endpush
