@extends('layouts.public')

@section('title', 'Lacak Pengajuan')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] py-16 px-4">
  <div class="w-full max-w-lg">
    <div class="text-center mb-8">
      <h1 class="text-3xl  text-[#31110F] mb-2">Lacak Pengajuan</h1>
      <p class="text-[#31110F]">
        Pantau status pembaruan dokumen kependudukan Anda melalui kerja sama <strong>Pengadilan Agama Painan</strong> dan <strong>Disdukcapil Pessel</strong>. Masukkan kode token yang Anda terima via WhatsApp.
      </p>
    </div>

    <div class="bg-[#F7F4EB] rounded-2xl shadow-md p-8">
      <form id="trackingForm" class="space-y-4">
        <div>
          <label for="token" class="block text-sm  text-[#31110F] mb-1">Kode Token</label>
          <input type="text" id="token" name="token" value="{{ $token ?? '' }}"
            placeholder="Contoh: PUB-ABCDE12345 atau TRK-2024-XXXXX"
            class="w-full px-4 py-3 border border-gray-300 rounded-xl text-[#31110F] placeholder-[#31110F] focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent uppercase" />
        </div>
        <button type="submit"
          class="w-full py-3 bg-brand text-[#F7F4EB]  rounded-xl hover:opacity-90 transition">
          Lacak Sekarang
        </button>
      </form>

      <div id="trackingResult" class="mt-6 hidden">
        <div id="resultContent"></div>
      </div>
    </div>

    <div class="text-center mt-4 space-y-2">
      <div>
        <a href="{{ route('public.submit.create') }}" class="text-sm text-[#31110F] hover:text-[#31110F] ">
          Belum mengajukan? Ajukan sekarang
        </a>
      </div>
      <div>
        <a href="{{ route('auth.login') }}" class="text-sm text-[#31110F] hover:opacity-75 ">
          Masuk ke sistem (petugas/admin)
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
    yellow: '#fef9c3;color:#31110F',
    blue:   '#e8f5e0;color:#31110F',
    indigo: '#e0e7ff;color:#31110F',
    green:  '#dcfce7;color:#31110F',
    red:    '#fee2e2;color:#31110F',
    emerald:'#d1fae5;color:#31110F',
    gray:   '#f3f4f6;color:#31110F',
    purple: '#f3e8ff;color:#31110F',
  };
  const style = colors[bg] ?? colors.gray;
  return `<span class="inline-block px-2 py-1 rounded-full text-xs " style="background:${style.split(';')[0].replace('background:','')};${style.split(';')[1]}">${label}</span>`;
}

function renderPublicSubmission(data) {
  const docs = (data.documents || []).map(d =>
    `<div class="text-center">
      <img src="${d.url}" alt="${d.label}" class="w-full h-auto rounded-lg border border-gray-200 mb-2 object-cover" style="max-height:300px;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 300%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2216%22 fill=%22%239ca3af%22%3EUnable to load image%3C/text%3E%3C/svg%3E'">
      <p class=" text-sm text-[#31110F]">${d.label}</p>
      <p class="text-xs text-[#31110F]">${d.size}</p>
    </div>`
  ).join('');

  const caseRow = data.case_number
    ? `<div class="flex justify-between"><span class="text-[#31110F]">Nomor Kasus Resmi</span><span class=" text-[#31110F]">${data.case_number}</span></div>`
    : '';

  return `
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 flex items-center justify-between">
        <span class=" text-[#31110F]">Status Pengajuan</span>
        ${statusBadge(data.status, data.status_label)}
      </div>
      <div class="p-5 space-y-3">
        <div class="flex justify-between"><span class="text-[#31110F]">Nama Pemohon</span><span class="">${data.petitioner_name ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-[#31110F]">NIK</span><span class="font-mono text-sm">${data.nik ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-[#31110F]">Tanggal Cerai</span><span class="">${data.divorce_date ?? '-'}</span></div>
        ${caseRow}
        <div class="flex justify-between"><span class="text-[#31110F]">Diajukan</span><span class="">${data.submitted_at ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-[#31110F]">Terakhir Diperbarui</span><span class="">${data.updated_at ?? '-'}</span></div>
        ${data.processed_at ? `<div class="flex justify-between"><span class="text-[#31110F]">Diproses Pada</span><span class="">${data.processed_at}</span></div>` : ''}
      </div>
      ${docs ? `<div class="border-t border-gray-100 px-5 py-4">
        <p class="text-xs  text-[#31110F] uppercase mb-4">Dokumen Diunggah (${data.documents_count})</p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">${docs}</div>
      </div>` : ''}
      <div class="bg-brand-light/10 px-5 py-3 border-t border-brand/20 text-xs text-[#31110F]">
        Untuk informasi lebih lanjut, hubungi petugas Disdukcapil setempat.
      </div>
    </div>`;
}

