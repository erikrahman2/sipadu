@extends('layouts.admin')

@section('title', 'Buat Kasus Baru')
@section('page-title', 'Buat Kasus Baru')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.cases') }}" class="hover:text-primary">Kasus</a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Buat Baru</span>
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
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-500 mb-4 shadow-lg">
      <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
    </div>
    <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Pengajuan Pembaruan Dokumen</h1>
    <p class="text-gray-500 max-w-xl mx-auto">
      Fokus input pada data KTP suami dan istri agar validasi OCR lebih akurat.
    </p>
  </div>

  @if($errors->any() && !$errors->has('suami_nik') && !$errors->has('istri_nik'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-3 text-red-700 text-sm">
      <i class="fas fa-exclamation-circle mr-1"></i>
      Terdapat kesalahan pada form. Mohon periksa kembali.
    </div>
  @endif

  @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm">
      <i class="fas fa-check-circle mr-1"></i>
      <strong>{{ session('success') }}</strong>
    </div>
  @endif

  @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-5 py-3 rounded-xl text-sm">
      <i class="fas fa-exclamation-triangle mr-1"></i>
      <strong>{{ session('error') }}</strong>
    </div>
  @endif

  <form id="caseForm" method="POST" action="{{ route('dashboard.cases.store') }}" enctype="multipart/form-data" class="space-y-8">
    @csrf

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">1</span>
        <h2 class="font-semibold text-gray-800">Input Data Pasangan</h2>
        <span class="text-xs text-gray-400 ml-auto">Maks. 5 MB per file - JPG, PNG, PDF</span>
      </div>
      <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 space-y-4">
            <h3 class="font-semibold text-emerald-800">Data Suami</h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">NIK Suami <span class="text-red-500">*</span></label>
              <input type="text" name="suami_nik" value="{{ old('suami_nik') }}" maxlength="16" inputmode="numeric" pattern="\d{16}"
                placeholder="16 digit sesuai KTP" class="input-field @error('suami_nik') border-red-400 @enderror" required>
              @error('suami_nik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nama Suami <span class="text-red-500">*</span></label>
              <input type="text" name="suami_name" value="{{ old('suami_name') }}" placeholder="Nama sesuai KTP"
                class="input-field @error('suami_name') border-red-400 @enderror" required>
              @error('suami_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Suami <span class="text-red-500">*</span></label>
              <input type="text" name="suami_alamat" value="{{ old('suami_alamat') }}" placeholder="Alamat sesuai KTP"
                class="input-field @error('suami_alamat') border-red-400 @enderror" required>
              @error('suami_alamat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RT/RW <span class="text-red-500">*</span></label>
                <input type="text" name="suami_rt_rw" value="{{ old('suami_rt_rw') }}" placeholder="000/000"
                  class="input-field @error('suami_rt_rw') border-red-400 @enderror" required>
                @error('suami_rt_rw') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan <span class="text-red-500">*</span></label>
                <input type="text" name="suami_kelurahan" value="{{ old('suami_kelurahan') }}"
                  class="input-field @error('suami_kelurahan') border-red-400 @enderror" required>
                @error('suami_kelurahan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
              <input type="text" name="suami_kecamatan" value="{{ old('suami_kecamatan') }}"
                class="input-field @error('suami_kecamatan') border-red-400 @enderror" required>
              @error('suami_kecamatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
          </div>

          <div class="rounded-xl border border-teal-100 bg-teal-50/40 p-4 space-y-4">
            <h3 class="font-semibold text-teal-800">Data Istri</h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">NIK Istri <span class="text-red-500">*</span></label>
              <input type="text" name="istri_nik" value="{{ old('istri_nik') }}" maxlength="16" inputmode="numeric" pattern="\d{16}"
                placeholder="16 digit sesuai KTP" class="input-field @error('istri_nik') border-red-400 @enderror" required>
              @error('istri_nik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nama Istri <span class="text-red-500">*</span></label>
              <input type="text" name="istri_name" value="{{ old('istri_name') }}" placeholder="Nama sesuai KTP"
                class="input-field @error('istri_name') border-red-400 @enderror" required>
              @error('istri_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Istri <span class="text-red-500">*</span></label>
              <input type="text" name="istri_alamat" value="{{ old('istri_alamat') }}" placeholder="Alamat sesuai KTP"
                class="input-field @error('istri_alamat') border-red-400 @enderror" required>
              @error('istri_alamat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RT/RW <span class="text-red-500">*</span></label>
                <input type="text" name="istri_rt_rw" value="{{ old('istri_rt_rw') }}" placeholder="000/000"
                  class="input-field @error('istri_rt_rw') border-red-400 @enderror" required>
                @error('istri_rt_rw') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan <span class="text-red-500">*</span></label>
                <input type="text" name="istri_kelurahan" value="{{ old('istri_kelurahan') }}"
                  class="input-field @error('istri_kelurahan') border-red-400 @enderror" required>
                @error('istri_kelurahan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
              <input type="text" name="istri_kecamatan" value="{{ old('istri_kecamatan') }}"
                class="input-field @error('istri_kecamatan') border-red-400 @enderror" required>
              @error('istri_kecamatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
          @php
            $pairDocs = [
              'KTP_SUAMI' => 'Upload KTP Suami *',
              'KTP_ISTRI' => 'Upload KTP Istri *',
              'KK' => 'Upload Kartu Keluarga (KK)',
            ];
          @endphp
          @foreach($pairDocs as $key => $label)
            <div class="{{ $key === 'KK' ? 'md:col-span-2' : '' }}">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
              <div class="doc-upload-area" id="area-{{ $key }}" onclick="document.getElementById('file-{{ $key }}').click()">
                <input type="file" name="documents[{{ $key }}]" id="file-{{ $key }}" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                  onchange="handleFileSelect(this, '{{ $key }}')">
                <div id="placeholder-{{ $key }}">
                  <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-1"></i>
                  <p class="text-sm text-gray-500">Klik untuk pilih file</p>
                </div>
                <div id="selected-{{ $key }}" class="hidden">
                  <i class="fas fa-check-circle text-emerald-500 text-2xl mb-1"></i>
                  <p class="text-sm text-emerald-700 font-medium" id="filename-{{ $key }}"></p>
                  <p class="text-xs text-gray-500" id="filesize-{{ $key }}"></p>
                </div>
              </div>
              @error('documents.' . $key)
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>
          @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-gray-100 pt-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Nomor WhatsApp Aktif <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center">
              <span class="px-3 py-3 bg-gray-100 border border-r-0 border-gray-300 rounded-l-xl text-gray-500 text-sm">+62</span>
              <input type="tel" name="phone_wa" value="{{ old('phone_wa') }}" inputmode="numeric" placeholder="81234567890"
                class="flex-1 px-4 py-3 border border-gray-300 rounded-r-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone_wa') border-red-400 @enderror"
                required>
            </div>
            @error('phone_wa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-400">Token tracking akan dikirimkan ke nomor ini melalui WhatsApp.</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Pengadilan Agama / Institusi <span class="text-red-500">*</span>
            </label>
            <select name="institution_id" required class="input-field @error('institution_id') border-red-400 @enderror">
              <option value="">Pilih Institusi</option>
              @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" {{ old('institution_id') == $inst->id ? 'selected' : '' }}>
                  {{ $inst->name }} ({{ $inst->type }})
                </option>
              @endforeach
            </select>
            @error('institution_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">2</span>
        <h2 class="font-semibold text-gray-800">Data Cerai</h2>
      </div>
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Putusan Cerai</label>
          <input type="date" name="divorce_date" value="{{ old('divorce_date') }}" max="{{ date('Y-m-d') }}"
            class="input-field @error('divorce_date') border-red-400 @enderror">
          @error('divorce_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Putusan PA</label>
          <input type="text" name="verdict_number" value="{{ old('verdict_number') }}"
            placeholder="Contoh: 0123/Pdt.G/2025/PA.JS" class="input-field @error('verdict_number') border-red-400 @enderror">
          @error('verdict_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        @php
          $divorceDocs = [
            'PUTUSAN_PA' => 'Upload Berkas Putusan Cerai',
            'AKTA_NIKAH' => 'Upload Buku Nikah',
          ];
        @endphp
        @foreach($divorceDocs as $key => $label)
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
            <div class="doc-upload-area" id="area-{{ $key }}" onclick="document.getElementById('file-{{ $key }}').click()">
              <input type="file" name="documents[{{ $key }}]" id="file-{{ $key }}" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, '{{ $key }}')">
              <div id="placeholder-{{ $key }}">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-1"></i>
                <p class="text-sm text-gray-500">Klik untuk pilih file</p>
              </div>
              <div id="selected-{{ $key }}" class="hidden">
                <i class="fas fa-check-circle text-emerald-500 text-2xl mb-1"></i>
                <p class="text-sm text-emerald-700 font-medium" id="filename-{{ $key }}"></p>
                <p class="text-xs text-gray-500" id="filesize-{{ $key }}"></p>
              </div>
            </div>
            @error('documents.' . $key)
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
        @endforeach

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
          <textarea name="notes" rows="3" placeholder="Keterangan tambahan (opsional)..." class="input-field resize-none">{{ old('notes') }}</textarea>
          @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">3</span>
        <h2 class="font-semibold text-gray-800">Upload Berkas Lainnya</h2>
        <span class="text-xs text-gray-400 ml-auto">Maks. 5 MB per file � JPG, PNG, PDF</span>
      </div>
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @php
          $otherDocs = [
            'AKTA_CERAI' => 'Akta Perceraian',
            'SURAT_PENGANTAR' => 'Surat Pengantar',
            'OTHER' => 'Dokumen Lainnya',
          ];
        @endphp
        @foreach($otherDocs as $key => $label)
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
            <div class="doc-upload-area" id="area-{{ $key }}" onclick="document.getElementById('file-{{ $key }}').click()">
              <input type="file" name="documents[{{ $key }}]" id="file-{{ $key }}" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, '{{ $key }}')">
              <div id="placeholder-{{ $key }}">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-1"></i>
                <p class="text-sm text-gray-500">Klik untuk pilih file</p>
              </div>
              <div id="selected-{{ $key }}" class="hidden">
                <i class="fas fa-check-circle text-emerald-500 text-2xl mb-1"></i>
                <p class="text-sm text-emerald-700 font-medium" id="filename-{{ $key }}"></p>
                <p class="text-xs text-gray-500" id="filesize-{{ $key }}"></p>
              </div>
            </div>
            @error('documents.' . $key)
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
        @endforeach
      </div>
    </div>

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
          <i class="fas fa-times mr-1"></i> Batal
        </a>
        <button type="button" id="draftBtn" onclick="saveDraft()"
          class="flex-1 py-3 bg-amber-500 text-white font-medium rounded-xl hover:bg-amber-600 transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
          <i class="fas fa-save mr-1"></i>
          Simpan Draft
        </button>
        <button type="submit" id="submitBtn"
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
  const btn = document.getElementById('draftBtn');
  const form = document.getElementById('caseForm');
  
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Menyimpan...';
  
  // Set action untuk draft
  form.action = '{{ route("dashboard.cases.save-draft") }}';
  form.submit();
}

document.getElementById('caseForm').addEventListener('submit', function () {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Mengirim...';
});
</script>
@endpush
