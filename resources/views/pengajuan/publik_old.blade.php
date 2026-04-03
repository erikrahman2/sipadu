@extends('layouts.public')

@section('title', 'Pengajuan Pembaruan Dokumen - SiPadu')

@push('styles')
<style>
  .doc-upload-area {
    @apply border-2 border-dashed border-gray-300 rounded-xl p-5 text-center cursor-pointer transition hover:border-blue-400 hover:bg-blue-50;
  }
  .doc-upload-area.has-file {
    @apply border-emerald-400 bg-emerald-50;
  }
  .step-badge {
    @apply inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-700 text-white text-sm font-bold flex-shrink-0;
  }
  .input-field {
    @apply w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
  }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto py-10 px-4">

  {{-- ── Header ────────────────────────────────────────────────────────────── --}}
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-500 mb-4 shadow-lg">
      <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
    </div>
    <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Pengajuan Pembaruan Dokumen</h1>
    <p class="text-gray-500 max-w-lg mx-auto">
      Ajukan pembaruan KTP/KK pasca perceraian secara mandiri.
      Token pelacakan akan dikirim ke nomor WhatsApp Anda — <strong>tanpa perlu membuat akun atau password</strong>.
    </p>
  </div>

  {{-- ── Info kuota ────────────────────────────────────────────────────────── --}}
  <div id="quotaInfo" class="mb-6 hidden">
    <div id="quotaAllowed" class="hidden bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 text-emerald-800 text-sm">
      <i class="fas fa-check-circle mr-1"></i>
      NIK ini masih memiliki <strong id="quotaCount">–</strong> kuota pengajuan.
    </div>
    <div id="quotaDenied" class="hidden bg-red-50 border border-red-200 rounded-xl px-5 py-3 text-red-700 text-sm">
      <i class="fas fa-ban mr-1"></i>
      NIK ini telah mencapai batas <strong>{{ App\Models\PublicSubmission::MAX_SUBMISSIONS }} pengajuan</strong>
      dalam {{ App\Models\PublicSubmission::LIMIT_DAYS }} hari terakhir.
      Anda bisa mengajukan kembali mulai <strong id="quotaNextDate">–</strong>.
    </div>
  </div>

  {{-- ── Error umum ──────────────────────────────────────────────────────────── --}}
  @if($errors->has('general'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-3 text-red-700 text-sm">
      <i class="fas fa-exclamation-circle mr-1"></i>
      {{ $errors->first('general') }}
    </div>
  @endif

  {{-- ── Form ─────────────────────────────────────────────────────────────── --}}
  <form id="submissionForm" method="POST" action="{{ route('public.submit.store') }}"
        enctype="multipart/form-data"
        class="space-y-8">
    @csrf

    {{-- === Langkah 1: Data Pemohon === --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">1</span>
        <h2 class="font-semibold text-gray-800">Input Data Pasangan</h2>
        <span class="text-xs text-gray-400 ml-auto">Maks. 5 MB per file - JPG, PNG, PDF</span>
      </div>
      <div class="p-6 space-y-6">
        {{-- Kartu Data Pemohon --}}
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 space-y-4">
          <h3 class="font-semibold text-emerald-800">Data Pemohon</h3>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">NIK <span class="text-red-500">*</span></label>
            <input type="text" name="nik" id="nik"
              value="{{ old('nik', $nik ?? '') }}"
              maxlength="16" inputmode="numeric" pattern="\d{16}"
              placeholder="16 digit sesuai KTP"
              class="input-field @error('nik') border-red-400 @enderror"
              required />
            @error('nik')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-400">NIK sama hanya dapat digunakan {{ App\Models\PublicSubmission::MAX_SUBMISSIONS }}× dalam {{ App\Models\PublicSubmission::LIMIT_DAYS }} hari.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pemohon <span class="text-red-500">*</span></label>
            <input type="text" name="petitioner_name"
              value="{{ old('petitioner_name') }}"
              placeholder="Nama sesuai KTP"
              class="input-field @error('petitioner_name') border-red-400 @enderror"
              required />
            @error('petitioner_name')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat <span class="text-red-500">*</span></label>
            <input type="text" name="alamat"
              value="{{ old('alamat') }}"
              placeholder="Alamat sesuai KTP"
              class="input-field @error('alamat') border-red-400 @enderror"
              required />
            @error('alamat')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">RT/RW <span class="text-red-500">*</span></label>
              <input type="text" name="rt_rw"
                value="{{ old('rt_rw') }}"
                placeholder="000/000"
                class="input-field @error('rt_rw') border-red-400 @enderror"
                required />
              @error('rt_rw')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan <span class="text-red-500">*</span></label>
              <input type="text" name="kelurahan"
                value="{{ old('kelurahan') }}"
                class="input-field @error('kelurahan') border-red-400 @enderror"
                required />
              @error('kelurahan')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan <span class="text-red-500">*</span></label>
            <input type="text" name="kecamatan"
              value="{{ old('kecamatan') }}"
              class="input-field @error('kecamatan') border-red-400 @enderror"
              required />
            @error('kecamatan')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>

        {{-- Upload KTP & KK --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload KTP <span class="text-red-500">*</span></label>
            <div class="doc-upload-area" id="area-KTP" onclick="document.getElementById('file-KTP').click()">
              <input type="file" name="documents[KTP]" id="file-KTP" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, 'KTP')">
              <div id="placeholder-KTP">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-1"></i>
                <p class="text-sm text-gray-500">Klik untuk pilih file</p>
              </div>
              <div id="selected-KTP" class="hidden">
                <i class="fas fa-check-circle text-emerald-500 text-2xl mb-1"></i>
                <p class="text-sm text-emerald-700 font-medium" id="filename-KTP"></p>
                <p class="text-xs text-gray-500" id="filesize-KTP"></p>
              </div>
            </div>
            @error('documents.KTP')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Kartu Keluarga (KK)</label>
            <div class="doc-upload-area" id="area-KK" onclick="document.getElementById('file-KK').click()">
              <input type="file" name="documents[KK]" id="file-KK" class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, 'KK')">
              <div id="placeholder-KK">
                <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-1"></i>
                <p class="text-sm text-gray-500">Klik untuk pilih file</p>
              </div>
              <div id="selected-KK" class="hidden">
                <i class="fas fa-check-circle text-emerald-500 text-2xl mb-1"></i>
                <p class="text-sm text-emerald-700 font-medium" id="filename-KK"></p>
                <p class="text-xs text-gray-500" id="filesize-KK"></p>
              </div>
            </div>
            @error('documents.KK')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>

        {{-- Nomor WA & Institusi --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-gray-100 pt-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Nomor WhatsApp Aktif <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center">
              <span class="px-3 py-3 bg-gray-100 border border-r-0 border-gray-300 rounded-l-xl text-gray-500 text-sm">+62</span>
              <input type="tel" name="phone_wa"
                value="{{ old('phone_wa') }}"
                inputmode="numeric"
                placeholder="81234567890"
                class="flex-1 px-4 py-3 border border-gray-300 rounded-r-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone_wa') border-red-400 @enderror"
                required />
            </div>
            @error('phone_wa')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-400">Token tracking akan dikirimkan ke nomor ini melalui WhatsApp.</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Pengadilan Agama / Institusi <span class="text-red-500">*</span>
            </label>
            <select name="institution_id" required class="input-field @error('institution_id') border-red-400 @enderror">
              <option value="">Pilih Institusi</option>
              @foreach($institutions ?? [] as $inst)
                <option value="{{ $inst->id }}" {{ old('institution_id') == $inst->id ? 'selected' : '' }}>
                  {{ $inst->name }} ({{ $inst->type }})
                </option>
              @endforeach
            </select>
            @error('institution_id')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- === Langkah 2: Data Perceraian === --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">2</span>
        <h2 class="font-semibold text-gray-800">Data Cerai</h2>
      </div>
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Nama mantan pasangan --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mantan Pasangan</label>
          <input type="text" name="respondent_name"
            value="{{ old('respondent_name') }}"
            placeholder="Nama sesuai KTP"
            class="input-field" />
          @error('respondent_name')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- NIK mantan pasangan --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">NIK Mantan Pasangan</label>
          <input type="text" name="respondent_nik"
            value="{{ old('respondent_nik') }}"
            maxlength="16" inputmode="numeric"
            placeholder="16 digit (opsional)"
            class="input-field" />
          @error('respondent_nik')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Tanggal cerai --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Putusan Cerai</label>
          <input type="date" name="divorce_date"
            value="{{ old('divorce_date') }}"
            max="{{ date('Y-m-d') }}"
            class="input-field" />
          @error('divorce_date')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Nomor putusan --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Putusan PA</label>
          <input type="text" name="verdict_number"
            value="{{ old('verdict_number') }}"
            placeholder="Contoh: 0123/Pdt.G/2025/PA.JS"
            class="input-field" />
          @error('verdict_number')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Upload dokumen cerai --}}
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

        {{-- Catatan --}}
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
          <textarea name="notes" rows="3"
            placeholder="Keterangan tambahan (opsional)..."
            class="input-field resize-none">{{ old('notes') }}</textarea>
          @error('notes')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>

      </div>
    </div>

    {{-- === Langkah 3: Upload Berkas Lainnya === --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <span class="step-badge">3</span>
        <h2 class="font-semibold text-gray-800">Upload Berkas Lainnya</h2>
        <span class="text-xs text-gray-400 ml-auto">Opsional · Maks. {{ $maxSizeMb }} MB per file</span>
      </div>
      <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
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
            <div class="doc-upload-area" id="area-{{ $key }}"
                 onclick="document.getElementById('file-{{ $key }}').click()">
              <input type="file" name="documents[{{ $key }}]" id="file-{{ $key }}"
                class="hidden" accept=".jpg,.jpeg,.png,.pdf"
                onchange="handleFileSelect(this, '{{ $key }}')" />
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

    {{-- === Pernyataan & Submit === --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

      {{-- Pernyataan kebenaran --}}
      <div class="mb-5">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="agreement" value="1"
            class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            {{ old('agreement') ? 'checked' : '' }}/>
          <span class="text-sm text-gray-600">
            Saya menyatakan bahwa seluruh data dan dokumen yang saya unggah adalah <strong>benar dan sah</strong>.
            Saya memahami bahwa pemalsuan data dapat dikenakan sanksi hukum sesuai peraturan yang berlaku.
          </span>
        </label>
        @error('agreement')
          <p class="mt-1 text-xs text-red-600 ml-7">{{ $message }}</p>
        @enderror
      </div>

      {{-- Privacy note --}}
      <div class="mb-6 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-blue-700 text-xs">
        <i class="fas fa-shield-alt mr-1"></i>
        Data Anda dilindungi sesuai ketentuan privasi SiPadu.
        Token tracking akan dikirimkan ke nomor WhatsApp yang Anda daftarkan.
        Tidak ada password yang perlu diingat.
      </div>

      <div class="flex gap-3">
        <a href="{{ route('home') }}" 
          class="flex-1 py-4 bg-gray-100 text-gray-700 font-medium text-center rounded-xl hover:bg-gray-200 transition flex items-center justify-center gap-2">
          <i class="fas fa-times mr-1"></i>
          Batal
        </a>
        <button type="submit" id="submitBtn"
          class="flex-1 py-4 bg-emerald-600 text-white font-bold text-base rounded-xl hover:bg-emerald-700 transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
          <i class="fas fa-paper-plane"></i>
          Kirim Pengajuan
        </button>
      </div>

      <p class="text-center text-xs text-gray-400 mt-3">
        Sudah punya token?
        <a href="{{ route('tracking.public') }}" class="text-blue-600 hover:underline">Lacak pengajuan di sini</a>
      </p>
    </div>

  </form>
</div>
@endsection

@push('scripts')
<style>
  .input-field {
    @apply w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
  }
  .input-field:focus { outline: none; }
</style>
<script>
/* ── Tailwind @apply tidak bekerja di <style> luar CSS — tambahkan manual: */
document.querySelectorAll('.input-field').forEach(el => {
  el.classList.add(
    'w-full','px-4','py-3','border','border-gray-300','rounded-xl',
    'text-gray-900','placeholder-gray-400',
    'focus:outline-none','focus:ring-2','focus:ring-blue-500','focus:border-transparent'
  );
});

/* ── File upload preview ───────────────────────────────────────────────── */
function handleFileSelect(input, key) {
  const area  = document.getElementById('area-' + key);
  const ph    = document.getElementById('placeholder-' + key);
  const sel   = document.getElementById('selected-' + key);
  const fname = document.getElementById('filename-' + key);
  const fsize = document.getElementById('filesize-' + key);

  if (input.files && input.files[0]) {
    const f    = input.files[0];
    const size = f.size < 1048576
      ? (f.size / 1024).toFixed(1) + ' KB'
      : (f.size / 1048576).toFixed(2) + ' MB';

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

/* ── Cek kuota NIK saat keluar dari field NIK ──────────────────────────── */
const nikField = document.getElementById('nik');

async function checkNikQuota(nik) {
  if (nik.length !== 16) return;
  try {
    const res = await fetch('{{ route('public.submit.check_nik') }}', {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ nik }),
    });
    const data = await res.json();

    const info    = document.getElementById('quotaInfo');
    const allowed = document.getElementById('quotaAllowed');
    const denied  = document.getElementById('quotaDenied');
    const btn     = document.getElementById('submitBtn');

    info.classList.remove('hidden');

    if (data.allowed) {
      allowed.classList.remove('hidden');
      denied.classList.add('hidden');
      document.getElementById('quotaCount').textContent = data.remaining + ' dari ' + data.max;
      btn.disabled = false;
    } else {
      denied.classList.remove('hidden');
      allowed.classList.add('hidden');
      document.getElementById('quotaNextDate').textContent = data.next_date ?? '–';
      btn.disabled = true;
    }
  } catch (_) {}
}

nikField.addEventListener('blur', () => checkNikQuota(nikField.value.trim()));
nikField.addEventListener('input', () => {
  if (nikField.value.trim().length === 16) checkNikQuota(nikField.value.trim());
});

/* ── Submit: loading state ────────────────────────────────────────────── */
document.getElementById('submissionForm').addEventListener('submit', function () {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Mengirim...';
});

/* ── Auto-cek jika NIK sudah terisi dari query param ─────────────────── */
@if(!empty($nik))
  checkNikQuota('{{ $nik }}');
@endif
</script>
@endpush
