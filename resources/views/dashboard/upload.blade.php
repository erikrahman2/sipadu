@extends('layouts.admin')

@section('title', 'Upload Dokumen')
@section('page-title', 'Upload Dokumen')

@section('breadcrumb')
  <a href="{{ route('dashboard.index') }}" class="hover:text-primary"><i class="fas fa-home"></i></a>
  <i class="fas fa-chevron-right text-xs"></i>
  <span class="text-gray-800 font-medium">Upload Dokumen</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="uploadForm()">

  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
    <h1 class="text-xl font-bold text-gray-800 mb-6">
      <i class="fas fa-upload mr-2 text-green-500"></i>Upload Dokumen Kependudukan
    </h1>

    <form @submit.prevent="submitUpload" enctype="multipart/form-data">

      {{-- Case Selection --}}
      <div class="mb-5">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Kasus <span class="text-red-500">*</span>
        </label>
        <select x-model="form.case_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
          <option value="">-- Pilih Kasus --</option>
          @foreach($cases as $case)
            <option value="{{ $case->id }}">{{ $case->case_number }}</option>
          @endforeach
        </select>
      </div>

      {{-- Document Type --}}
      <div class="mb-5">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Jenis Dokumen <span class="text-red-500">*</span>
        </label>
        <select x-model="form.document_type" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
          <option value="">-- Pilih Jenis --</option>
          <option value="KTP">KTP (Kartu Tanda Penduduk)</option>
          <option value="KK">KK (Kartu Keluarga)</option>
          <option value="AKTA_CERAI">Akta Cerai</option>
          <option value="PUTUSAN_PA">Putusan Pengadilan Agama</option>
          <option value="AKTA_NIKAH">Akta Nikah</option>
          <option value="SURAT_PENGANTAR">Surat Pengantar</option>
          <option value="OTHER">Lainnya</option>
        </select>
      </div>

      {{-- File Upload Area --}}
      <div class="mb-5">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          File Dokumen <span class="text-red-500">*</span>
        </label>
        <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-2xl cursor-pointer transition"
               :class="selectedFile ? 'border-green-400 bg-green-50' : 'border-gray-200 bg-gray-50 hover:border-primary hover:bg-blue-50'">
          <div class="flex flex-col items-center text-center">
            <i class="fas text-3xl mb-2" :class="selectedFile ? 'fa-file-check text-green-400' : 'fa-cloud-upload-alt text-gray-300'"></i>
            <p class="text-sm" x-show="!selectedFile" class="text-gray-400">Klik atau drag & drop file di sini</p>
            <p class="text-sm font-medium text-green-600" x-show="selectedFile" x-text="selectedFile?.name"></p>
            <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF, TIFF — maks. 10MB</p>
          </div>
          <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.tiff"
                 @change="handleFile($event)" />
        </label>
      </div>

      {{-- OCR Option --}}
      <div class="flex items-center gap-3 mb-6 bg-blue-50 rounded-xl px-4 py-3">
        <input type="checkbox" id="auto_ocr" x-model="form.auto_ocr" class="w-4 h-4 text-primary" />
        <label for="auto_ocr" class="text-sm font-medium text-gray-700">
          Proses OCR otomatis setelah upload
          <span class="text-xs text-gray-400 block">Ekstraksi data (NIK, No. KK, Nama) dari gambar</span>
        </label>
      </div>

      {{-- Error --}}
      <div x-show="errorMsg" x-text="errorMsg" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"></div>

      {{-- Progress --}}
      <div x-show="uploading" class="mb-4">
        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full bg-primary transition-all duration-300 rounded-full" :style="`width: ${progress}%`"></div>
        </div>
        <p class="text-xs text-gray-400 mt-1 text-center" x-text="`Mengupload... ${progress}%`"></p>
      </div>

      {{-- Result --}}
      <div x-show="result" class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm">
        <p class="font-semibold">Dokumen berhasil diupload!</p>
        <template x-if="form.auto_ocr">
          <p class="mt-1">OCR sedang diproses di latar belakang. Cek OCR Result untuk hasilnya.</p>
        </template>
      </div>

      <button type="submit" :disabled="uploading || !form.case_id || !form.document_type || !selectedFile"
              class="w-full bg-primary text-white rounded-xl py-3 font-medium hover:bg-primary-dark transition flex items-center justify-center gap-2 disabled:opacity-60">
        <i class="fas fa-spinner fa-spin" x-show="uploading"></i>
        <i class="fas fa-upload" x-show="!uploading"></i>
        <span x-text="uploading ? 'Mengupload...' : 'Upload Dokumen'"></span>
      </button>
    </form>
  </div>

</div>

@push('scripts')
<script>
function uploadForm() {
  return {
    form: { case_id: '', document_type: '', auto_ocr: false },
    selectedFile: null,
    uploading: false,
    progress: 0,
    errorMsg: '',
    result: null,

    handleFile(event) {
      this.selectedFile = event.target.files[0] || null;
    },

    async submitUpload() {
      if (!this.selectedFile) return;
      this.uploading = true;
      this.errorMsg = '';
      this.result = null;
      this.progress = 0;

      const formData = new FormData();
      formData.append('case_id', this.form.case_id);
      formData.append('document_type', this.form.document_type);
      formData.append('file', this.selectedFile);

      try {
        const xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', e => {
          if (e.lengthComputable) this.progress = Math.round(e.loaded / e.total * 100);
        });

        const res = await new Promise((resolve, reject) => {
          xhr.open('POST', '/api/v1/documents/upload');
          xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
          xhr.setRequestHeader('Accept', 'application/json');
          xhr.onload = () => resolve(xhr);
          xhr.onerror = () => reject(new Error('Network error'));
          xhr.send(formData);
        });

        const data = JSON.parse(res.responseText);
        if (res.status >= 400) {
          this.errorMsg = data.message || JSON.stringify(data.errors);
        } else {
          this.result = data;
          if (this.form.auto_ocr && data.document?.id) {
            await this.triggerOcr(data.document.id);
          }
        }
      } catch(e) {
        this.errorMsg = 'Gagal upload: ' + e.message;
      } finally {
        this.uploading = false;
      }
    },

    async triggerOcr(documentId) {
      await fetch('/api/v1/ocr/process', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                   'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ document_id: documentId }),
      });
    }
  };
}
</script>
@endpush
@endsection
