@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Kasus Menunggu Validasi Disdukcapil</h1>
        <p class="text-gray-600 mt-2">Daftar permohonan yang perlu divalidasi oleh Disdukcapil</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-600 font-semibold">Total Menunggu</p>
            <p class="text-3xl font-bold text-blue-900">{{ $cases->total() }}</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-yellow-600 font-semibold">Per Halaman</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $cases->count() }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm text-green-600 font-semibold">Status</p>
            <p class="text-3xl font-bold text-green-900">{{ now()->format('H:i') }}</p>
        </div>
    </div>

    <!-- Error/Success Messages -->
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-800 font-semibold">Terjadi Kesalahan:</p>
            <ul class="list-disc list-inside text-red-700 text-sm mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <p class="text-green-800">✓ {{ session('success') }}</p>
        </div>
    @endif

    <!-- Cases Table -->
    @if($cases->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">No. Kasus</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Pemohon</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Pasangan</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Tanggal Diterima</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cases as $case)
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-blue-600">
                                    <a href="{{ route('dashboard.disdukcapil.show', $case->id) }}" class="hover:underline">
                                        {{ $case->case_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div>
                                        <p class="font-medium">{{ $case->petitioner_name }}</p>
                                        <p class="text-gray-500">{{ $case->petitioner_nik }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div>
                                        <p class="font-medium">{{ $case->spouse_name }}</p>
                                        <p class="text-gray-500">{{ $case->spouse_nik }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                                        {{ $case->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $case->submitted_at?->format('d M Y H:i') ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('dashboard.disdukcapil.show', $case->id) }}" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition">
                                        👁️ Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($cases->hasPages())
                <div class="bg-gray-50 border-t px-6 py-4">
                    {{ $cases->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p class="text-gray-600 text-lg font-medium">Tidak ada kasus yang menunggu validasi</p>
            <p class="text-gray-500 mt-2">Semua kasus sudah diproses</p>
        </div>
    @endif
</div>
@endsection
