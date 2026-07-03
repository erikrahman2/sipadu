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
  .cerai-option-card {
    @apply border-emerald-500 bg-emerald-50;
  }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

  {{-- Page Title --}}
  <div class="text-center mb-10">
    <h1 class="text-2xl font-bold text-[#31110F]">Pengajuan Pembaruan Dokumen</h1>
    <p class="text-sm text-[#31110F]/60 mt-1">Fokus input pada data KTP suami dan istri agar validasi OCR lebih akurat.</p>
  </div>

  @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-3 text-[#31110F] text-sm">
      <strong>Terdapat kesalahan pada form:</strong>
      <ul class="mt-2 list-disc list-inside text-xs">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
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

        <div class="md:col-span-2">
          <label for="notes" class="block text-sm  text-[#31110F] mb-1">Catatan Tambahan</label>
          <textarea id="notes" name="notes" rows="3" placeholder="Keterangan tambahan (opsional)..." class="input-field resize-none">{{ old('notes') }}</textarea>
          @error('notes') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    {{-- === Langkah 3: Pilih Jenis Cerai === --}}
    <div class="bg-[#F7F4EB] rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">3</span>
        <h2 class=" text-[#31110F]">Pilih Jenis Cerai</h2>
      </div>
      <div class="p-6">
        <label class="block text-sm font-medium text-[#31110F] mb-2">Jenis Cerai</label>
        <select name="cerai_type" id="cerai-type-select" class="input-field @error('cerai_type') border-red-400 @enderror" required>
          @foreach($ceraiOptions as $key => $option)
            <option value="{{ $key }}" {{ old('cerai_type', 'cerai_normal') === $key ? 'selected' : '' }}
              data-docs="{{ count($option['docs']) }}">
              {{ $option['label'] }} ({{ count($option['docs']) }} dokumen)
            </option>
          @endforeach
        </select>
        @error('cerai_type') <p class="mt-1 text-xs text-[#31110F]">{{ $message }}</p> @enderror
        <p class="mt-2 text-xs text-[#31110F]/60">Dokumen yang perlu diunggah akan disesuaikan secara otomatis.</p>
      </div>
    </div>

    {{-- === Langkah 4: Upload Dokumen === --}}
    <div class="bg-[#F7F4EB] rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">4</span>
        <h2 class=" text-[#31110F]">Upload Dokumen</h2>
        <span class="text-xs text-[#31110F] ml-auto">Maks. 5 MB per file - JPG, PNG, PDF</span>
      </div>
      <div class="p-6 space-y-6">
        @foreach($ceraiOptions as $key => $option)
          <div data-cerai-panel="{{ $key }}" class="cerai-panel hidden">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-5">
                <div>
                  <p class="text-[10px] uppercase tracking-[0.18em] text-[#31110F]/40 mb-2">{{ $option['label'] }}</p>
                  <h3 class="text-lg font-semibold text-[#31110F] mb-1">Dokumen untuk {{ $option['label'] }}</h3>
                  <p class="text-sm text-[#31110F]/60 max-w-2xl">{{ $option['description'] }}</p>
                </div>
                <div class="rounded-full bg-emerald-50 text-emerald-700 px-3 py-1.5 text-xs font-semibold">
                  {{ count($option['docs']) }} dokumen
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($option['docs'] as $docKey => $docLabel)
                  @php
                    $inputId = $key . '-' . $docKey;
                    $isRequired = in_array($docKey, $option['required'] ?? []);
                  @endphp
                  <div>
                    <label for="file-{{ $inputId }}" class="block text-sm text-[#31110F] mb-1">
                      {{ $docLabel }}
                      @if($isRequired)
                        <span class="text-red-500">*</span>
                      @else
                        <span class="text-gray-400 text-xs">(opsional)</span>
                      @endif
                    </label>
                    <div class="doc-upload-area @if($isRequired) required-upload @endif" id="area-{{ $inputId }}" onclick="document.getElementById('file-{{ $inputId }}').click()">
                      <input type="file" name="documents[{{ $docKey }}]" id="file-{{ $inputId }}" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                        onchange="handleFileSelect(this, '{{ $inputId }}')">
                      <div id="placeholder-{{ $inputId }}">
                        <p class="text-sm text-[#31110F]">Klik untuk pilih file</p>
                      </div>
                      <div id="selected-{{ $inputId }}" class="hidden">
                        <p class="text-sm text-[#31110F]" id="filename-{{ $inputId }}"></p>
                        <p class="text-xs text-[#31110F]" id="filesize-{{ $inputId }}"></p>
                      </div>
                    </div>
                    @error('documents.' . $docKey) <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                  </div>
                @endforeach
              </div>
            </div>
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
        Data Anda dilindungi sesuai ketentuan kerja sama SiPadu antara Pengadilan Agama Painan dan Disdukcapil Kabupaten Pesisir Selatan.
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

function syncCeraiPanels() {
  const select = document.getElementById('cerai-type-select');
  const activeType = select ? select.value : 'cerai_normal';

  document.querySelectorAll('[data-cerai-panel]').forEach(function(panel) {
    const isActive = panel.dataset.ceraiPanel === activeType;
    panel.classList.toggle('hidden', !isActive);

    // Disable inputs in hidden panels, enable inputs in active panel
    const inputs = panel.querySelectorAll('input[type="file"]');
    inputs.forEach(function(input) {
      input.disabled = !isActive;
    });
  });
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
  syncCeraiPanels();

  const select = document.getElementById('cerai-type-select');
  if (select) {
    select.addEventListener('change', syncCeraiPanels);
  }

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

  // Form submission - validate required documents manually
  const form = document.getElementById('submissionForm');
  const btn = document.getElementById('submitBtn');

  if (form) {
    form.addEventListener('submit', function(e) {
      // Sync panel state first - disable hidden inputs
      syncCeraiPanels();

      // Get current active cerai type
      const select = document.getElementById('cerai-type-select');
      const activeType = select ? select.value : 'cerai_normal';

      // Get required docs from ceraiOptions (passed from controller via PHP)
      const ceraiOptionsData = @json($ceraiOptions);
      const requiredDocKeys = ceraiOptionsData[activeType]?.required ?? [];

      // Check each required document
      const missing = [];
      requiredDocKeys.forEach(docType => {
        const input = document.getElementById('file-' + activeType + '-' + docType);
        if (!input || !input.files || !input.files[0]) {
          missing.push(docType);
        }
      });

      if (missing.length > 0) {
        e.preventDefault();
        // Show error message
        const docLabels = missing.map(dt => {
          const docs = ceraiOptionsData[activeType]?.docs ?? {};
          return docs[dt] ?? dt;
        });
        alert('Dokumen wajib belum diupload:\n' + docLabels.join('\n'));
        return;
      }

      // Documents are valid - show loading state and submit
      btn.disabled = true;
      btn.innerHTML = '<span class="animate-spin inline-block mr-2">⟳</span> Mengirim...';

      // Submit the form directly
      form.submit();
    });
  }
});
</script>
@endpush
