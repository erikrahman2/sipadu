@extends('layouts.admin')

@section('title', 'Detail Kasus ' . $case->case_number)

@section('content')
<div class="max-w-5xl mx-auto" x-data="caseDetail({{ $case->id }})">

  {{-- Header dengan nama user --}}
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Detail Pengajuan</h1>
    <div class="flex items-center gap-2">
      <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
        {{ substr(auth()->user()->name, 0, 1) }}
      </div>
      <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
    </div>
  </div>

  {{-- Breadcrumb --}}
  <nav class="text-sm text-gray-500 mb-5">
    <span class="text-gray-700">Kotak Masuk</span>
    <span class="mx-2">/</span>
    <span class="font-mono text-gray-900 font-medium">{{ $case->case_number }}</span>
  </nav>

  {{-- Notifikasi --}}
  @if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm flex items-center gap-2">
      <i class="fas fa-check-circle"></i>
      <span>{{ session('success') }}</span>
    </div>
  @endif
  @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm flex items-center gap-2">
      <i class="fas fa-exclamation-circle"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  {{-- Token & Status Card --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
    <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">Token Tracking</div>
    <div class="font-mono text-lg font-bold text-blue-700 mb-3">{{ $case->tracking_token }}</div>

    @php
      $statusColors = [
        'DRAFT' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Draft'],
        'SUBMITTED' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Dikirim'],
        'OCR_PROCESSED' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'label' => 'OCR Selesai'],
        'PA_REVIEW' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Review PA'],
        'DISDUKCAPIL_VALIDATION' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => 'Validasi Disdukcapil'],
        'COMPLETED' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Selesai'],
        'REJECTED' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Ditolak'],
      ];
      $status = $statusColors[$case->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => $case->status];
    @endphp

    <span class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold {{ $status['bg'] }} {{ $status['text'] }}">
      {{ $status['label'] }}
    </span>

    <div class="mt-4 flex items-center gap-2 text-sm text-gray-600">
      <i class="far fa-clock text-gray-400"></i>
      <span>Dibuat</span>
      <span class="text-gray-800 font-medium">{{ $case->created_at->translatedFormat('d M Y, H:i') }}</span>
    </div>

    @if($case->submitted_at)
    <div class="mt-2 flex items-center gap-2 text-sm text-gray-600">
      <i class="far fa-paper-plane text-gray-400"></i>
      <span>Dikirim</span>
      <span class="text-gray-800 font-medium">{{ $case->submitted_at->translatedFormat('d M Y, H:i') }}</span>
    </div>
    @endif
  </div>

  {{-- Informasi Kasus --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
    <h3 class="font-semibold text-gray-800 text-base mb-4">
      <i class="fas fa-info-circle mr-2 text-blue-500"></i>Informasi Kasus
    </h3>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
      <div>
        <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nomor Kasus</dt>
        <dd class="font-mono text-base font-semibold text-gray-900">{{ $case->case_number }}</dd>
      </div>
      <div>
        <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Pemohon</dt>
        <dd class="text-base font-medium text-gray-900">{{ $case->submitter?->name }}</dd>
      </div>
      <div>
        <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Institusi</dt>
        <dd class="text-base text-gray-900">{{ $case->institution?->name }}</dd>
      </div>
      <div>
        <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Status</dt>
        <dd class="text-base text-gray-900">{{ $status['label'] }}</dd>
      </div>
    </dl>
  </div>

  {{-- Data Pemohon --}}
  @if($case->petitioner_name || $case->petitioner_nik)
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="bg-gradient-to-r from-blue-50 to-blue-100/50 px-6 py-4 border-b border-blue-200">
      <h3 class="font-semibold text-gray-800 text-base">Data Pemohon</h3>
    </div>
    <div class="p-6">
      <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
        @if($case->petitioner_nik)
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">NIK</dt>
          <dd class="font-mono text-base font-semibold text-gray-900">{{ $case->petitioner_nik }}</dd>
        </div>
        @endif
        @if($case->petitioner_name)
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nama Lengkap</dt>
          <dd class="text-base font-medium text-gray-900">{{ $case->petitioner_name }}</dd>
        </div>
        @endif
        @if($case->petitioner_phone)
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nomor Telepon</dt>
          <dd class="font-mono text-base text-gray-900">{{ $case->petitioner_phone }}</dd>
        </div>
        @endif
      </dl>
    </div>
  </div>
  @endif

  {{-- Data Perceraian --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="bg-gradient-to-r from-purple-50 to-purple-100/50 px-6 py-4 border-b border-purple-200">
      <h3 class="font-semibold text-gray-800 text-base">Data Perceraian</h3>
    </div>
    <div class="p-6">
      <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nama Mantan Pasangan</dt>
          <dd class="text-base text-gray-900">{{ $case->spouse_name ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">NIK Mantan Pasangan</dt>
          <dd class="font-mono text-base text-gray-900">{{ $case->spouse_nik ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Tanggal Cerai</dt>
          <dd class="text-base text-gray-900">{{ $case->divorce_date?->translatedFormat('d F Y') ?? '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Nomor Putusan PA</dt>
          <dd class="text-base text-gray-900">{{ $case->verdict_number ?? '-' }}</dd>
        </div>
        @if($case->notes)
        <div class="col-span-2">
          <dt class="text-xs text-gray-500 uppercase tracking-wide mb-1.5">Catatan</dt>
          <dd class="text-sm text-gray-700 bg-gray-50 rounded-lg p-4 whitespace-pre-wrap leading-relaxed">{{ $case->notes }}</dd>
        </div>
        @endif
      </dl>
    </div>
  </div>

  {{-- Dokumen Diunggah --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="bg-gradient-to-r from-emerald-50 to-emerald-100/50 px-6 py-4 border-b border-emerald-200 flex items-center justify-between">
      <h3 class="font-semibold text-gray-800 text-base">Dokumen Diunggah ({{ $case->documents->count() }})</h3>
      @if(in_array($case->status, ['DRAFT','SUBMITTED']) && auth()->user()->hasAnyRole(['pa_assistant','pa_staff']))
      <a href="{{ route('dashboard.upload') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
        <i class="fas fa-upload mr-1"></i>Upload Dokumen
      </a>
      @endif
    </div>
    <ul class="divide-y divide-gray-100">
      @forelse($case->documents as $doc)
        <li class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl {{ $doc->mime_type === 'application/pdf' ? 'bg-red-100' : 'bg-blue-100' }} flex items-center justify-center flex-shrink-0">
              <i class="fas fa-{{ $doc->mime_type === 'application/pdf' ? 'file-pdf text-red-500' : 'image text-blue-500' }} text-lg"></i>
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-900 mb-0.5 flex items-center gap-2">
                {{ $doc->document_type }}
                @if($doc->status)
                  @php
                    $docStatusColors = [
                      'PENDING' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700'],
                      'PROCESSING' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
                      'OCR_SUCCESS' => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
                      'OCR_FAILED' => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
                      'VERIFIED' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
                    ];
                    $docStatus = $docStatusColors[$doc->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700'];
                  @endphp
                  <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $docStatus['bg'] }} {{ $docStatus['text'] }}">
                    {{ $doc->status }}
                  </span>
                @endif
              </div>
              <div class="text-xs text-gray-500">
                {{ $doc->original_name }} · 
                <span class="text-gray-400">{{ number_format($doc->size_bytes / 1024, 1) }} KB</span>
                @if($doc->ocrResult)
                <span class="mx-1">·</span>
                <a href="{{ route('dashboard.ocr.result', $doc->id) }}" class="text-blue-600 hover:text-blue-700">
                  <i class="fas fa-microscope mr-1"></i>Lihat OCR
                </a>
                @endif
              </div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">{{ $doc->created_at->translatedFormat('d M Y') }}</span>
            <a href="{{ route('dashboard.ocr.result', $doc->id) }}" 
               class="text-gray-400 hover:text-blue-600 transition">
              <i class="fas fa-download"></i>
            </a>
          </div>
        </li>
      @empty
        <li class="px-6 py-8 text-center text-gray-400 text-sm">
          <i class="far fa-folder-open text-3xl mb-2 opacity-30"></i>
          <div>Tidak ada dokumen</div>
        </li>
      @endforelse
    </ul>
  </div>

  {{-- Hasil Validasi OCR (PA Management Only) --}}
  @if(auth()->user()->hasAnyRole(['pa_management', 'super_admin']) && $case->ocrValidations->isNotEmpty())
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="bg-gradient-to-r from-indigo-50 to-indigo-100/50 px-6 py-4 border-b border-indigo-200 flex items-center justify-between">
      <h3 class="font-semibold text-gray-800 text-base">
        <i class="fas fa-microscope mr-2 text-indigo-600"></i>Hasil Validasi OCR
        <span class="ml-2 text-xs font-normal text-gray-600">({{ $case->ocrValidations->count() }} dokumen)</span>
      </h3>
      <a href="{{ route('dashboard.review.show', $case->id) }}" 
         class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
        <i class="fas fa-external-link-alt mr-1"></i>Detail Lengkap
      </a>
    </div>
    
    <div class="divide-y divide-gray-100">
      @foreach($case->ocrValidations as $validation)
        <div class="p-6 hover:bg-gray-50/50 transition">
          {{-- Header Validation --}}
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-alt text-indigo-600"></i>
              </div>
              <div>
                <div class="text-sm font-semibold text-gray-900">
                  {{ $validation->document ? strtoupper(str_replace('_', ' ', $validation->document->document_type)) : 'Unknown' }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ $validation->created_at->translatedFormat('d M Y, H:i') }}
                </div>
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              {{-- Status Badge --}}
              @php
                $statusBadge = match($validation->validation_status) {
                  'MATCH' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => 'check-circle', 'label' => 'Match'],
                  'PARTIAL_MATCH' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'exclamation-triangle', 'label' => 'Partial Match'],
                  'MANUAL_REVIEW' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'user-check', 'label' => 'Manual Review'],
                  'MISMATCH' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'times-circle', 'label' => 'Mismatch'],
                  default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => 'question-circle', 'label' => 'Unknown'],
                };
              @endphp
              <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusBadge['bg'] }} {{ $statusBadge['text'] }}">
                <i class="fas fa-{{ $statusBadge['icon'] }} mr-1"></i>
                {{ $statusBadge['label'] }}
              </span>
              
              {{-- Match Score --}}
              <div class="flex items-center gap-2 bg-gray-50 px-3 py-1 rounded-full">
                <span class="text-xs text-gray-600">Score:</span>
                <span class="text-sm font-bold {{ $validation->overall_match_score >= 95 ? 'text-green-600' : ($validation->overall_match_score >= 80 ? 'text-yellow-600' : 'text-red-600') }}">
                  {{ number_format($validation->overall_match_score, 1) }}%
                </span>
              </div>
            </div>
          </div>

          {{-- Field Comparison Summary --}}
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              @php
                $fieldLabels = [
                  'nik' => 'NIK',
                  'nama' => 'Nama',
                  'tempat_lahir' => 'Tempat Lahir',
                  'tgl_lahir' => 'Tanggal Lahir',
                  'alamat' => 'Alamat',
                ];
                $comparisonResults = $validation->comparison_results ?? [];
                $displayFields = array_slice($fieldLabels, 0, 6, true); // Tampilkan 6 field utama
              @endphp
              
              @foreach($displayFields as $fieldKey => $fieldLabel)
                @php
                  $inputValue = $validation->{"input_$fieldKey"} ?? null;
                  $ocrValue = $validation->{"ocr_$fieldKey"} ?? null;
                  $matchScore = $comparisonResults[$fieldKey] ?? 0;
                  $isMatch = $matchScore >= 95;
                  $isMismatch = $matchScore < 80;
                @endphp
                
                @if($inputValue || $ocrValue)
                <div class="flex items-start gap-2">
                  {{-- Icon Status --}}
                  <div class="mt-0.5">
                    @if($isMatch)
                      <i class="fas fa-check-circle text-green-500 text-sm"></i>
                    @elseif($isMismatch)
                      <i class="fas fa-exclamation-circle text-red-500 text-sm"></i>
                    @else
                      <i class="fas fa-minus-circle text-yellow-500 text-sm"></i>
                    @endif
                  </div>
                  
                  {{-- Field Info --}}
                  <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-gray-600 mb-1">
                      {{ $fieldLabel }}
                      @if($fieldKey === 'nik')
                        <span class="ml-1 px-1.5 py-0.5 bg-red-100 text-red-600 text-[10px] rounded font-bold">CRITICAL</span>
                      @endif
                    </div>
                    <div class="flex items-center gap-2">
                      {{-- Input Value --}}
                      <div class="flex-1 min-w-0">
                        <div class="text-xs text-gray-500 mb-0.5">Input:</div>
                        <div class="text-sm {{ $isMismatch ? 'text-red-600 font-medium' : 'text-gray-900' }} truncate">
                          {{ $inputValue ?: '-' }}
                        </div>
                      </div>
                      
                      {{-- Arrow --}}
                      <i class="fas fa-arrow-right text-xs text-gray-300 flex-shrink-0"></i>
                      
                      {{-- OCR Value --}}
                      <div class="flex-1 min-w-0">
                        <div class="text-xs text-gray-500 mb-0.5">OCR:</div>
                        <div class="text-sm {{ $isMismatch ? 'text-red-600 font-medium' : 'text-gray-900' }} truncate">
                          {{ $ocrValue ?: '-' }}
                        </div>
                      </div>
                      
                      {{-- Match Score --}}
                      <div class="flex-shrink-0 text-right">
                        <div class="text-xs font-bold {{ $isMatch ? 'text-green-600' : ($isMismatch ? 'text-red-600' : 'text-yellow-600') }}">
                          {{ number_format($matchScore, 0) }}%
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                @endif
              @endforeach
            </div>
          </div>

          {{-- Review Status --}}
          @if($validation->is_reviewed)
            <div class="mt-4 flex items-start gap-3 bg-green-50 border border-green-200 rounded-lg p-3">
              <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-green-800">
                  Sudah Direview - {{ ucfirst($validation->review_action) }}
                </div>
                <div class="text-xs text-green-700 mt-1">
                  <span class="font-medium">{{ $validation->reviewer->name ?? 'Unknown' }}</span>
                  · {{ $validation->reviewed_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                </div>
                @if($validation->review_notes)
                  <div class="text-xs text-green-700 mt-2 italic">
                    "{{ $validation->review_notes }}"
                  </div>
                @endif
              </div>
            </div>
          @else
            <div class="mt-4 flex items-center justify-between bg-yellow-50 border border-yellow-200 rounded-lg p-3">
              <div class="flex items-center gap-2 text-yellow-800">
                <i class="fas fa-clock"></i>
                <span class="text-sm font-medium">Menunggu Review PA Management</span>
              </div>
              <a href="{{ route('dashboard.review.show', $case->id) }}" 
                 class="text-sm text-yellow-700 hover:text-yellow-800 font-medium">
                Review Sekarang →
              </a>
            </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Timeline --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
    <h3 class="font-semibold text-gray-800 text-base mb-4">
      <i class="fas fa-history mr-2 text-purple-500"></i>Timeline Proses
    </h3>
    <ol class="relative border-l border-gray-200 space-y-4 ml-3">
      @forelse($case->transitions as $t)
      <li class="ml-4">
        <span class="absolute -left-1.5 mt-1 w-3 h-3 bg-blue-600 rounded-full border-2 border-white"></span>
        <div>
          <p class="text-xs text-gray-500">{{ $t->created_at->translatedFormat('d M Y, H:i') }}</p>
          <p class="text-sm font-medium mt-1">
            <span class="text-gray-500">{{ $t->from_state }}</span>
            <i class="fas fa-arrow-right text-xs mx-1 text-gray-400"></i>
            <span class="text-blue-700">{{ $t->to_state }}</span>
          </p>
          @if($t->reason)
          <p class="text-xs text-gray-600 mt-1 italic bg-gray-50 rounded px-2 py-1 inline-block">"{{ $t->reason }}"</p>
          @endif
          <p class="text-xs text-gray-500 mt-1">oleh {{ $t->actor?->name }}</p>
        </div>
      </li>
      @empty
      <li class="ml-4 text-sm text-gray-400 py-4">Belum ada aktivitas</li>
      @endforelse
    </ol>
  </div>

  {{-- Tindakan --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-800 text-base mb-4">Tindakan</h3>

    <div class="flex flex-col sm:flex-row gap-3">
      {{-- Submit --}}
      @if($case->status === 'DRAFT' && auth()->user()->id === $case->submitter_id)
        <button @click="submitCase()"
          class="flex-1 py-3 text-sm font-semibold bg-blue-700 text-white rounded-xl hover:bg-blue-800 transition flex items-center justify-center gap-2">
          <i class="fas fa-paper-plane"></i>
          <span>Submit Kasus</span>
        </button>
      @endif

      {{-- PA Review --}}
      @if($case->status === 'PA_REVIEW' && auth()->user()->hasAnyRole(['pa_management','pa_staff']))
        <button @click="review('approve')"
          class="flex-1 py-3 text-sm font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition flex items-center justify-center gap-2">
          <i class="fas fa-check"></i>
          <span>Setujui (PA)</span>
        </button>
        <button @click="showRejectModal = true"
          class="flex-1 py-3 text-sm font-semibold border-2 border-red-300 text-red-600 rounded-xl hover:bg-red-50 transition flex items-center justify-center gap-2">
          <i class="fas fa-times"></i>
          <span>Tolak</span>
        </button>
      @endif

      {{-- Disdukcapil Validate --}}
      @if($case->status === 'DISDUKCAPIL_VALIDATION' && auth()->user()->hasRole('disdukcapil_staff'))
        <button @click="validate('validate')"
          class="flex-1 py-3 text-sm font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition flex items-center justify-center gap-2">
          <i class="fas fa-stamp"></i>
          <span>Validasi Disdukcapil</span>
        </button>
        <button @click="showRejectModal = true"
          class="flex-1 py-3 text-sm font-semibold border-2 border-red-300 text-red-600 rounded-xl hover:bg-red-50 transition flex items-center justify-center gap-2">
          <i class="fas fa-times"></i>
          <span>Tolak</span>
        </button>
      @endif

      @if(!in_array($case->status, ['COMPLETED', 'REJECTED']))
        @if(!$case->status === 'DRAFT' && !$case->status === 'PA_REVIEW' && !$case->status === 'DISDUKCAPIL_VALIDATION')
          <div class="text-sm text-gray-500 text-center py-3">
            Status saat ini: <span class="font-semibold">{{ $status['label'] }}</span>
          </div>
        @endif
      @endif
    </div>
  </div>

  {{-- Reject Modal --}}
  <div x-show="showRejectModal" 
       x-cloak
       style="display: none"
       @click.self="showRejectModal = false"
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md" @click.stop>
      <h3 class="text-lg font-bold text-gray-900 mb-4">Alasan Penolakan</h3>
      <textarea x-model="rejectReason" rows="3" placeholder="Jelaskan alasan penolakan..."
                class="w-full px-4 py-2 border border-gray-300 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
      <div class="flex gap-3 mt-4">
        <button @click="rejectCase()" class="flex-1 py-2 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 transition">Tolak</button>
        <button @click="showRejectModal = false" class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition">Batal</button>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
function caseDetail(caseId) {
  return {
    caseId,
    showRejectModal: false,
    rejectReason: '',

    async submitCase() {
      const res = await this.apiCall('POST', `/api/v1/review/submit/${caseId}`);
      if (res.ok) {
        location.reload();
      } else {
        alert('Gagal submit kasus');
      }
    },

    async review(decision) {
      const res = await this.apiCall('POST', '/api/v1/review/pa', { case_id: caseId, decision });
      if (res.ok) {
        location.reload();
      } else {
        alert('Gagal review kasus');
      }
    },

    async validate(decision) {
      const res = await this.apiCall('POST', '/api/v1/review/disdukcapil', { case_id: caseId, decision });
      if (res.ok) {
        location.reload();
      } else {
        alert('Gagal validasi kasus');
      }
    },

    async rejectCase() {
      if (!this.rejectReason.trim()) {
        alert('Alasan penolakan harus diisi');
        return;
      }
      
      const endpoint = '{{ in_array($case->status, ["PA_REVIEW"]) ? "/api/v1/review/pa" : "/api/v1/review/disdukcapil" }}';
      const body = { case_id: caseId, decision: 'reject', notes: this.rejectReason };
      const res = await this.apiCall('POST', endpoint, body);
      
      if (res.ok) {
        location.reload();
      } else {
        alert('Gagal menolak kasus');
      }
    },

    async apiCall(method, url, body = null) {
      const opts = {
        method,
        headers: { 
          'Content-Type': 'application/json', 
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
        },
      };
      if (body) opts.body = JSON.stringify(body);
      return await fetch(url, opts);
    }
  };
}
</script>
@endpush
@endsection
