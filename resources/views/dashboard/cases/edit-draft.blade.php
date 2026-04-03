@extends('layouts.admin')

@section('title', 'Edit Draft Kasus')
@section('page-title', 'Edit Draft Kasus')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.cases') }}" class="hover:text-primary">Kasus</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Edit Draft</span>
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
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
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

  @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm">
      <i class="fas fa-check-circle mr-1"></i>
      <strong>{{ session('success') }}</strong>
    </div>
  @endif

  <form id="draftForm" method="POST" action="{{ route('dashboard.cases.update-draft', $case->id) }}" enctype="multipart/form-data" class="space-y-8">
    @csrf
    @method('PATCH')
    <input type="hidden" id="formAction" value="update">

    {{-- ── DATA PASANGAN ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">1</span>
        <h2 class="font-semibold text-gray-800">Data Pasangan</h2>
        <span class="text-xs text-gray-400 ml-auto">Maks. 5 MB per file - JPG, PNG, PDF</span>
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

    {{-- ── DATA CERAI ─────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">2</span>
        <h2 class="font-semibold text-gray-800">Data Perceraian & Kontak</h2>
      </div>
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Putusan Cerai</label>
          <input type="date" name="divorce_date" value="{{ old('divorce_date', $case->divorce_date) }}" max="{{ date('Y-m-d') }}"
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

    {{-- ── DOKUMEN ─────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">3</span>
        <h2 class="font-semibold text-gray-800">Dokumen Pendukung</h2>
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
                    <div class="text-xs text-gray-500">{{ $doc->document_type }} • {{ round($doc->size_bytes / 1024, 1) }} KB</div>
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

        {{-- Form upload dokumen tambahan --}}
        <div>
          <h3 class="font-semibold text-gray-800 mb-3">{{ $case->documents->count() > 0 ? 'Tambah Dokumen' : 'Upload Dokumen' }}</h3>
          @php
            $divorceDocs = [
              'KTP_SUAMI' => 'Upload KTP Suami',
              'KTP_ISTRI' => 'Upload KTP Istri',
              'KK' => 'Upload Kartu Keluarga (KK)',
              'AKTA_CERAI' => 'Upload Akta Cerai / SKBF',
              'PUTUSAN_PA' => 'Upload Berkas Putusan Cerai',
              'AKTA_NIKAH' => 'Upload Buku Nikah',
            ];
          @endphp
          @foreach($divorceDocs as $key => $label)
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
              <div class="doc-upload-area" id="area-{{ $key }}" onclick="document.getElementById('file-{{ $key }}').click()">
                <input type="file" name="documents[{{ $key }}]" id="file-{{ $key }}" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                  onchange="handleFileSelect(this, '{{ $key }}')">
                <div id="placeholder-{{ $key }}">
                  <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-1"></i>
                  <p class="text-sm text-gray-500">Klik untuk pilih file</p>
                </div>
                <div id="selected-{{ $key }}" class="hidden">
                  <i class="fas fa-file text-emerald-500 text-2xl mb-1"></i>
                  <p class="text-sm font-medium text-emerald-700" id="filename-{{ $key }}"></p>
                  <p class="text-xs text-emerald-600" id="filesize-{{ $key }}"></p>
                </div>
              </div>
            </div>
          @endforeach
        </div>
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
          class="flex-1 py-3 bg-amber-500 text-white font-medium rounded-xl hover:bg-amber-600 transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
          <i class="fas fa-save mr-1"></i>
          Simpan Draft
        </button>
        <button type="button" id="submitBtn" onclick="submitDraft()"
          class="flex-1 py-4 bg-emerald-600 text-white font-bold text-base rounded-xl hover:bg-emerald-700 transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
          <i class="fas fa-paper-plane"></i>
          Kirim Pengajuan
        </button>
      </div>
    </div>

  </form>
</div>
@endsection

@push('scripts')
<script>
function handleFileSelect(input, key) {
  const area  = document.getElementById('area-' + key);
  const ph    = document.getElementById('placeholder-' + key);
  const sel   = document.getElementById('selected-' + key);
  const fname = document.getElementById('filename-' + key);
  const fsize = document.getElementById('filesize-' + key);

  if (input.files && input.files[0]) {
    const f = input.files[0];
    const size = f.size < 1048576 ? (f.size / 1024).toFixed(1) + ' KB' : (f.size / 1048576).toFixed(2) + ' MB';

    fname.textContent = f.name;
    fsize.textContent = size;
    ph.classList.add('hidden');
    sel.classList.remove('hidden');
    area.classList.add('has-file');
  } else {
    ph.classList.remove('hidden');
    sel.classList.add('hidden');
    area.classList.remove('has-file');
  }
}

function saveDraft() {
  const btn = document.getElementById('saveBtn');
  const form = document.getElementById('draftForm');
  
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>Menyimpan...';
  
  // Set action untuk update draft
  document.getElementById('formAction').value = 'update';
  form.method = 'POST';
  form.action = '{{ route("dashboard.cases.update-draft", $case->id) }}';
  
  // Uncheck agreement checkbox
  const agreementCheckbox = form.querySelector('input[name="agreement"]');
  agreementCheckbox.checked = false;
  
  form.submit();
}

function submitDraft() {
  const btn = document.getElementById('submitBtn');
  const form = document.getElementById('draftForm');
  
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>Mengirim...';
  
  // Validate agreement
  const agreementCheckbox = form.querySelector('input[name="agreement"]');
  if (!agreementCheckbox.checked) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Pengajuan';
    alert('Anda harus menyetujui pernyataan kebenaran data terlebih dahulu');
    return;
  }
  
  // Change form action to submit-draft
  document.getElementById('formAction').value = 'submit';
  form.method = 'POST';
  form.action = '{{ route("dashboard.cases.submit-draft", $case->id) }}';
  
  // Remove PATCH method override
  const patchInput = form.querySelector('input[name="_method"]');
  if (patchInput) {
    patchInput.remove();
  }
  
  form.submit();
}
</script>
@endpush
