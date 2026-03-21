@if(isset($cases) && $cases->isNotEmpty())
<table class="min-w-full text-sm">
  <thead>
    <tr class="bg-gray-50 text-gray-500 text-left">
      <th class="px-4 py-3 font-semibold">No. Kasus</th>
      <th class="px-4 py-3 font-semibold">Institusi</th>
      <th class="px-4 py-3 font-semibold">Status</th>
      <th class="px-4 py-3 font-semibold">Tanggal</th>
      <th class="px-4 py-3 font-semibold">Aksi</th>
    </tr>
  </thead>
  <tbody class="divide-y divide-gray-100">
    @foreach($cases as $case)
    <tr class="hover:bg-blue-50/30 transition">
      <td class="px-4 py-3 font-mono text-xs text-primary">{{ $case->case_number }}</td>
      <td class="px-4 py-3 text-xs">{{ $case->institution?->name ?? '-' }}</td>
      <td class="px-4 py-3">
        @include('components.status-badge', ['status' => $case->status])
      </td>
      <td class="px-4 py-3 text-xs text-gray-400">{{ $case->created_at->format('d/m/Y') }}</td>
      <td class="px-4 py-3">
        <a href="{{ route('dashboard.cases.show', $case->id) }}"
           class="text-primary hover:underline text-xs font-medium">Detail</a>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
@else
<div class="text-center py-8 text-gray-400 text-sm">
  <i class="fas fa-folder-open text-3xl mb-2 block"></i>
  Belum ada kasus.
</div>
@endif
