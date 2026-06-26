@extends('layouts.admin')

@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('content')
<div class="space-y-4">
  <h1 class="text-xl font-bold text-stone-800"><i class="fas fa-users mr-2 text-amber-700"></i>Manajemen User</h1>

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-gray-50 text-gray-500 text-left">
          <th class="px-4 py-3">Nama</th>
          <th class="px-4 py-3">Email</th>
          <th class="px-4 py-3">Role</th>
          <th class="px-4 py-3">Institusi</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Bergabung</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse($users as $user)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
          <td class="px-4 py-3 text-gray-500 text-xs">{{ $user->email }}</td>
          <td class="px-4 py-3">
            @foreach($user->roles as $role)
            <span class="bg-stone-200 text-stone-700 text-xs px-2 py-0.5 rounded-full">{{ $role->name }}</span>
            @endforeach
          </td>
          <td class="px-4 py-3 text-xs text-gray-500">{{ $user->institution?->name ?? '-' }}</td>
          <td class="px-4 py-3">
            @include('components.status-badge', ['status' => $user->status])
          </td>
          <td class="px-4 py-3 text-xs text-gray-400">{{ $user->created_at->format('d/m/Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center py-8 text-gray-300">Belum ada user.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="px-4 py-3 border-t">{{ $users->links() }}</div>
  </div>
</div>
@endsection
