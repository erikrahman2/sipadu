@extends('layouts.admin')

@section('title', 'Kotak Masuk Pengajuan Publik')
@section('page-title', 'Pengajuan Publik')

@section('content')
<div class="space-y-6">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-gray-800">Pengajuan Publik Masuk</h2>
      <p class="text-sm text-gray-500 mt-1">Pengajuan pembaruan dokumen yang dikirim warga tanpa akun.</p>
    </div>
  </div>

  {{-- Statistik --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    @foreach(['PENDING' => 'yellow', 'REVIEWING' => 'blue', 'WAITING_OCR' => 'indigo', 'APPROVED' => 'green', 'REJECTED' => 'red', 'COMPLETED' => 'emerald'] as $s => $c)
      <a href="{{ request()->fullUrlWithQuery(['status' => $s]) }}"
         class="bg-white border rounded-xl px-4 py-3 text-center hover:shadow transition
                {{ request('status') === $s ? 'ring-2 ring-'.$c.'-400' : '' }}">
        <div class="text-2xl font-bold text-{{ $c }}-600">{{ $counts[$s] ?? 0 }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ \App\Models\PublicSubmission::make(['status'=>$s])->statusLabel() }}</div>
      </a>
    @endforeach
  </div>

  {{-- Filter & Pencarian --}}
  <form method="GET" class="flex gap-3 mb-5 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
      placeholder="NIK / Nama / Token..."
      class="flex-1 min-w-[200px] px-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
    <select name="status" class="px-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">Semua Status</option>
      @foreach(['PENDING','REVIEWING','WAITING_OCR','APPROVED','REJECTED','COMPLETED'] as $s)
        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
      @endforeach
    </select>
    <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-xl text-sm hover:bg-blue-800 transition">
      <i class="fas fa-search mr-1"></i> Cari
    </button>
    <a href="{{ route('dashboard.public-inbox.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-xl text-sm hover:bg-gray-50 transition">Reset</a>
  </form>

  {{-- Tabel --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase text-xs">
          <tr>
            <th class="px-5 py-3 text-left">Token</th>
            <th class="px-5 py-3 text-left">Nama / NIK</th>
            <th class="px-5 py-3 text-left">No. WA</th>
            <th class="px-5 py-3 text-left">Dok.</th>
            <th class="px-5 py-3 text-left">Status</th>
            <th class="px-5 py-3 text-left">WA</th>
            <th class="px-5 py-3 text-left">Masuk</th>
            <th class="px-5 py-3 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @forelse($submissions as $sub)
            @php
              $statusColors = [
                'PENDING' => 'yellow', 'REVIEWING' => 'blue', 'WAITING_OCR' => 'indigo',
                'APPROVED' => 'green', 'REJECTED' => 'red', 'COMPLETED' => 'emerald',
              ];
              $c = $statusColors[$sub->status] ?? 'gray';
              $waColors = ['sent'=>'emerald','delivered'=>'emerald','failed'=>'red','pending'=>'gray'];
              $wc = $waColors[$sub->wa_status] ?? 'gray';
            @endphp
            <tr class="hover:bg-gray-50">
              <td class="px-5 py-3 font-mono text-xs text-blue-700">{{ $sub->tracking_token }}</td>
              <td class="px-5 py-3">
                <div class="font-medium text-gray-900">{{ $sub->petitioner_name }}</div>
                <div class="text-xs text-gray-400 font-mono">{{ $sub->nik }}</div>
              </td>
              <td class="px-5 py-3 text-gray-700 font-mono text-xs">{{ $sub->phone_wa }}</td>
              <td class="px-5 py-3 text-center text-gray-600">{{ $sub->documents->count() }}</td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $c }}-100 text-{{ $c }}-800">
                  {{ $sub->statusLabel() }}
                </span>
              </td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $wc }}-100 text-{{ $wc }}-800">
                  {{ strtoupper($sub->wa_status) }}
                </span>
              </td>
              <td class="px-5 py-3 text-gray-500 text-xs whitespace-nowrap">
                {{ $sub->created_at->diffForHumans() }}
              </td>
              <td class="px-5 py-3">
                <a href="{{ route('dashboard.public-inbox.show', $sub->id) }}"
                  class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                  Detail →
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-5 py-10 text-center text-gray-400">
                <i class="fas fa-inbox text-3xl mb-2 block"></i>
                Tidak ada pengajuan ditemukan.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($submissions->hasPages())
      <div class="px-5 py-4 border-t border-gray-100">
        {{ $submissions->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
