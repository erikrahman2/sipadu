@extends('layouts.admin')

@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
<div class="space-y-4">
  <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-shield-alt mr-2 text-yellow-500"></i>Audit Trail</h1>

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-gray-500 text-left">
          <th class="px-4 py-3">Waktu</th>
          <th class="px-4 py-3">User</th>
          <th class="px-4 py-3">Aksi</th>
          <th class="px-4 py-3">Subjek</th>
          <th class="px-4 py-3">IP</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($logs as $log)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-4 py-3 text-xs text-gray-400">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
          <td class="px-4 py-3 text-xs">{{ $log->user?->name ?? 'System' }}</td>
          <td class="px-4 py-3">
            <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $log->action }}</span>
          </td>
          <td class="px-4 py-3 text-xs text-gray-500">{{ $log->subject_type }}:{{ $log->subject_id }}</td>
          <td class="px-4 py-3 text-xs font-mono text-gray-400">{{ $log->ip_address }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center py-8 text-gray-300">Belum ada audit log.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="px-4 py-3 border-t">{{ $logs->links() }}</div>
  </div>
</div>
@endsection
