@extends('layouts.public')

@section('title', 'Pengajuan Pembaruan Dokumen - SiPadu')

@push('styles')
<style>
  .doc-upload-area {
    @apply border-2 border-dashed border-gray-300 rounded-xl p-5 text-center cursor-pointer transition hover:border-emerald-400 hover:bg-emerald-50;
  }
  .doc-upload-area.has-file {
    @apply border-emerald-400 bg-emerald-50;
  }
  .step-badge {
    @apply inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-600 text-[#F7F4EB] text-sm  flex-shrink-0;
  }
  .input-field {
    @apply w-full px-4 py-3 border border-gray-300 rounded-xl text-[#31110F] placeholder-[#31110F] focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent;
  }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

  @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-3 text-[#31110F] text-sm">
      Terdapat kesalahan pada form. Mohon periksa kembali.
    </div>
  @endif

  @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-[#31110F] px-5 py-3 rounded-xl text-sm">
      <strong>{{ session('success') }}</strong>
    </div>
  @endif

  @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-[#31110F] px-5 py-3 rounded-xl text-sm">
      <strong>{{ session('error') }}</strong>
    </div>
  @endif

  <form id="submissionForm" method="POST" action="{{ route('public.submit.store') }}" enctype="multipart/form-data" class="space-y-8">
    @csrf

    {{-- === Langkah 1: Input Data Pasangan === --}}
    <div class="bg-[#F7F4EB] rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">1</span>
        <h2 class=" text-[#31110F]">Input Data Pasangan</h2>
        <span class="text-xs text-[#31110F] ml-auto">Maks. 5 MB per file - JPG, PNG, PDF</span>
      </div>
      <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          {{-- Data Suami --}}
          <div class="space-y-4">
            <h3 class=" text-[#31110F] text-lg">Data Suami</h3>
            <div>
              <label for="nik_suami" class="block text-sm  text-[#31110F] mb-1">NIK Suami <span class="text-[#31110F]">*</span></label>
              <input id="nik_suami" type="text" name="nik_suami" value="{{ old('nik_suami') }}" maxlength="16" inputmode="numeric" pattern="\d{16}"
                placeholder="16 digit sesuai KTP" class="input-field @error('nik_suami') border-red-400 @enderror" required>
              @error('nik_suami') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            </div>
            <div>
              <label for="nama_suami" class="block text-sm  text-[#31110F] mb-1">Nama Suami <span class="text-[#31110F]">*</span></label>
              <input id="nama_suami" type="text" name="nama_suami" value="{{ old('nama_suami') }}" placeholder="Nama sesuai KTP"
                class="input-field @error('nama_suami') border-red-400 @enderror" required>
              @error('nama_suami') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            </div>
            <div>
              <label for="alamat_suami" class="block text-sm  text-[#31110F] mb-1">Alamat Suami <span class="text-[#31110F]">*</span></label>
              <input id="alamat_suami" type="text" name="alamat_suami" value="{{ old('alamat_suami') }}" placeholder="Alamat sesuai KTP"
                class="input-field @error('alamat_suami') border-red-400 @enderror" required>
              @error('alamat_suami') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label for="rt_rw_suami" class="block text-sm  text-[#31110F] mb-1">RT/RW <span class="text-[#31110F]">*</span></label>
                <input id="rt_rw_suami" type="text" name="rt_rw_suami" value="{{ old('rt_rw_suami') }}" placeholder="000/000"
                  class="input-field @error('rt_rw_suami') border-red-400 @enderror" required>
                @error('rt_rw_suami') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
              </div>
              <div class="col-span-2">
                <label for="kelurahan_suami" class="block text-sm  text-[#31110F] mb-1">Kelurahan <span class="text-[#31110F]">*</span></label>
                <input id="kelurahan_suami" type="text" name="kelurahan_suami" value="{{ old('kelurahan_suami') }}"
                  class="input-field @error('kelurahan_suami') border-red-400 @enderror" required>
                @error('kelurahan_suami') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
              </div>
            </div>
            <div>
              <label for="kecamatan_suami" class="block text-sm  text-[#31110F] mb-1">Kecamatan <span class="text-[#31110F]">*</span></label>
              <input id="kecamatan_suami" type="text" name="kecamatan_suami" value="{{ old('kecamatan_suami') }}"
                class="input-field @error('kecamatan_suami') border-red-400 @enderror" required>
              @error('kecamatan_suami') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            </div>
          </div>

          {{-- Data Istri --}}
          <div class="space-y-4">
            <h3 class=" text-[#31110F] text-lg">Data Istri</h3>
            <div>
              <label for="nik_istri" class="block text-sm  text-[#31110F] mb-1">NIK Istri <span class="text-[#31110F]">*</span></label>
              <input id="nik_istri" type="text" name="nik_istri" value="{{ old('nik_istri') }}" maxlength="16" inputmode="numeric" pattern="\d{16}"
                placeholder="16 digit sesuai KTP" class="input-field @error('nik_istri') border-red-400 @enderror" required>
              @error('nik_istri') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            </div>
            <div>
              <label for="nama_istri" class="block text-sm  text-[#31110F] mb-1">Nama Istri <span class="text-[#31110F]">*</span></label>
              <input id="nama_istri" type="text" name="nama_istri" value="{{ old('nama_istri') }}" placeholder="Nama sesuai KTP"
                class="input-field @error('nama_istri') border-red-400 @enderror" required>
              @error('nama_istri') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            </div>
            <div>
              <label for="alamat_istri" class="block text-sm  text-[#31110F] mb-1">Alamat Istri <span class="text-[#31110F]">*</span></label>
              <input id="alamat_istri" type="text" name="alamat_istri" value="{{ old('alamat_istri') }}" placeholder="Alamat sesuai KTP"
                class="input-field @error('alamat_istri') border-red-400 @enderror" required>
              @error('alamat_istri') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label for="rt_rw_istri" class="block text-sm  text-[#31110F] mb-1">RT/RW <span class="text-[#31110F]">*</span></label>
                <input id="rt_rw_istri" type="text" name="rt_rw_istri" value="{{ old('rt_rw_istri') }}" placeholder="000/000"
                  class="input-field @error('rt_rw_istri') border-red-400 @enderror" required>
                @error('rt_rw_istri') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
              </div>
              <div class="col-span-2">
                <label for="kelurahan_istri" class="block text-sm  text-[#31110F] mb-1">Kelurahan <span class="text-[#31110F]">*</span></label>
                <input id="kelurahan_istri" type="text" name="kelurahan_istri" value="{{ old('kelurahan_istri') }}"
                  class="input-field @error('kelurahan_istri') border-red-400 @enderror" required>
                @error('kelurahan_istri') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
              </div>
            </div>
            <div>
              <label for="kecamatan_istri" class="block text-sm  text-[#31110F] mb-1">Kecamatan <span class="text-[#31110F]">*</span></label>
              <input id="kecamatan_istri" type="text" name="kecamatan_istri" value="{{ old('kecamatan_istri') }}"
                class="input-field @error('kecamatan_istri') border-red-400 @enderror" required>
              @error('kecamatan_istri') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            </div>
          </div>
        </div>

        {{-- Upload KTP --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
          <div>
            <label for="file-KTP_SUAMI" class="block text-sm  text-[#31110F] mb-1">Upload KTP Suami <span class="text-[#31110F]">*</span></label>
            <div class="doc-upload-area" id="area-KTP_SUAMI" onclick="document.getElementById('file-KTP_SUAMI').click()">
              <input type="file" name="documents[KTP_SUAMI]" id="file-KTP_SUAMI" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, 'KTP_SUAMI')">
              <div id="placeholder-KTP_SUAMI">
                <p class="text-sm text-[#31110F]">Klik untuk pilih file</p>
              </div>
              <div id="selected-KTP_SUAMI" class="hidden">
                <p class="text-sm text-[#31110F] " id="filename-KTP_SUAMI"></p>
                <p class="text-xs text-[#31110F]" id="filesize-KTP_SUAMI"></p>
              </div>
            </div>
            @error('documents.KTP_SUAMI') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
          </div>

          <div>
            <label for="file-KTP_ISTRI" class="block text-sm  text-[#31110F] mb-1">Upload KTP Istri <span class="text-[#31110F]">*</span></label>
            <div class="doc-upload-area" id="area-KTP_ISTRI" onclick="document.getElementById('file-KTP_ISTRI').click()">
              <input type="file" name="documents[KTP_ISTRI]" id="file-KTP_ISTRI" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, 'KTP_ISTRI')">
              <div id="placeholder-KTP_ISTRI">
                <p class="text-sm text-[#31110F]">Klik untuk pilih file</p>
              </div>
              <div id="selected-KTP_ISTRI" class="hidden">
                <p class="text-sm text-[#31110F] " id="filename-KTP_ISTRI"></p>
                <p class=\"text-xs text-[#31110F]\" id=\"filesize-KTP_ISTRI\"></p>
              </div>
            </div>
            @error('documents.KTP_ISTRI') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-gray-100 pt-4">
          <div class="md:col-span-2">
            <label for="phone_wa" class="block text-sm  text-[#31110F] mb-1">Nomor WhatsApp Aktif <span class="text-[#31110F]">*</span></label>
            <div class="flex items-center">
              <span class="px-3 py-3 bg-gray-100 border border-r-0 border-gray-300 rounded-l-xl text-[#31110F] text-sm ">+62</span>
              <input id="phone_wa" type="tel" name="phone_wa" value="{{ old('phone_wa') }}" inputmode="numeric" placeholder="812345678 atau 08123456789"
                class="flex-1 px-4 py-3 border border-gray-300 rounded-r-xl text-[#31110F] focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('phone_wa') border-red-400 @enderror" required>
            </div>
            @error('phone_wa') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-[#31110F]">
              Hanya masukkan angka tanpa +62. Contoh: <code class="bg-gray-100 px-1 rounded">812345678</code> atau <code class="bg-gray-100 px-1 rounded">08123456789</code>
            </p>
          </div>

          <div class="md:col-span-2">
            <label for="institution_id" class="block text-sm  text-[#31110F] mb-1">Pengadilan Agama / Institusi <span class="text-[#31110F]">*</span></label>
            <select id="institution_id" name="institution_id" required class="input-field @error('institution_id') border-red-400 @enderror">
              <option value="">Pilih Institusi</option>
              @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" {{ old('institution_id') == $inst->id ? 'selected' : '' }}>
                  {{ $inst->name }} ({{ $inst->type }})
                </option>
              @endforeach
            </select>
            @error('institution_id') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- === Langkah 2: Data Cerai === --}}
    <div class="bg-[#F7F4EB] rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">2</span>
        <h2 class=" text-[#31110F]">Data Cerai</h2>
      </div>
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label for="divorce_date" class="block text-sm  text-[#31110F] mb-1">Tanggal Putusan Cerai</label>
          <input id="divorce_date" type="date" name="divorce_date" value="{{ old('divorce_date') }}" max="{{ date('Y-m-d') }}"
            class="input-field @error('divorce_date') border-red-400 @enderror">
          @error('divorce_date') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
        </div>

        <div>
          <label for="verdict_number" class="block text-sm  text-[#31110F] mb-1">Nomor Putusan PA</label>
          <input id="verdict_number" type="text" name="verdict_number" value="{{ old('verdict_number') }}"
            placeholder="Contoh: 0123/Pdt.G/2025/PA.JS" class="input-field @error('verdict_number') border-red-400 @enderror">
          @error('verdict_number') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
        </div>

        @php
          $divorceDocs = [
            'PUTUSAN_PA' => 'Upload Berkas Putusan Cerai',
            'AKTA_NIKAH' => 'Upload Buku Nikah',
          ];
        @endphp
        @foreach($divorceDocs as $key => $label)
          <div>
            <label for="file-{{ $key }}" class="block text-sm  text-[#31110F] mb-1">{{ $label }}</label>
            <div class="doc-upload-area" id="area-{{ $key }}" onclick="document.getElementById('file-{{ $key }}').click()">
              <input type="file" name="documents[{{ $key }}]" id="file-{{ $key }}" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, '{{ $key }}')">
              <div id="placeholder-{{ $key }}">
                <p class="text-sm text-[#31110F]">Klik untuk pilih file</p>
              </div>
              <div id="selected-{{ $key }}" class="hidden">
                <p class="text-sm text-[#31110F] " id="filename-{{ $key }}"></p>
                <p class="text-xs text-[#31110F]" id="filesize-{{ $key }}"></p>
              </div>
            </div>
            @error('documents.' . $key) <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
          </div>
        @endforeach

        <div class="md:col-span-2">
          <label for="notes" class="block text-sm  text-[#31110F] mb-1">Catatan Tambahan</label>
          <textarea id="notes" name="notes" rows="3" placeholder="Keterangan tambahan (opsional)..." class="input-field resize-none">{{ old('notes') }}</textarea>
          @error('notes') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    {{-- === Langkah 3: Upload Berkas Lainnya === --}}
    <div class="bg-[#F7F4EB] rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">3</span>
        <h2 class=" text-[#31110F]">Upload Berkas Lainnya</h2>
        <span class="text-xs text-[#31110F] ml-auto">Maks. 10 MB per file - JPG, PNG, PDF</span>
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
            <label for="file-{{ $key }}" class="block text-sm  text-[#31110F] mb-1">{{ $label }}</label>
            <div class="doc-upload-area" id="area-{{ $key }}" onclick="document.getElementById('file-{{ $key }}').click()">
              <input type="file" name="documents[{{ $key }}]" id="file-{{ $key }}" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, '{{ $key }}')">
              <div id="placeholder-{{ $key }}">
                <p class="text-sm text-[#31110F]">Klik untuk pilih file</p>
              </div>
              <div id="selected-{{ $key }}" class="hidden">
                <p class="text-sm text-[#31110F] " id="filename-{{ $key }}"></p>
                <p class="text-xs text-[#31110F]" id="filesize-{{ $key }}"></p>
              </div>
            </div>
            @error('documents.' . $key) <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
          </div>
        @endforeach
      </div>
    </div>

    {{-- === Pernyataan & Submit === --}}
    <div class="bg-[#F7F4EB] rounded-2xl shadow-sm border border-gray-100 p-6">
      <div class="mb-5">
        <label for="agreement" class="flex items-start gap-3 cursor-pointer">
          <input id="agreement" type="checkbox" name="agreement" value="1" class="mt-1 w-4 h-4 rounded border-gray-300 text-[#31110F] focus:ring-emerald-500" {{ old('agreement') ? 'checked' : '' }}/>
          <span class="text-sm text-[#31110F]">
            Saya menyatakan bahwa seluruh data dan dokumen yang saya unggah adalah <strong>benar dan sah</strong>.
            Saya memahami bahwa pemalsuan data dapat dikenakan sanksi hukum sesuai peraturan yang berlaku.
          </span>
        </label>
        @error('agreement') <p class="mt-1 text-xs text-[#31110F] ml-7">{{ $message }}</p> @enderror
      </div>

      <div class="mb-6 bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-[#31110F] text-xs">
        Data Anda dilindungi sesuai ketentuan privasi SiPadu.
        Token tracking akan dikirimkan ke nomor WhatsApp yang Anda daftarkan.
        Tidak ada password yang perlu diingat.
      </div>

      <div class="flex gap-3">
        <a href="{{ route('home') }}" class="flex-1 py-4 bg-gray-100 text-[#31110F]  text-center rounded-xl hover:bg-gray-200 transition flex items-center justify-center gap-2">
          Batal
        </a>
        <button type="submit" id="submitBtn" class="flex-1 py-4 bg-emerald-600 text-[#F7F4EB]  text-base rounded-xl hover:bg-emerald-700 transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
          Kirim Pengajuan
        </button>
      </div>

      <p class="text-center text-xs text-[#31110F] mt-3">
        Sudah punya token?
        <a href="{{ route('tracking.public') }}" class="text-[#31110F] hover:underline">Lacak pengajuan di sini</a>
      </p>
    </div>

  </form>
</div>
@endsection

@push('scripts')
<script>
// Suppress only HARMLESS third-party errors that don't affect functionality
window.addEventListener('error', function(e) {
  const msg = (e.message || '').toLowerCase();
  // Silence MetaMask extension errors only
  if (msg.includes('could not establish connection') || msg.includes('receiving end does not exist')) {
    e.preventDefault();
    return true;
  }
}, true);

// Suppress unhandled promise rejections from MetaMask only
window.addEventListener('unhandledrejection', event => {
  const reason = event.reason?.toString?.().toLowerCase() || '';
  if (reason.includes('could not establish connection') || reason.includes('receiving end does not exist')) {
    event.preventDefault();
  }
});

// Handle file selection UI
function handleFileSelect(input, key) {
  const area  = document.getElementById('area-' + key);
  const ph    = document.getElementById('placeholder-' + key);
  const sel   = document.getElementById('selected-' + key);
  const fname = document.getElementById('filename-' + key);
  const fsize = document.getElementById('filesize-' + key);

  if (input.files && input.files[0]) {
    const f    = input.files[0];
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

// Refresh CSRF token before submission to prevent 419 expiration errors
function refreshCsrfToken() {
  return fetch('/pengajuan', { credentials: 'include' })
    .then(res => res.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newToken = doc.querySelector('input[name="_token"]')?.value;
      if (newToken) {
        document.querySelector('input[name="_token"]').value = newToken;
      }
    })
    .catch(() => {});
}

// Refresh CSRF token periodically (every 30 min) to keep session alive
setInterval(refreshCsrfToken, 30 * 60 * 1000);

// Initialize form handlers on load
document.addEventListener('DOMContentLoaded', function() {
  // WhatsApp number formatting
  const phoneWaField = document.querySelector('input[name="phone_wa"]');
  if (phoneWaField) {
    phoneWaField.addEventListener('input', function() {
      let cleaned = this.value
        .replace(/^(\+62|0)/, '')
        .replace(/\s+/g, '')
        .replace(/[-.()]/g, '')
        .replace(/\D/g, '');
      
      if (cleaned.length > 15) {
        cleaned = cleaned.substring(0, 15);
      }
      this.value = cleaned;
    });
  }

  // Form submission - refresh CSRF token before submit
  const form = document.getElementById('submissionForm');
  const btn = document.getElementById('submitBtn');
  
  if (form) {
    form.addEventListener('submit', function(e) {
      // Let browser handle submission if valid
      if (!form.checkValidity()) {
        e.preventDefault();
        return false;
      }
      // Valid - refresh token first, then update button
      e.preventDefault();
      btn.disabled = true;
      btn.innerHTML = 'Mengirim...';
      
      // Refresh CSRF token before submitting
      refreshCsrfToken().then(() => {
        // Token refreshed, now submit form
        form.submit();
      });
    });
  }
});
</script>
@endpush