function renderCase(data) {
  const timeline = (data.timeline || []).map(t =>
    `<li class="flex items-start gap-3 text-sm">
      <div class="w-2 h-2 rounded-full bg-brand mt-1.5 flex-shrink-0"></div>
      <div>
        <span class="text-[#31110F] ">${t.from} - ${t.to}</span>
        <span class="ml-2 text-xs text-[#31110F]">${new Date(t.date).toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric'})}</span>
      </div>
    </li>`
  ).join('');

  // Render dokumen BAST dan DIGITAL untuk COMPLETED cases
  const documents = (data.documents || []).map(doc =>
    `<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-brand/50 hover:bg-brand-light/10 transition">
      <div class="flex items-center gap-3 flex-1">
        <div>
          <p class=" text-sm text-[#31110F]">${doc.document_type === 'BAST' ? 'BAST (Berita Acara)' : 'Dokumen Digital'}</p>
          <p class="text-xs text-[#31110F]">${(doc.size_bytes / 1024).toFixed(1)} KB</p>
        </div>
      </div>
      <a href="/api/v1/tracking/${encodeURIComponent(data.tracking_token)}/download/${doc.id}" 
         class="px-3 py-2 bg-emerald-600 text-[#F7F4EB] text-xs  rounded-lg hover:bg-emerald-700 transition inline-flex items-center gap-1">
        Download
      </a>
    </div>`
  ).join('');

  const docsSection = documents 
    ? `<div class="border-t border-gray-100 px-5 py-4">
        <p class="text-xs  text-[#31110F] uppercase mb-3">Dokumen Selesai (${(data.documents || []).length})</p>
        <div class="space-y-2">${documents}</div>
      </div>`
    : '';

  return `
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 flex items-center justify-between">
        <span class=" text-[#31110F]">Status Kasus</span>
        ${statusBadge(data.status, data.status_label)}
      </div>
      <div class="p-5 space-y-3">
        <div class="flex justify-between"><span class="text-[#31110F]">Nomor Kasus</span><span class="">${data.case_number ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-[#31110F]">Institusi</span><span class="">${data.institution ?? '-'}</span></div>
        <div class="flex justify-between"><span class="text-[#31110F]">Diajukan</span><span class="">${data.submitted_at ? new Date(data.submitted_at).toLocaleDateString('id-ID') : '-'}</span></div>
        <div class="flex justify-between"><span class="text-[#31110F]">Selesai</span><span class="">${data.completed_at ? new Date(data.completed_at).toLocaleDateString('id-ID') : '-'}</span></div>
      </div>
      ${timeline ? `<div class="border-t border-gray-100 px-5 py-4">
        <p class="text-xs  text-[#31110F] uppercase mb-2">Riwayat Status</p>
        <ul class="space-y-2">${timeline}</ul>
      </div>` : ''}
      ${docsSection}
    </div>`;
}

document.getElementById('trackingForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const rawToken = document.getElementById('token').value.trim().toUpperCase();
  if (!rawToken) return;

  const resultDiv    = document.getElementById('trackingResult');
  const resultContent = document.getElementById('resultContent');

  resultContent.innerHTML = '<div class="text-center py-4"><span class="text-[#31110F]">Memuat...</span></div>';
  resultDiv.classList.remove('hidden');

  try {
    const res  = await fetch(`/api/v1/tracking/${encodeURIComponent(rawToken)}`);
    const data = await res.json();

    if (!res.ok) {
      resultContent.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-[#31110F]">
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
    resultContent.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-[#31110F]">Terjadi kesalahan jaringan.</div>`;
  }
});

// Auto-search if token provided from URL
@if(!empty($token))
document.getElementById('trackingForm').dispatchEvent(new Event('submit'));
@endif
</script>
@endpush

