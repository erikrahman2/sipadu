@extends('layouts.admin')

@section('title', $isRejectedEdit ?? false ? 'Perbaiki Kasus Ditolak' : 'Edit Draft Kasus')
@section('page-title', $isRejectedEdit ?? false ? 'Perbaiki Kasus Ditolak' : 'Edit Draft Kasus')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.cases') }}" class="hover:text-primary">Kasus</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">{{ $isRejectedEdit ?? false ? 'Perbaiki Ditolak' : 'Edit Draft' }}</span>
@endsection

@push('styles')
<style>
  .doc-upload-area {
    @apply border-2 border-dashed border-gray-300 rounded-xl p-5 text-center cursor-pointer transition hover:border-emerald-400 hover:bg-emerald-50;
  }
  .doc-upload-area.has-file {
    @apply border-emerald-400 bg-emerald-50;
  }
  .step-badge {
    @apply inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-600 text-white text-sm font-bold flex-shrink-0;
  }
  .input-field {
    @apply w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent;
  }
  .doc-item {
    @apply flex items-center justify-between p-3 rounded-lg bg-gray-50 border border-gray-200;
  }
  .cerai-option-card {
    @apply border-emerald-500 bg-emerald-50;
  }
  /* Panel visibility - hidden but inputs still accessible */
  .hidden-panel-only {
    display: none;
  }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    @if($isRejectedEdit ?? false)
    <div class="bg-red-50 border border-red-200 rounded-2xl p-6">
      <div class="flex items-start gap-3">
        <div class="mt-1">
          <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
        </div>
        <div>
          <h3 class="font-semibold text-red-800 mb-1">Status: PERBAIKAN DITOLAK</h3>
          <p class="text-sm text-red-700">Kasus ini ditolak oleh PA Management. Silakan perbaiki data dan dokumen, lalu kirim ulang.</p>
        </div>
      </div>
    </div>
    @else
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
      <div class="flex items-start gap-3">
        <div class="mt-1">
          <i class="fas fa-file-import text-amber-600 text-2xl"></i>
        </div>
        <div>
          <h3 class="font-semibold text-amber-800 mb-1">Status: DRAFT</h3>
          <p class="text-sm text-amber-700">Kasus masih dalam penyimpanan sementara. Lengkapi dan kirim kapan saja.</p>
        </div>
      </div>
    </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6">
      <div class="flex items-start gap-3">
        <div class="mt-1">
          <i class="fas fa-lightbulb text-blue-600 text-2xl"></i>
        </div>
        <div>
          <h3 class="font-semibold text-blue-800 mb-1">Token Tracking</h3>
          <p class="text-sm text-blue-700 font-mono">{{ $case->tracking_token }}</p>
        </div>
      </div>
    </div>
  </div>

  @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-3 text-red-700 text-sm">
      <i class="fas fa-exclamation-circle mr-1"></i>
      Terdapat kesalahan pada form. Mohon periksa kembali.
      @if($errors->first())
        <div class="mt-2 text-xs">{{ $errors->first() }}</div>
      @endif
    </div>
  @endif

  <form id="draftForm" method="POST" action="{{ route('dashboard.cases.update-draft', $case->id) }}" enctype="multipart/form-data" class="space-y-8">
    @csrf
    @method('PATCH')
    <input type="hidden" id="formAction" value="update">

    {{-- === Langkah 1: Data Pasangan === --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">1</span>
        <h2 class="font-semibold text-gray-800">Data Pasangan</h2>
        <span class="text-xs text-gray-400 ml-auto">Maks. 10 MB per file - JPG, PNG, PDF</span>
      </div>
      <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 space-y-4">
            <h3 class="font-semibold text-emerald-800">Data Suami</h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">NIK Suami <span class="text-red-500">*</span></label>
              <input type="text" name="suami_nik" value="{{ old('suami_nik', $case->petitioner_nik) }}" maxlength="16" inputmode="numeric" pattern="\d{16}"
                placeholder="16 digit sesuai KTP" class="input-field @error('suami_nik') border-red-400 @enderror" required>
              @error('suami_nik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nama Suami <span class="text-red-500">*</span></label>
              <input type="text" name="suami_name" value="{{ old('suami_name', $case->petitioner_name) }}" placeholder="Nama sesuai KTP"
                class="input-field @error('suami_name') border-red-400 @enderror" required>
              @error('suami_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Suami <span class="text-red-500">*</span></label>
              <input type="text" name="suami_alamat" value="{{ old('suami_alamat', $case->petitioner_alamat) }}" placeholder="Alamat sesuai KTP"
                class="input-field @error('suami_alamat') border-red-400 @enderror" required>
              @error('suami_alamat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RT/RW <span class="text-red-500">*</span></label>
                <input type="text" name="suami_rt_rw" value="{{ old('suami_rt_rw', $case->petitioner_rt_rw) }}" placeholder="000/000"
                  class="input-field @error('suami_rt_rw') border-red-400 @enderror" required>
                @error('suami_rt_rw') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan <span class="text-red-500">*</span></label>
                <input type="text" name="suami_kelurahan" value="{{ old('suami_kelurahan', $case->petitioner_kelurahan) }}"
                  class="input-field @error('suami_kelurahan') border-red-400 @enderror" required>
                @error('suami_kelurahan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
              <input type="text" name="suami_kecamatan" value="{{ old('suami_kecamatan', $case->petitioner_kecamatan) }}"
                class="input-field @error('suami_kecamatan') border-red-400 @enderror" required>
              @error('suami_kecamatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
          </div>

          <div class="rounded-xl border border-teal-100 bg-teal-50/40 p-4 space-y-4">
            <h3 class="font-semibold text-teal-800">Data Istri</h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">NIK Istri <span class="text-red-500">*</span></label>
              <input type="text" name="istri_nik" value="{{ old('istri_nik', $case->spouse_nik) }}" maxlength="16" inputmode="numeric" pattern="\d{16}"
                placeholder="16 digit sesuai KTP" class="input-field @error('istri_nik') border-red-400 @enderror" required>
              @error('istri_nik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nama Istri <span class="text-red-500">*</span></label>
              <input type="text" name="istri_name" value="{{ old('istri_name', $case->spouse_name) }}" placeholder="Nama sesuai KTP"
                class="input-field @error('istri_name') border-red-400 @enderror" required>
              @error('istri_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Istri <span class="text-red-500">*</span></label>
              <input type="text" name="istri_alamat" value="{{ old('istri_alamat', $case->spouse_alamat) }}" placeholder="Alamat sesuai KTP"
                class="input-field @error('istri_alamat') border-red-400 @enderror" required>
              @error('istri_alamat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RT/RW <span class="text-red-500">*</span></label>
                <input type="text" name="istri_rt_rw" value="{{ old('istri_rt_rw', $case->spouse_rt_rw) }}" placeholder="000/000"
                  class="input-field @error('istri_rt_rw') border-red-400 @enderror" required>
                @error('istri_rt_rw') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan <span class="text-red-500">*</span></label>
                <input type="text" name="istri_kelurahan" value="{{ old('istri_kelurahan', $case->spouse_kelurahan) }}"
                  class="input-field @error('istri_kelurahan') border-red-400 @enderror" required>
                @error('istri_kelurahan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
              <input type="text" name="istri_kecamatan" value="{{ old('istri_kecamatan', $case->spouse_kecamatan) }}"
                class="input-field @error('istri_kecamatan') border-red-400 @enderror" required>
              @error('istri_kecamatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ── DATA CERAI & KONTAK ─────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">2</span>
        <h2 class="font-semibold text-gray-800">Data Perceraian & Kontak</h2>
      </div>
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Putusan Cerai</label>
          <input type="date" name="divorce_date" value="{{ old('divorce_date', $case->divorce_date ? \Carbon\Carbon::parse($case->divorce_date)->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}"
            class="input-field @error('divorce_date') border-red-400 @enderror">
          @error('divorce_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Putusan PA</label>
          <input type="text" name="verdict_number" value="{{ old('verdict_number', $case->verdict_number) }}"
            placeholder="Contoh: 0123/Pdt.G/2025/PA.JS" class="input-field @error('verdict_number') border-red-400 @enderror">
          @error('verdict_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Institusi <span class="text-red-500">*</span></label>
          <select name="institution_id" class="input-field @error('institution_id') border-red-400 @enderror" required>
            <option value="">-- Pilih Institusi --</option>
            @foreach($institutions as $inst)
              <option value="{{ $inst->id }}" {{ old('institution_id', $case->institution_id) == $inst->id ? 'selected' : '' }}>
                {{ $inst->name }} ({{ $inst->type }})
              </option>
            @endforeach
          </select>
          @error('institution_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp <span class="text-red-500">*</span></label>
          <input type="tel" name="phone_wa" value="{{ old('phone_wa', $case->petitioner_phone) }}" placeholder="+62..."
            class="input-field @error('phone_wa') border-red-400 @enderror" required>
          @error('phone_wa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
          <textarea name="notes" placeholder="Catatan atau informasi tambahan jika ada..." rows="3"
            class="input-field @error('notes') border-red-400 @enderror">{{ old('notes', $case->notes) }}</textarea>
          @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    {{-- === Langkah 3: Pilih Jenis Cerai === --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">3</span>
        <h2 class="font-semibold text-gray-800">Pilih Jenis Cerai</h2>
      </div>
      <div class="p-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Cerai</label>
        <select name="cerai_type" id="cerai-type-select" class="input-field @error('cerai_type') border-red-400 @enderror" required>
          @foreach($ceraiOptions as $key => $option)
            <option value="{{ $key }}" {{ old('cerai_type', $case->cerai_type ?? 'cerai_normal') === $key ? 'selected' : '' }}
              data-docs="{{ count($option['docs']) }}">
              {{ $option['label'] }} ({{ count($option['docs']) }} dokumen)
            </option>
          @endforeach
        </select>
        @error('cerai_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        <p class="mt-2 text-xs text-gray-500">Dokumen yang perlu diunggah akan disesuaikan secara otomatis.</p>
      </div>
    </div>

    {{-- ── DOKUMEN (Dynamic) ─────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">4</span>
        <h2 class="font-semibold text-gray-800">Dokumen Pendukung</h2>
        <span class="text-xs text-gray-400 ml-auto">Maks. 10 MB per file - JPG, PNG, PDF</span>
      </div>
      <div class="p-6 space-y-6">
        {{-- Dokumen yang sudah ada --}}
        @if($case->documents->count() > 0)
          <div>
            <h3 class="font-semibold text-gray-800 mb-3">Dokumen yang Sudah Diupload</h3>
            <div class="space-y-2">
              @foreach($case->documents as $doc)
                <div class="doc-item">
                  <div class="flex-1">
                    <div class="font-medium text-sm text-gray-800">{{ $doc->original_name }}</div>
                    <div class="text-xs text-gray-500">{{ $doc->document_type }} &bull; {{ round($doc->size_bytes / 1024, 1) }} KB</div>
                  </div>
                  <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" name="remove_documents[]" value="{{ $doc->id }}" class="w-4 h-4 rounded">
                      <span class="text-red-600 hover:text-red-700 text-xs font-medium">Hapus</span>
                    </label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        {{-- File inputs directly in form - not hidden, triggered via JS --}}
        <div style="position:fixed;left:-9999px;top:-9999px;">
          @foreach($ceraiOptions as $key => $option)
            @foreach($option['docs'] as $docKey => $docLabel)
              @php $inputId = $key . '-' . $docKey; @endphp
              <input type="file" name="documents[{{ $docKey }}]" id="file-{{ $inputId }}" class="file-input" data-cerai-type="{{ $key }}" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, '{{ $inputId }}')">
            @endforeach
          @endforeach
        </div>

        {{-- Document panels - just UI, not form inputs --}}
        @foreach($ceraiOptions as $key => $option)
          <div data-cerai-panel="{{ $key }}" class="hidden-panel-only">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 md:p-6">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-5">
                <div>
                  <p class="text-[10px] uppercase tracking-[0.18em] text-gray-400 mb-2">{{ $option['label'] }}</p>
                  <h3 class="text-lg font-semibold text-gray-900 mb-1">Dokumen untuk {{ $option['label'] }}</h3>
                  <p class="text-sm text-gray-500 max-w-2xl">{{ $option['description'] }}</p>
                </div>
                <div class="rounded-full bg-emerald-100 text-emerald-700 px-3 py-1.5 text-xs font-semibold">
                  {{ count($option['docs']) }} dokumen
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($option['docs'] as $docKey => $docLabel)
                  @php
                    $inputId = $key . '-' . $docKey;
                    $normalizedDocType = \App\Services\DocumentTypeMapper::toCaseType($docKey);
                    $isUploaded = in_array($normalizedDocType, $uploadedDocTypes);
                  @endphp
                  <div>
                    <label class="block text-sm text-gray-700 mb-1">{{ $docLabel }}</label>
                    {{-- Upload area is ALWAYS clickable regardless of upload status --}}
                    <label for="file-{{ $inputId }}" class="doc-upload-area @if($isUploaded) has-file @endif" id="area-{{ $inputId }}">
                      <div id="placeholder-{{ $inputId }}" @if($isUploaded) class="hidden" @endif>
                        <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-1"></i>
                        <p class="text-sm text-gray-500">Klik untuk pilih file</p>
                      </div>
                      <div id="selected-{{ $inputId }}">
                        <i class="fas fa-check-circle text-emerald-500 text-2xl mb-1"></i>
                        <p class="text-sm font-medium text-emerald-700">@if($isUploaded) Sudah diupload @else<span id="filename-{{ $inputId }}"></span>@endif</p>
                        <p class="text-xs text-emerald-600" id="filesize-{{ $inputId }}">
                          @if($isUploaded)
                            Dokumen tersimpan - klik untuk ganti
                          @else
                            <span id="size-display-{{ $inputId }}"></span>
                          @endif
                        </p>
                      </div>
                    </label>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- ── PERSETUJUAN & BUTTONS ───────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
      <div class="mb-5">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="agreement" value="1" class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            {{ old('agreement') ? 'checked' : '' }} required>
          <span class="text-sm text-gray-600">
            Saya menyatakan bahwa seluruh data dan dokumen yang saya unggah adalah <strong>benar dan sah</strong>.
          </span>
        </label>
        @error('agreement') <p class="mt-1 text-xs text-red-600 ml-7">{{ $message }}</p> @enderror
      </div>

      <div class="flex gap-3">
        <a href="{{ route('dashboard.cases') }}" class="flex-1 py-3 bg-gray-100 text-gray-700 font-medium text-center rounded-xl hover:bg-gray-200 transition">
          <i class="fas fa-times mr-1"></i> Kembali
        </a>
        <button type="button" id="saveBtn" onclick="saveDraft()"
          class="flex-1 py-3 bg-[#8b6f47] text-white font-medium rounded-xl hover:bg-[#7a5c3c] transition disabled:opacity-60 disabled:cursor-not-allowed">Simpan Draft</button>
        <button type="button" id="submitBtn" onclick="console.log('SUBMIT BTN CLICKED'); submitDraft(event)"
          class="flex-1 py-3 bg-[#2d3a27] text-white font-bold rounded-xl hover:bg-[#1a2515] transition disabled:opacity-60 disabled:cursor-not-allowed">Kirim Pengajuan</button>
      </div>
    </div>

  </form>
</div>
@endsection

@php
  // Debug: show what document types are already uploaded
  $uploadedDocTypesJson = json_encode($uploadedDocTypes);
@endphp

@push('scripts')
<script>
const uploadedDocTypes = {{ Js::from($uploadedDocTypes) }};

function handleFileSelect(input, key) {
  const area  = document.getElementById('area-' + key);
  const ph    = document.getElementById('placeholder-' + key);
  const sel   = document.getElementById('selected-' + key);
  const fname = document.getElementById('filename-' + key);
  const fsize = document.getElementById('filesize-' + key);
  const disp  = document.getElementById('size-display-' + key);

  if (input.files && input.files[0]) {
    const f = input.files[0];
    const size = f.size < 1048576 ? (f.size / 1024).toFixed(1) + ' KB' : (f.size / 1048576).toFixed(2) + ' MB';

    fname.textContent = f.name;
    if (fsize) fsize.textContent = size;
    if (disp) disp.textContent = size;
    ph.classList.add('hidden');
    sel.classList.remove('hidden');
    area.classList.add('has-file');
  } else {
    ph.classList.remove('hidden');
    sel.classList.add('hidden');
    area.classList.remove('has-file');
  }
}

function syncCeraiPanels() {
  const select = document.getElementById('cerai-type-select');
  const activeType = select ? select.value : 'cerai_normal';

  document.querySelectorAll('[data-cerai-panel]').forEach(function(panel) {
    const isActive = panel.dataset.ceraiPanel === activeType;
    panel.classList.toggle('hidden-panel-only', !isActive);
  });
}

function saveDraft() {
  const btn = document.getElementById('saveBtn');
  const form = document.getElementById('draftForm');

  btn.disabled = true;
  btn.innerHTML = 'Menyimpan...';

  const formData = new FormData(form);

  // Remove _method field that causes PATCH conflict - use POST for submit
  formData.delete('_method');

  // Explicitly append all file inputs to FormData (handles inputs outside form or in hidden divs)
  const ceraiOptions = ['cerai_normal', 'cerai_mati', 'cerai_pindah', 'cerai_ghaib', 'cerai_hak_asuh'];
  const docTypes = ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH',
                     'AKTA_KEMATIAN', 'SURAT_KETERANGAN_AHLI_WARIS', 'SURAT_PINDAH',
                     'SURAT_KETERANGAN_GHAIB', 'AKTA_KELAHIRAN_ANAK'];

  ceraiOptions.forEach(ceraiType => {
    docTypes.forEach(docType => {
      const input = document.getElementById('file-' + ceraiType + '-' + docType);
      if (input && input.files && input.files[0]) {
        formData.append('documents[' + docType + ']', input.files[0]);
      }
    });
  });

  console.log('=== Save Draft Debug ===');
  for (let [key, value] of formData.entries()) {
    console.log(key + ':', value instanceof File ? value.name + ' (' + value.size + ' bytes)' : value);
  }

  fetch('{{ route("dashboard.cases.update-draft", $case->id) }}', {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
    },
    credentials: 'same-origin'
  })
  .then(res => {
    console.log('Response status:', res.status);
    if (res.redirected) {
      console.log('Redirected to:', res.url);
      window.location.href = res.url;
      return null;
    }
    return res.json().catch(() => null);
  })
  .then(data => {
    console.log('Response data:', data);
    if (!data) return;
    if (data.success || data.error) {
      window.location.href = '{{ route("dashboard.cases.edit-draft", $case->id) }}?saved=1';
    } else if (data.errors) {
      btn.disabled = false;
      btn.innerHTML = 'Simpan Draft';
      const msgs = Object.values(data.errors).flat().join('\n');
      alert('Error:\n' + msgs);
    } else {
      window.location.reload();
    }
  })
  .catch(err => {
    console.error('Save draft error:', err);
    btn.disabled = false;
    btn.innerHTML = 'Simpan Draft';
    alert('Gagal menyimpan draft: ' + err.message);
  });
}

function submitDraft(e) {
  e.preventDefault();
  console.log('=== submitDraft called ===');
  const btn = document.getElementById('submitBtn');
  const form = document.getElementById('draftForm');

  const select = document.getElementById('cerai-type-select');
  const activeType = select ? select.value : 'cerai_normal';

  // Skip document validation - let server handle it

  const agreementCheckbox = form.querySelector('input[name="agreement"]');
  if (!agreementCheckbox.checked) {
    btn.disabled = false;
    btn.innerHTML = 'Kirim Pengajuan';
    alert('Anda harus menyetujui pernyataan kebenaran data terlebih dahulu');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = 'Mengirim...';

  const formData = new FormData(form);

  // Remove _method field that causes PATCH conflict - use POST for submit
  formData.delete('_method');

  // Explicitly append all file inputs to FormData
  const ceraiOptions = ['cerai_normal', 'cerai_mati', 'cerai_pindah', 'cerai_ghaib', 'cerai_hak_asuh'];
  const docTypes = ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH',
                     'AKTA_KEMATIAN', 'SURAT_KETERANGAN_AHLI_WARIS', 'SURAT_PINDAH',
                     'SURAT_KETERANGAN_GHAIB', 'AKTA_KELAHIRAN_ANAK'];

  ceraiOptions.forEach(ceraiType => {
    docTypes.forEach(docType => {
      const input = document.getElementById('file-' + ceraiType + '-' + docType);
      if (input && input.files && input.files[0]) {
        formData.append('documents[' + docType + ']', input.files[0]);
      }
    });
  });

  console.log('=== FormData Debug ===');
  for (let [key, value] of formData.entries()) {
    console.log(key + ':', value instanceof File ? value.name + ' (' + value.size + ' bytes)' : value);
  }

  fetch('{{ route("dashboard.cases.submit-draft", $case->id) }}', {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
    },
    credentials: 'same-origin'
  })
  .then(res => {
    console.log('Response status:', res.status, 'statusText:', res.statusText);
    console.log('Content-Type:', res.headers.get('content-type'));
    console.log('Redirected:', res.redirected);
    if (res.redirected) {
      console.log('Redirected to:', res.url);
      window.location.href = res.url;
      return null;
    }
    const contentType = res.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
      return res.json().catch(() => ({ success: false, errors: ['Invalid JSON response from server'] }));
    } else {
      return res.text().then(text => {
        console.log('Non-JSON response preview:', text.substring(0, 1000));
        if (text.includes('<!DOCTYPE') || text.includes('<html')) {
          return { success: false, errors: ['Server error (HTTP ' + res.status + '). Cek console untuk detail.'] };
        }
        return { success: false, errors: [text.substring(0, 500)] };
      }).catch(() => ({ success: false, errors: ['Unknown error parsing response'] }));
    }
  })
  .then(data => {
    console.log('Response data:', JSON.stringify(data));
    if (!data) {
      btn.disabled = false;
      btn.innerHTML = 'Kirim Pengajuan';
      alert('Terjadi kesalahan: Response tidak valid dari server');
      return;
    }
    if (data.success) {
      window.location.href = data.redirect || '{{ route("dashboard.cases.show", $case->id) }}';
    } else if (data.errors) {
      btn.disabled = false;
      btn.innerHTML = 'Kirim Pengajuan';
      const errMsg = Array.isArray(data.errors) ? data.errors.join('\n') : Object.values(data.errors).flat().join('\n');
      alert('Error:\n' + errMsg);
    } else if (data.redirect) {
      window.location.href = data.redirect;
    } else {
      btn.disabled = false;
      btn.innerHTML = 'Kirim Pengajuan';
      alert('Terjadi kesalahan: ' + (data.message || 'Unknown error'));
    }
  })
  .catch(err => {
    console.error('Submit error:', err);
    btn.disabled = false;
    btn.innerHTML = 'Kirim Pengajuan';
    alert('Gagal mengirim pengajuan: ' + err.message);
  });
}

document.addEventListener('DOMContentLoaded', function() {
  syncCeraiPanels();

  const select = document.getElementById('cerai-type-select');
  if (select) {
    select.addEventListener('change', syncCeraiPanels);
  }
});
</script>
@endpush
