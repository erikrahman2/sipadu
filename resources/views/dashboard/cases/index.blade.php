@extends('layouts.admin')

@section('title', 'Daftar Kasus')
@section('page-title', 'Daftar Kasus')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Kasus</span>
@endsection

@section('content')
<div class="space-y-4">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-folder mr-2 text-primary"></i>Manajemen Kasus</h1>
    @role('pa_assistant')
    <a href="{{ route('dashboard.cases.create') }}"
       class="bg-primary text-white rounded-xl px-4 py-2 text-sm font-medium hover:bg-primary-dark transition flex items-center gap-2">
      <i class="fas fa-plus"></i> Kasus Baru
    </a>
    @endrole
  </div>

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
        <optgroup label="Kasus Manual">
          @foreach(config('workflow.states', []) as $key => $label)
            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </optgroup>
        <optgroup label="Pengajuan Publik">
          <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Menunggu Review</option>
          <option value="REVIEWING" {{ request('status') === 'REVIEWING' ? 'selected' : '' }}>Sedang Ditinjau</option>
        </optgroup>
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
            @if($case->source_type === 'case')
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
              {{ $case->source_type === 'case' ? $case->case_number : $case->tracking_token }}
            </div>
            @if($case->source_type === 'public' && $case->case_number)
              <div class="text-xs text-gray-500 mt-1">{{ $case->case_number }}</div>
            @endif
          </td>
          <td class="px-4 py-3">
            @if($case->source_type === 'case')
              <div>{{ $case->petitioner_name ?? '-' }}</div>
              @if($case->institution)
                <div class="text-xs text-gray-500">{{ $case->institution->name }}</div>
              @endif
              @if($case->spouse_name)
                <div class="text-xs text-gray-400">⚭ {{ $case->spouse_name }}</div>
              @endif
            @else
              <div>{{ $case->applicant_name ?? '-' }}</div>
              @if($case->spouse_name)
                <div class="text-xs text-gray-400">⚭ {{ $case->spouse_name }}</div>
              @endif
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
            @if($case->source_type === 'case')
              <a href="{{ auth()->user()->hasAnyRole(['pa_management', 'super_admin']) ? route('dashboard.review.show', $case->id) : route('dashboard.cases.show', $case->id) }}"
                 class="text-primary hover:underline text-xs font-medium">
                <i class="fas fa-eye mr-1"></i>Detail
              </a>
            @else
              <a href="{{ route('dashboard.public-inbox.show', $case->id) }}"
                 class="text-purple-600 hover:underline text-xs font-medium">
                <i class="fas fa-eye mr-1"></i>Review
              </a>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center py-12 text-gray-400">
            <i class="fas fa-folder-open text-4xl mb-3 block"></i>
            Belum ada data.
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
