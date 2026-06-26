@extends('layouts.admin')

@php
  $statusLabel = request('status') ? (config('workflow.states')[request('status')] ?? request('status')) : 'Semua';
  $isValidation = request('status') === 'DISDUKCAPIL_VALIDATION';
  $isCompleted = request('status') === 'COMPLETED';
  $isRejected = request('status') === 'REJECTED';
  $isDisdukcapil = auth()->user()->hasRole('disdukcapil_staff');
  
  $pageTitle = 'Daftar Kasus';
  if ($isDisdukcapil && $isValidation) {
    $pageTitle = 'List Validasi';
  } elseif ($isDisdukcapil && $isCompleted) {
    $pageTitle = 'List Kelola';
  } elseif ($isDisdukcapil && $isRejected) {
    $pageTitle = 'Kasus Ditolak';
  }
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <a href="{{ route('dashboard.cases') }}" class="hover:text-primary">Kasus</a>
  @if(request('status'))
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-800 font-medium">{{ $statusLabel }}</span>
  @endif
@endsection

@section('content')
<div class="space-y-4">

  {{-- Status Banner (jika filter) --}}
  @if(request('status'))
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
      <i class="fas fa-info-circle text-blue-600 text-lg flex-shrink-0 mt-0.5"></i>
      <div>
        <p class="font-semibold text-blue-900">Filter Status Aktif: <span class="text-blue-700">{{ $statusLabel }}</span></p>
        <p class="text-sm text-blue-700 mt-1">Menampilkan kasus dengan status "<strong>{{ $statusLabel }}</strong>"</p>
      </div>
    </div>
  @endif

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-gray-800">
        @if($isDisdukcapil && $isValidation)
          <i class="fas fa-hourglass-half text-amber-600 mr-2"></i>List Validasi
        @elseif($isDisdukcapil && $isCompleted)
          <i class="fas fa-check-circle text-green-600 mr-2"></i>List Kelola
        @elseif($isDisdukcapil && $isRejected)
          <i class="fas fa-times-circle text-red-600 mr-2"></i>Kasus Ditolak
        @else
          <i class="fas fa-folder mr-2 text-primary"></i>{{ $pageTitle }}
        @endif
      </h1>
    </div>
    @role('pa_assistant')
    <a href="{{ route('dashboard.cases.create') }}"
       class="bg-primary text-white rounded-xl px-4 py-2 text-sm font-medium hover:bg-primary-dark transition flex items-center gap-2">
      <i class="fas fa-plus"></i> Kasus Baru
    </a>
    @endrole
  </div>

  {{-- Tab Navigation untuk Disdukcapil Staff --}}
  @role('disdukcapil_staff')
  <div class="flex gap-2 border-b border-gray-200">
    <a href="{{ route('dashboard.cases', ['status' => 'DISDUKCAPIL_VALIDATION']) }}"
       class="px-4 py-3 text-sm font-medium transition border-b-2 {{ $isValidation ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-600 hover:text-gray-800' }}">
      <i class="fas fa-hourglass-half mr-1"></i>Validasi
      <span class="bg-amber-100 text-amber-700 rounded-full px-2 py-0.5 text-xs font-bold ml-1">{{ $counts['validation_pending'] ?? 0 }}</span>
    </a>
    <a href="{{ route('dashboard.cases', ['status' => 'COMPLETED']) }}"
       class="px-4 py-3 text-sm font-medium transition border-b-2 {{ $isCompleted ? 'border-green-500 text-green-600' : 'border-transparent text-gray-600 hover:text-gray-800' }}">
      <i class="fas fa-check-circle mr-1"></i>Kelola
      <span class="bg-green-100 text-green-700 rounded-full px-2 py-0.5 text-xs font-bold ml-1">{{ $counts['validation_completed'] ?? 0 }}</span>
    </a>
    <a href="{{ route('dashboard.cases', ['status' => 'REJECTED']) }}"
       class="px-4 py-3 text-sm font-medium transition border-b-2 {{ $isRejected ? 'border-red-500 text-red-600' : 'border-transparent text-gray-600 hover:text-gray-800' }}">
      <i class="fas fa-times-circle mr-1"></i>Ditolak
      <span class="bg-red-100 text-red-700 rounded-full px-2 py-0.5 text-xs font-bold ml-1">{{ $counts['validation_rejected'] ?? 0 }}</span>
    </a>
  </div>
  @endrole

  {{-- Filter Bar --}}
  <form method="GET" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-wrap gap-3 items-end">
    <div>
      <label class="block text-xs text-gray-500 mb-1">Jenis Pengajuan</label>
      <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="all" {{ request('type', 'all') === 'all' ? 'selected' : '' }}>Semua ({{ $counts['all'] ?? 0 }})</option>
        <option value="cases" {{ request('type') === 'cases' ? 'selected' : '' }}>Kasus Manual ({{ $counts['cases'] ?? 0 }})</option>
        <option value="public" {{ request('type') === 'public' ? 'selected' : '' }}>Pengajuan Publik ({{ $counts['public'] ?? 0 }})</option>
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">Filter Status</label>
      <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">Semua Status</option>
        @foreach(config('workflow.states', []) as $key => $label)
          <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <button type="submit" class="bg-primary text-white rounded-lg px-4 py-2 text-sm hover:bg-primary-dark transition">
      <i class="fas fa-filter mr-1"></i> Filter
    </button>
    <a href="{{ route('dashboard.cases') }}" class="text-gray-500 text-sm hover:text-red-500 py-2">Reset</a>
  </form>

  {{-- Cases + Public Submissions Table --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-gray-600 text-left">
          <th class="px-4 py-3 font-semibold">Jenis</th>
          <th class="px-4 py-3 font-semibold">No. Kasus/Token</th>
          <th class="px-4 py-3 font-semibold">Pemohon</th>
          <th class="px-4 py-3 font-semibold">Status</th>
          <th class="px-4 py-3 font-semibold">Tanggal</th>
          <th class="px-4 py-3 font-semibold">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($cases as $case)
        <tr class="hover:bg-blue-50/40 transition">
          <td class="px-4 py-3">
            @if($case->source_type === 'internal')
              <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700">
                <i class="fas fa-briefcase mr-1"></i> Kasus
              </span>
            @else
              <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700">
                <i class="fas fa-users mr-1"></i> Publik
              </span>
            @endif
          </td>
          <td class="px-4 py-3">
            <div class="font-mono text-xs text-primary">
              {{ $case->case_number }}
            </div>
            @if($case->source_type === 'public')
              <div class="text-xs text-gray-500 mt-1">{{ $case->tracking_token }}</div>
            @endif
          </td>
          <td class="px-4 py-3">
            <div>{{ $case->petitioner_name ?? '-' }}</div>
            @if($case->institution)
              <div class="text-xs text-gray-500">{{ $case->institution->name }}</div>
            @endif
            @if($case->spouse_name)
              <div class="text-xs text-gray-400">⚭ {{ $case->spouse_name }}</div>
            @endif
          </td>
          <td class="px-4 py-3">
            @include('components.status-badge', ['status' => $case->status])
          </td>
          <td class="px-4 py-3 text-gray-500 text-xs">
            <div>{{ $case->created_at->format('d/m/Y H:i') }}</div>
            <div class="text-gray-400">{{ $case->created_at->diffForHumans() }}</div>
          </td>
          <td class="px-4 py-3">
            <a href="{{ auth()->user()->hasAnyRole(['pa_management', 'super_admin']) ? route('dashboard.review.show', $case->id) : route('dashboard.cases.show', $case->id) }}"
               class="text-primary hover:underline text-xs font-medium">
              <i class="fas fa-eye mr-1"></i>Detail
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center py-12">
            @if($isValidation)
              <i class="fas fa-inbox text-4xl mb-3 block text-amber-200"></i>
              <p class="text-gray-400 font-medium">Tidak ada kasus yang menunggu validasi</p>
              <p class="text-gray-300 text-xs mt-1">Semua kasus sudah diproses atau belum ada pengiriman dari PA Management</p>
            @elseif($isCompleted)
              <i class="fas fa-check-circle text-4xl mb-3 block text-green-200"></i>
              <p class="text-gray-400 font-medium">Tidak ada kasus yang sudah divalidasi</p>
              <p class="text-gray-300 text-xs mt-1">Belum ada kasus yang berhasil divalidasi</p>
            @elseif($isRejected)
              <i class="fas fa-ban text-4xl mb-3 block text-red-200"></i>
              <p class="text-gray-400 font-medium">Tidak ada kasus yang ditolak</p>
              <p class="text-gray-300 text-xs mt-1">Semua kasus berhasil melalui proses validasi</p>
            @else
              <i class="fas fa-folder-open text-4xl mb-3 block text-gray-300"></i>
              <p class="text-gray-400 font-medium">Belum ada data kasus</p>
            @endif
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>

    {{-- Pagination --}}
    @if($cases->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
      {{ $cases->links() }}
    </div>
    @endif
  </div>

</div>
@endsection
