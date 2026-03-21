@extends('layouts.app')

@section('title', 'Lacak Pengajuan')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] py-16 px-4">
  <div class="w-full max-w-lg">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-800 to-cyan-600 mb-4 shadow-lg">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
      </div>
      <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Lacak Pengajuan</h1>
      <p class="text-gray-500">
        Masukkan kode token yang Anda terima via WhatsApp untuk memantau status pengajuan.
      </p>
    </div>

    <div class="bg-white rounded-2xl shadow-md p-8">
      <form id="trackingForm" class="space-y-4">
        <div>
          <label for="token" class="block text-sm font-medium text-gray-700 mb-1">Kode Token</label>
          <input type="text" id="token" name="token" value="{{ $token ?? '' }}"
            placeholder="Contoh: PUB-ABCDE12345 atau TRK-2024-XXXXX"
            class="w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase" />
        </div>
        <button type="submit"
          class="w-full py-3 bg-blue-700 text-white font-semibold rounded-xl hover:bg-blue-800 transition">
          <i class="fas fa-search mr-1"></i> Lacak Sekarang
        </button>
      </form>

      <div id="trackingResult" class="mt-6 hidden">
        <div id="resultContent"></div>
      </div>
    </div>

    <div class="text-center mt-4 space-y-2">
      <div>
        <a href="{{ route('public.submit.create') }}" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">
          <i class="fas fa-file-alt mr-1"></i> Belum mengajukan? Ajukan sekarang
        </a>
      </div>
      <div>
        <a href="{{ route('auth.login') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
          &larr; Masuk ke sistem (petugas/admin)
        </a>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
const STATUS_COLORS = {
  // Public submission statuses
  PENDING:       { bg: 'yellow',  label: 'Menunggu Verifikasi' },
  REVIEWING:     { bg: 'blue',    label: 'Sedang Ditinjau' },
  WAITING_OCR:   { bg: 'indigo',  label: 'Proses Verifikasi Dokumen' },
  APPROVED:      { bg: 'green',   label: 'Disetujui' },
  REJECTED:      { bg: 'red',     label: 'Ditolak' },
  COMPLETED:     { bg: 'emerald', label: 'Selesai' },
  // Case statuses
  DRAFT:                   { bg: 'gray',   label: 'Draft' },
  SUBMITTED:               { bg: 'yellow', label: 'Diajukan' },
  OCR_PROCESSED:           { bg: 'indigo', label: 'OCR Selesai' },
  PA_REVIEW:               { bg: 'blue',   label: 'Tinjauan PA' },
  DISDUKCAPIL_VALIDATION:  { bg: 'purple', label: 'Validasi Disdukcapil' },
  ARCHIVED:                { bg: 'gray',   label: 'Diarsip' },
};

function statusBadge(status, labelOverride) {
  const s     = STATUS_COLORS[status] ?? { bg: 'gray', label: status };
  const label = labelOverride ?? s.label;
  const bg    = s.bg;
  // Inline style approach (Tailwind purge-safe)
  const colors = {
    yellow: '#fef9c3;color:#92400e',
    blue:   '#dbeafe;color:#1e40af',
    indigo: '#e0e7ff;color:#3730a3',
    green:  '#dcfce7;color:#166534',
    red:    '#fee2e2;color:#991b1b',
    emerald:'#d1fae5;color:#065f46',
    gray:   '#f3f4f6;color:#374151',
    purple: '#f3e8ff;color:#6b21a8',
  };
  const style = colors[bg] ?? colors.gray;
  return `<span class="inline-block px-2 py-1 rounded-full text-xs font-semibold" style="background:${style.split(';')[0].replace('background:','')};${style.split(';')[1]}">${label}</span>`;
}

