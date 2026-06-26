@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Proses Validasi Disdukcapil</h1>
                <p class="text-gray-600 mt-2">No. Kasus: <strong>{{ $case->case_number }}</strong></p>
            </div>
            <div class="text-right">
                <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                    {{ $case->status }}
                </span>
            </div>
        </div>
    </div>

    <!-- Case Info -->
    <div class="grid grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Pemohon</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-600">Nama:</span>
                    <p class="font-medium">{{ $case->petitioner_name }}</p>
                </div>
                <div>
                    <span class="text-gray-600">NIK:</span>
                    <p class="font-medium">{{ $case->petitioner_nik }}</p>
                </div>
                <div>
                    <span class="text-gray-600">Alamat:</span>
                    <p class="font-medium">{{ $case->petitioner_alamat }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Pasangan</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-600">Nama:</span>
                    <p class="font-medium">{{ $case->spouse_name }}</p>
                </div>
                <div>
                    <span class="text-gray-600">NIK:</span>
                    <p class="font-medium">{{ $case->spouse_nik }}</p>
                </div>
                <div>
                    <span class="text-gray-600">Alamat:</span>
                    <p class="font-medium">{{ $case->spouse_alamat }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- OCR Validation Results Summary -->
    @if($case->ocrValidations->count() > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Hasil Validasi OCR</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Dokumen</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Confidence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($case->ocrValidations as $validation)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm">{{ $validation->document->document_type }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if($validation->review_action === 'approve')
                                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">✓ APPROVED</span>
                            @else
                                <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-semibold">⚠ {{ strtoupper($validation->review_action) }}</span>
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

    <!-- Upload Forms -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Upload Dokumen Serah Terima</h2>

        <form action="/dashboard/disdukcapil/cases/{{ $case->id }}/process" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- BAST Upload -->
            <div class="border-b pb-6">
                <label class="block text-lg font-semibold text-gray-900 mb-2">
                    📋 Berita Acara Serah Terima (BAST)
                </label>
                <p class="text-sm text-gray-600 mb-4">
                    Upload dokumen BAST dari Disdukcapil sebagai bukti penerimaan dokumen
                </p>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-400 transition">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <label class="cursor-pointer text-blue-600 hover:text-blue-800 font-semibold">
                            Pilih file BAST
                            <input type="file" name="bast_file" accept=".pdf,.doc,.docx" class="hidden" id="bast_input">
                        </label>
                        <p class="text-xs text-gray-500 mt-2">PDF, DOC, atau DOCX (Max 10MB)</p>
                    </div>
                </div>

                @if($bastDoc)
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800">
                            ✓ BAST sudah diupload: <strong>{{ $bastDoc->original_name }}</strong>
                        </p>
                    </div>
                @endif

                <div id="bast_preview" class="mt-3"></div>
            </div>

            <!-- Digital Documents Upload -->
            <div class="border-b pb-6">
                <label class="block text-lg font-semibold text-gray-900 mb-2">
                    📄 Dokumen Digital (Scan Dokumen Asli)
                </label>
                <p class="text-sm text-gray-600 mb-4">
                    Upload scan dokumen asli (KTP, Akta Cerai, Putusan PA) sebagai dokumen digital
                </p>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-400 transition">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <label class="cursor-pointer text-blue-600 hover:text-blue-800 font-semibold">
                            Pilih file dokumen (multiple)
                            <input type="file" name="digital_files[]" multiple accept=".pdf,.jpg,.png" class="hidden" id="digital_input">
                        </label>
                        <p class="text-xs text-gray-500 mt-2">PDF, JPG, atau PNG (Max 10MB per file)</p>
                    </div>
                </div>

                @if($digitalDocs->count() > 0)
                    <div class="mt-4 space-y-2">
                        @foreach($digitalDocs as $doc)
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-sm text-green-800">
                                    ✓ {{ $doc->original_name }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div id="digital_preview" class="mt-3 space-y-2"></div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-lg font-semibold text-gray-900 mb-2">
                    📝 Catatan
                </label>
                <textarea 
                    name="notes" 
                    rows="4" 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm"
                    placeholder="Tambahkan catatan atau komentar jika diperlukan..."
                ></textarea>
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <ul class="list-disc list-inside text-red-800 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Submit Button -->
            <div class="flex gap-4">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                    ✓ Selesaikan Validasi & Kirim ke PA Management
                </button>
                <a href="{{ route('dashboard.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white font-semibold py-3 px-6 rounded-lg transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    // Ensure form submits to correct URL
    const form = document.querySelector('form');
    
    // Force the action to be correct
    form.action = '/dashboard/disdukcapil/cases/{{ $case->id }}/process';
    form.method = 'POST';
    
    console.log('✓ Form action set to:', form.action);
    console.log('✓ Form method set to:', form.method);

    // Let form submit normally (no AJAX)
    form.addEventListener('submit', function(e) {
        console.log('✓ Form submitting to:', form.action);
        // Submit normally - don't preventDefault
    });

    // Preview BAST file
    document.getElementById('bast_input')?.addEventListener('change', function(e) {
        const preview = document.getElementById('bast_preview');
        preview.innerHTML = '';
        
        if (this.files.length > 0) {
            const file = this.files[0];
            preview.innerHTML = `
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        📄 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                    </p>
                </div>
            `;
        }
    });

    // Preview digital files
    document.getElementById('digital_input')?.addEventListener('change', function(e) {
        const preview = document.getElementById('digital_preview');
        preview.innerHTML = '';
        
        Array.from(this.files).forEach((file, index) => {
            preview.innerHTML += `
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        📄 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                    </p>
                </div>
            `;
        });
    });
</script>
@endsection
