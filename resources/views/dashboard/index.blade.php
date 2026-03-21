@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-gray-800 font-medium">Dashboard</span>
@endsection

@section('content')
<div class="space-y-6">

  {{-- Welcome Banner --}}
  <div class="bg-gradient-to-r from-primary-dark to-primary rounded-2xl p-6 text-white shadow-lg">
    <h1 class="text-2xl font-bold">Selamat datang, {{ auth()->user()->name }} 👋</h1>
    <p class="text-blue-200 mt-1">
      {{ auth()->user()->institution?->name ?? 'Sistem SiPadu' }}
      &nbsp;·&nbsp;
      @php
        $roleLabels = [
          'super_admin'       => 'Administrator',
          'pa_assistant'      => 'PA Assistant',
          'pa_management'     => 'PA Management',
          'pa_staff'          => 'PA Staff',
          'disdukcapil_staff' => 'Disdukcapil Staff',
        ];
        $currentRole = auth()->user()->getRoleNames()->first();
        $roleLabel   = $roleLabels[$currentRole] ?? $currentRole;
      @endphp
      <span class="bg-white/20 rounded-full px-2 py-0.5 text-xs font-medium">{{ $roleLabel }}</span>
    </p>
  </div>

  {{-- Stat Cards --}}
  <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
    @php
      $cards = [
        ['label' => 'Total Kasus',  'value' => $stats['total'],       'icon' => 'fa-folder',       'color' => 'blue'],
        ['label' => 'Draft',        'value' => $stats['draft'],        'icon' => 'fa-pen',          'color' => 'gray'],
        ['label' => 'Diproses',     'value' => $stats['in_progress'], 'icon' => 'fa-spinner',      'color' => 'yellow'],
        ['label' => 'Selesai',      'value' => $stats['completed'],    'icon' => 'fa-check-circle', 'color' => 'green'],
        ['label' => 'Ditolak',      'value' => $stats['rejected'],     'icon' => 'fa-times-circle', 'color' => 'red'],
      ];
    @endphp
    @foreach($cards as $card)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-{{ $card['color'] }}-100 flex items-center justify-center">
        <i class="fas {{ $card['icon'] }} text-{{ $card['color'] }}-500 text-xl"></i>
      </div>
      <div>
        <p class="text-2xl font-bold text-gray-800">{{ $card['value'] }}</p>
        <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
      </div>
    </div>
    @endforeach
  </div>

  {{-- Quick Actions — per Aktor --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    {{-- ── PA Assistant ─────────────────────────────────────────────── --}}
    @role('pa_assistant')
      <a href="{{ route('dashboard.cases.create') }}"
         class="bg-white border-2 border-dashed border-primary rounded-2xl p-6 flex items-center gap-4 hover:bg-blue-50 transition group shadow-sm">
        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-primary rounded-xl flex items-center justify-center group-hover:scale-110 transition shadow">
          <i class="fas fa-plus text-white text-2xl"></i>
        </div>
        <div>
          <p class="font-bold text-gray-800 text-lg">Buat Pengajuan Baru</p>
          <p class="text-sm text-gray-500">Input dan daftarkan kasus baru</p>
        </div>
      </a>
      <a href="{{ route('dashboard.cases') }}"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-primary hover:bg-blue-50 transition">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-folder-open text-blue-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Lihat Semua Kasus</p>
          <p class="text-xs text-gray-500">Kasus internal & pengajuan publik</p>
        </div>
      </a>
      <a href="{{ route('dashboard.public-inbox.index') }}"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-purple-400 hover:bg-purple-50 transition">
        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-inbox text-purple-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Pengajuan Publik</p>
          <p class="text-xs text-gray-500">Review pengajuan dari masyarakat</p>
        </div>
      </a>
    @endrole

    {{-- ── PA Management ────────────────────────────────────────────── --}}
    @role('pa_management')
      <a href="{{ route('dashboard.cases') }}?status=OCR_PROCESSED"
         class="bg-white border border-dashed border-yellow-400 rounded-2xl p-6 flex items-center gap-4 hover:bg-yellow-50 transition group">
        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center group-hover:bg-yellow-200 transition">
          <i class="fas fa-file-alt text-yellow-600 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Review Hasil OCR</p>
          <p class="text-xs text-gray-500">Koreksi & verifikasi data dokumen</p>
        </div>
      </a>
      <a href="{{ route('dashboard.cases') }}?status=PA_REVIEW"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-yellow-400 hover:bg-yellow-50 transition">
        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-tasks text-orange-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Approve / Reject</p>
          <p class="text-xs text-gray-500">Keputusan pengajuan ke Disdukcapil</p>
        </div>
      </a>
      <a href="{{ route('dashboard.cases') }}"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-primary hover:bg-blue-50 transition">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-folder-open text-blue-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Semua Kasus</p>
          <p class="text-xs text-gray-500">Riwayat & status seluruh pengajuan</p>
        </div>
      </a>
    @endrole

    {{-- ── PA Staff ──────────────────────────────────────────────────── --}}
    @role('pa_staff')
      <a href="{{ route('dashboard.cases') }}?status=COMPLETED"
         class="bg-white border border-dashed border-green-400 rounded-2xl p-6 flex items-center gap-4 hover:bg-green-50 transition group">
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition">
          <i class="fas fa-archive text-green-600 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Arsip & Serah-Terima</p>
          <p class="text-xs text-gray-500">Kasus selesai siap diarsipkan</p>
        </div>
      </a>
      <a href="{{ route('dashboard.cases') }}"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-green-400 hover:bg-green-50 transition">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-folder text-blue-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Semua Kasus</p>
          <p class="text-xs text-gray-500">Pantau dokumen yang beredar</p>
        </div>
      </a>
      <a href="{{ route('dashboard.upload') }}"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-green-400 hover:bg-green-50 transition">
        <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-paper-plane text-teal-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Kirim Notifikasi</p>
          <p class="text-xs text-gray-500">Info pengambilan dokumen ke pemohon</p>
        </div>
      </a>
    @endrole

    {{-- ── Disdukcapil Staff ────────────────────────────────────────── --}}
    @role('disdukcapil_staff')
      <a href="{{ route('dashboard.cases') }}?status=DISDUKCAPIL_VALIDATION"
         class="bg-white border border-dashed border-indigo-400 rounded-2xl p-6 flex items-center gap-4 hover:bg-indigo-50 transition group">
        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center group-hover:bg-indigo-200 transition">
          <i class="fas fa-check-double text-indigo-600 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Validasi Masuk</p>
          <p class="text-xs text-gray-500">Data dari PA perlu divalidasi</p>
        </div>
      </a>
      <a href="{{ route('dashboard.cases') }}?status=COMPLETED"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-indigo-400 hover:bg-indigo-50 transition">
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-file-download text-green-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Distribusi Hasil</p>
          <p class="text-xs text-gray-500">Dokumen selesai siap dikirim</p>
        </div>
      </a>
      <a href="{{ route('dashboard.cases') }}"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-indigo-400 hover:bg-indigo-50 transition">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-folder-open text-blue-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Semua Kasus</p>
          <p class="text-xs text-gray-500">Riwayat validasi dokumen</p>
        </div>
      </a>
    @endrole

    {{-- super_admin diarahkan ke /dashboard/admin/users oleh controller,
         blok ini hanya sebagai fallback jika view ini dirender untuk super_admin --}}
    @role('super_admin')
      <a href="{{ route('dashboard.admin.users') }}"
         class="bg-white border border-dashed border-primary rounded-2xl p-6 flex items-center gap-4 hover:bg-blue-50 transition group">
        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-200 transition">
          <i class="fas fa-users text-purple-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Manajemen User</p>
          <p class="text-xs text-gray-500">Kelola akun & hak akses</p>
        </div>
      </a>
      <a href="{{ route('dashboard.admin.audit') }}"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-primary hover:bg-blue-50 transition">
        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-shield-halved text-red-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Audit Log</p>
          <p class="text-xs text-gray-500">Pantau aktivitas sistem</p>
        </div>
      </a>
      <a href="{{ route('dashboard.admin.logs') }}"
         class="bg-white border border-gray-200 rounded-2xl p-6 flex items-center gap-4 hover:border-primary hover:bg-blue-50 transition">
        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
          <i class="fas fa-list-check text-gray-500 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Access Log</p>
          <p class="text-xs text-gray-500">Rekaman seluruh request HTTP</p>
        </div>
      </a>
    @endrole

  </div>

  {{-- Recent Items Table (Cases + Public Submissions) --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-800"><i class="fas fa-history mr-2 text-primary"></i>Aktivitas Terbaru</h2>
      <a href="{{ route('dashboard.cases') }}" class="text-sm text-primary hover:underline">Lihat semua →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-gray-600 text-left text-xs">
            <th class="px-4 py-3 font-semibold">Jenis</th>
            <th class="px-4 py-3 font-semibold">No/Token</th>
            <th class="px-4 py-3 font-semibold">Pemohon</th>
            <th class="px-4 py-3 font-semibold">Status</th>
            <th class="px-4 py-3 font-semibold">Tanggal</th>
            <th class="px-4 py-3 font-semibold">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($recentItems as $item)
          <tr class="hover:bg-blue-50/40 transition">
            <td class="px-4 py-3">
              @if($item->source_type === 'case')
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700">
                  <i class="fas fa-briefcase mr-1"></i> Kasus
                </span>
              @else
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700">
                  <i class="fas fa-users mr-1"></i> Publik
                </span>
              @endif
            </td>
            <td class="px-4 py-3 font-mono text-xs text-primary">
              {{ $item->source_type === 'case' ? Str::limit($item->case_number, 20) : Str::limit($item->tracking_token, 20) }}
            </td>
            <td class="px-4 py-3">
              {{ $item->source_type === 'case' ? ($item->petitioner_name ?? '-') : ($item->applicant_name ?? '-') }}
            </td>
            <td class="px-4 py-3">
              @include('components.status-badge', ['status' => $item->status])
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->updated_at->diffForHumans() }}</td>
            <td class="px-4 py-3">
              @if($item->source_type === 'case')
                <a href="{{ auth()->user()->hasAnyRole(['pa_management', 'super_admin']) ? route('dashboard.review.show', $item->id) : route('dashboard.cases.show', $item->id) }}"
                   class="text-primary hover:underline text-xs font-medium">
                  <i class="fas fa-eye mr-1"></i>Review
                </a>
              @else
                <a href="{{ route('dashboard.public-inbox.show', $item->id) }}"
                   class="text-purple-600 hover:underline text-xs font-medium">
                  <i class="fas fa-eye mr-1"></i>Review
                </a>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center py-8 text-gray-400">
              <i class="fas fa-inbox text-3xl mb-2 block"></i>
              Belum ada aktivitas.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
