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
  .cerai-option-card {
    @apply border-emerald-500 bg-emerald-50;
  }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Buat Kasus Baru</h1>
    <p class="text-gray-500 text-sm mt-1">Lengkapi data di bawah ini untuk membuat kasus baru.</p>
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

  @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-5 py-3 rounded-xl text-sm">
      <i class="fas fa-exclamation-triangle mr-1"></i>
      <strong>{{ session('error') }}</strong>
    </div>
  @endif

  <form id="caseForm" method="POST" action="{{ route('dashboard.cases.store') }}" enctype="multipart/form-data" class="space-y-8">
    @csrf

    {{-- === Langkah 1: Input Data Pasangan === --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">1</span>
        <h2 class="font-semibold text-gray-800">Input Data Pasangan</h2>
        <span class="text-xs text-gray-400 ml-auto">Maks. 10 MB per file - JPG, PNG, PDF</span>
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

    {{-- === Langkah 2: Data Cerai === --}}
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

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
          <textarea name="notes" rows="3" placeholder="Keterangan tambahan (opsional)..." class="input-field resize-none">{{ old('notes') }}</textarea>
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
            <option value="{{ $key }}" {{ old('cerai_type', 'cerai_normal') === $key ? 'selected' : '' }}
              data-docs="{{ count($option['docs']) }}">
              {{ $option['label'] }} ({{ count($option['docs']) }} dokumen)
            </option>
          @endforeach
        </select>
        @error('cerai_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        <p class="mt-2 text-xs text-gray-500">Dokumen yang perlu diunggah akan disesuaikan secara otomatis.</p>
      </div>
    </div>

    {{-- === Langkah 4: Upload Dokumen === --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">4</span>
        <h2 class="font-semibold text-gray-800">Upload Dokumen</h2>
        <span class="text-xs text-gray-400 ml-auto">Maks. 10 MB per file - JPG, PNG, PDF</span>
      </div>
      <div class="p-6 space-y-6">
        @foreach($ceraiOptions as $key => $option)
          <div data-cerai-panel="{{ $key }}" class="cerai-panel hidden">
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
                  @php $inputId = $key . '-' . $docKey; @endphp
                  <div>
                    <label for="file-{{ $inputId }}" class="block text-sm text-gray-700 mb-1">{{ $docLabel }}</label>
                    <div class="doc-upload-area" id="area-{{ $inputId }}" onclick="document.getElementById('file-{{ $inputId }}').click()">
                      <input type="file" name="documents[{{ $key }}][{{ $docKey }}]" id="file-{{ $inputId }}" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                        onchange="handleFileSelect(this, '{{ $inputId }}')">
                      <div id="placeholder-{{ $inputId }}">
                        <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-1"></i>
                        <p class="text-sm text-gray-500">Klik untuk pilih file</p>
                      </div>
                      <div id="selected-{{ $inputId }}" class="hidden">
                        <i class="fas fa-check-circle text-emerald-500 text-2xl mb-1"></i>
                        <p class="text-sm text-emerald-700 font-medium" id="filename-{{ $inputId }}"></p>
                        <p class="text-xs text-emerald-600" id="filesize-{{ $inputId }}"></p>
                      </div>
                    </div>
                    @error('documents.' . $key . '.' . $docKey) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- === Pernyataan & Submit === --}}
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
        <a href="{{ route('dashboard.cases') }}" class="flex-1 py-3 bg-gray-100 text-gray-700 font-medium text-center rounded-xl hover:bg-gray-200 transition">Batal</a>
        <button type="button" id="draftBtn" onclick="saveDraft()"
          class="flex-1 py-3 bg-[#8b6f47] text-white font-medium rounded-xl hover:bg-[#7a5c3c] transition disabled:opacity-60 disabled:cursor-not-allowed">Simpan Draft</button>
        <button type="submit" id="submitBtn"
          class="flex-1 py-3 bg-[#2d3a27] text-white font-bold rounded-xl hover:bg-[#1a2515] transition disabled:opacity-60 disabled:cursor-not-allowed">Kirim Pengajuan</button>
      </div>
    </div>

  </form>
</div>
@endsection

@push('scripts')
<script>
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

function saveDraft() {
  const btn = document.getElementById('draftBtn');
  const form = document.getElementById('caseForm');

  btn.disabled = true;
  btn.innerHTML = 'Menyimpan...';

  form.action = '{{ route("dashboard.cases.save-draft") }}';
  form.submit();
}

function syncCeraiPanels() {
  const select = document.getElementById('cerai-type-select');
  const activeType = select ? select.value : 'cerai_normal';

  document.querySelectorAll('[data-cerai-panel]').forEach(function(panel) {
    const isActive = panel.dataset.ceraiPanel === activeType;
    // Jangan disable input - browser tidak kirim field disabled
    // Hanya hide panel secara visual
    panel.classList.toggle('hidden', !isActive);
  });
}

document.addEventListener('DOMContentLoaded', function() {
  syncCeraiPanels();

  const select = document.getElementById('cerai-type-select');
  if (select) {
    select.addEventListener('change', syncCeraiPanels);
  }
});

document.getElementById('caseForm').addEventListener('submit', function (e) {
  const btn = document.getElementById('submitBtn');

  const select = document.getElementById('cerai-type-select');
  const activeType = select ? select.value : 'cerai_normal';

  const requiredDocs = {
    'cerai_normal': ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH'],
    'cerai_mati': ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH', 'AKTA_KEMATIAN', 'SURAT_KETERANGAN_AHLI_WARIS'],
    'cerai_pindah': ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH', 'SURAT_PINDAH'],
    'cerai_ghaib': ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH', 'SURAT_KETERANGAN_GHAIB'],
    'cerai_hak_asuh': ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH', 'AKTA_KELAHIRAN_ANAK'],
  };

  const docs = requiredDocs[activeType] || requiredDocs['cerai_normal'];
  const missing = docs.filter(docType => {
    // Check file with cerai type prefix: file-cerai_normal-KTP_SUAMI
    const input = document.getElementById('file-' + activeType + '-' + docType);
    return !input || !input.files || !input.files[0];
  });

  if (missing.length > 0) {
    e.preventDefault();
    btn.disabled = false;
    btn.innerHTML = 'Kirim Pengajuan';
    alert('Dokumen belum lengkap! Upload dulu: ' + missing.join(', '));
    return;
  }

  btn.disabled = true;
  btn.innerHTML = 'Mengirim...';
});
</script>
@endpush
