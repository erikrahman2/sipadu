@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('dashboard.disdukcapil.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Kembali ke Daftar
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Detail Kasus</h1>
            <p class="text-gray-600 mt-2">No. Kasus: <strong>{{ $case->case_number }}</strong></p>
        </div>
        <div class="text-right">
            <span class="inline-block bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-semibold">
                {{ $case->status }}
            </span>
        </div>
    </div>

    <!-- Case Info Grid -->
    <div class="grid grid-cols-2 gap-6 mb-6">
        <!-- Pemohon -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">👤 Informasi Pemohon</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-600">Nama</span>
                    <p class="font-medium text-gray-900">{{ $case->petitioner_name }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">NIK</span>
                    <p class="font-medium text-gray-900">{{ $case->petitioner_nik }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Alamat</span>
                    <p class="font-medium text-gray-900">{{ $case->petitioner_alamat }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Kontak</span>
                    <p class="font-medium text-gray-900">{{ $case->petitioner_phone }}</p>
                </div>
            </div>
        </div>

        <!-- Pasangan -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">👥 Informasi Pasangan</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-600">Nama</span>
                    <p class="font-medium text-gray-900">{{ $case->spouse_name }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">NIK</span>
                    <p class="font-medium text-gray-900">{{ $case->spouse_nik }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Alamat</span>
                    <p class="font-medium text-gray-900">{{ $case->spouse_alamat }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Kecamatan</span>
                    <p class="font-medium text-gray-900">{{ $case->spouse_kecamatan }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Permohonan Details -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Informasi Permohonan</h3>
        <div class="grid grid-cols-2 gap-6">
            <div>
                <span class="text-sm text-gray-600">Tanggal Perceraian</span>
                <p class="font-medium text-gray-900">{{ $case->divorce_date?->format('d M Y') ?? '-' }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-600">Nomor Putusan</span>
                <p class="font-medium text-gray-900">{{ $case->verdict_number }}</p>
            </div>
            <div class="col-span-2">
                <span class="text-sm text-gray-600">Catatan</span>
                <p class="font-medium text-gray-900">{{ $case->notes ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- OCR Validation Results -->
    @if($case->ocrValidations->count() > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">📄 Hasil Validasi OCR</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Dokumen</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Nama (OCR)</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Confidence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($case->ocrValidations as $validation)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm font-medium">{{ $validation->document->document_type }}</td>
                        <td class="px-4 py-2 text-sm">{{ $validation->ocr_nama ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if($validation->review_action === 'approve')
                                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">✓ APPROVED</span>
                            @else
                                <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-semibold">{{ strtoupper($validation->review_action) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm">{{ round(($validation->ocrResult->overall_confidence ?? 0) * 100, 1) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex gap-4">
        <a href="{{ route('dashboard.disdukcapil.process.show', $case->id) }}" 
           class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition inline-flex items-center gap-2">
            ✓ Validasi & Upload BAST
        </a>
        <a href="{{ route('dashboard.disdukcapil.index') }}" 
           class="bg-gray-400 hover:bg-gray-500 text-white font-semibold py-3 px-6 rounded-lg transition">
            Kembali
        </a>
    </div>
</div>
@endsection