function renderPublicSubmission(data) {
  const docs = (data.documents || []).map(d =>
    `<li class="flex items-center gap-2 text-sm text-gray-700">
      <i class="fas fa-file text-gray-400"></i>
      <span>${d.label}</span>
      <span class="text-xs text-gray-400">(${d.size})</span>
    </li>`
  ).join('');

  const caseRow = data.case_number
    ? `<div class="flex justify-between"><span class="text-gray-500">Nomor Kasus Resmi</span><span class="font-medium text-blue-700">${data.case_number}</span></div>`
    : '';

  return `
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 flex items-center justify-between">
        <span class="font-semibold text-gray-800">Status Pengajuan</span>
        ${statusBadge(data.status, data.status_label)}
      </div>
      <div class="p-5 space-y-3">
        <div class="flex justify-between"><span class="text-gray-500">Nama Pemohon</span><span class="font-medium">${data.petitioner_name ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">NIK</span><span class="font-mono text-sm">${data.nik ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Tanggal Cerai</span><span class="font-medium">${data.divorce_date ?? '-'}</span></div>
        ${caseRow}
        <div class="flex justify-between"><span class="text-gray-500">Diajukan</span><span class="font-medium">${data.submitted_at ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Terakhir Diperbarui</span><span class="font-medium">${data.updated_at ?? '-'}</span></div>
        ${data.processed_at ? `<div class="flex justify-between"><span class="text-gray-500">Diproses Pada</span><span class="font-medium">${data.processed_at}</span></div>` : ''}
      </div>
      ${docs ? `<div class="border-t border-gray-100 px-5 py-4">
        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Dokumen Diunggah</p>
        <ul class="space-y-1">${docs}</ul>
      </div>` : ''}
      <div class="bg-blue-50 px-5 py-3 border-t border-blue-100 text-xs text-blue-600">
        <i class="fas fa-info-circle mr-1"></i>
        Untuk informasi lebih lanjut, hubungi petugas Disdukcapil setempat.
      </div>
    </div>`;
}

function renderCase(data) {
  const timeline = (data.timeline || []).map(t =>
    `<li class="flex items-start gap-3 text-sm">
      <div class="w-2 h-2 rounded-full bg-blue-400 mt-1.5 flex-shrink-0"></div>
      <div>
        <span class="text-gray-700 font-medium">${t.from} → ${t.to}</span>
        <span class="ml-2 text-xs text-gray-400">${new Date(t.date).toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric'})}</span>
      </div>
    </li>`
  ).join('');

  return `
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 flex items-center justify-between">
        <span class="font-semibold text-gray-800">Status Kasus</span>
        ${statusBadge(data.status, data.status_label)}
      </div>
      <div class="p-5 space-y-3">
        <div class="flex justify-between"><span class="text-gray-500">Nomor Kasus</span><span class="font-medium">${data.case_number ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Institusi</span><span class="font-medium">${data.institution ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Diajukan</span><span class="font-medium">${data.submitted_at ? new Date(data.submitted_at).toLocaleDateString('id-ID') : '-'}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Selesai</span><span class="font-medium">${data.completed_at ? new Date(data.completed_at).toLocaleDateString('id-ID') : '-'}</span></div>
      </div>
      ${timeline ? `<div class="border-t border-gray-100 px-5 py-4">
        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Riwayat Status</p>
        <ul class="space-y-2">${timeline}</ul>
      </div>` : ''}
    </div>`;
}

document.getElementById('trackingForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const rawToken = document.getElementById('token').value.trim().toUpperCase();
  if (!rawToken) return;

  const resultDiv    = document.getElementById('trackingResult');
  const resultContent = document.getElementById('resultContent');

  resultContent.innerHTML = '<div class="text-center py-4"><span class="text-gray-500">Memuat...</span></div>';
  resultDiv.classList.remove('hidden');

  try {
    const res  = await fetch(`/api/v1/tracking/${encodeURIComponent(rawToken)}`);
    const data = await res.json();

    if (!res.ok) {
      resultContent.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700">
        <strong>Tidak ditemukan:</strong> ${data.message || 'Token tidak valid atau belum terdaftar.'}
      </div>`;
      return;
    }

    if (data.type === 'public_submission') {
      resultContent.innerHTML = renderPublicSubmission(data);
    } else {
      resultContent.innerHTML = renderCase(data);
    }
  } catch (err) {
    resultContent.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700">Terjadi kesalahan jaringan.</div>`;
  }
});

// Auto-search if token provided from URL
@if(!empty($token))
document.getElementById('trackingForm').dispatchEvent(new Event('submit'));
@endif
</script>
@endpush

