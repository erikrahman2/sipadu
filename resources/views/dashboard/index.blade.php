@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumb')
  <i class="fas fa-home text-primary"></i>
  <span class="text-gray-800 font-medium">Dashboard</span>
@endsection

@section('content')
<div class="space-y-10">

  {{-- Disdukcapil Staff: Dashboard Validasi --}}
  @role('disdukcapil_staff')
    {{-- Welcome Banner --}}
    <div class="bg-gradient-to-r from-primary-dark to-primary rounded-2xl p-6 text-white shadow-lg">
      <h1 class="text-2xl font-bold">Selamat datang, {{ auth()->user()->name }} 👋</h1>
      <p class="text-blue-200 mt-1">
        {{ auth()->user()->institution?->name ?? 'Sistem SiPadu' }}
        &nbsp;·&nbsp;
        <span class="bg-white/20 rounded-full px-2 py-0.5 text-xs font-medium">Disdukcapil Staff</span>
      </p>
    </div>

    {{-- Stat Cards untuk Disdukcapil Staff --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      {{-- Menunggu Validasi --}}
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">Menunggu Validasi</p>
            <p class="text-3xl font-bold text-amber-600 mt-2">{{ $stats['validation_pending'] ?? 0 }}</p>
          </div>
          <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
            <i class="fas fa-hourglass-end text-2xl text-amber-600"></i>
          </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
          <a href="{{ route('dashboard.cases', ['status' => 'DISDUKCAPIL_VALIDATION']) }}" class="text-amber-600 text-sm font-semibold hover:text-amber-700 transition flex items-center gap-1">
            Lihat Daftar <i class="fas fa-arrow-right text-xs"></i>
          </a>
        </div>
      </div>

      {{-- Sudah Divalidasi --}}
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">Sudah Divalidasi</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['completed'] ?? 0 }}</p>
          </div>
          <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
            <i class="fas fa-check-circle text-2xl text-green-600"></i>
          </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
          <p class="text-gray-500 text-sm">Bulan ini</p>
        </div>
      </div>

      {{-- Ditolak --}}
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">Ditolak</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $stats['rejected'] ?? 0 }}</p>
          </div>
          <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
            <i class="fas fa-times-circle text-2xl text-red-600"></i>
          </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
          <p class="text-gray-500 text-sm">Total</p>
        </div>
      </div>
    </div>

    {{-- Tabel Kasus Menunggu Validasi --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <i class="fas fa-check-double text-primary text-xl"></i>
          <h3 class="font-semibold text-gray-800">Kasus Menunggu Validasi</h3>
          @if($stats['validation_pending'] > 0)
            <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-2 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
              {{ $stats['validation_pending'] }}
            </span>
          @endif
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-gray-500 text-left border-b border-gray-100">
              <th class="px-6 py-3 font-medium">No. Kasus</th>
              <th class="px-6 py-3 font-medium">Nama Pemohon</th>
              <th class="px-6 py-3 font-medium">Nama Pasangan</th>
              <th class="px-6 py-3 font-medium">Tgl Perceraian</th>
              <th class="px-6 py-3 font-medium">Diajukan Pada</th>
              <th class="px-6 py-3 font-medium text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @php
              $validationCases = \App\Models\CaseModel::forUser(auth()->user())
                ->byStatus('DISDUKCAPIL_VALIDATION')
                ->with('submitter:id,name')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
            @endphp
            @forelse($validationCases as $case)
              <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                  <span class="font-semibold text-gray-800">{{ $case->case_number }}</span>
                  <br>
                  <span class="text-xs text-gray-400">{{ $case->tracking_token }}</span>
                  @if($case->source_type === 'public')
                    <br>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 mt-1">
                      <i class="fas fa-globe text-xs"></i>
                      Pengajuan Publik
                    </span>
                  @endif
                </td>
                <td class="px-6 py-4">
                  <p class="font-medium text-gray-800">{{ $case->petitioner_name }}</p>
                  <p class="text-xs text-gray-500">{{ $case->petitioner_nik }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="font-medium text-gray-800">{{ $case->spouse_name }}</p>
                  <p class="text-xs text-gray-500">{{ $case->spouse_nik }}</p>
                </td>
                <td class="px-6 py-4">
                  {{ $case->divorce_date ? $case->divorce_date->format('d/m/Y') : '—' }}
                </td>
                <td class="px-6 py-4 text-gray-600">
                  {{ $case->submitted_at ? $case->submitted_at->format('d/m/Y H:i') : '—' }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-center gap-2">
                    <a href="{{ route('dashboard.cases.show', $case->id) }}" 
                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition" 
                       title="Lihat Detail">
                      <i class="fas fa-eye text-sm"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-8 text-gray-400">
                  <i class="fas fa-inbox text-3xl mb-2 block"></i>
                  Tidak ada kasus yang menunggu validasi
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Workflow Information & Guidelines --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {{-- Workflow Steps --}}
      <div class="bg-gradient-to-br from-emerald-50 to-green-50 border border-emerald-200 rounded-2xl p-6">
        <div class="flex items-start gap-4 mb-4">
          <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-tasks text-emerald-600"></i>
          </div>
          <h4 class="font-semibold text-emerald-900">Alur Validasi Disdukcapil</h4>
        </div>
        <ol class="space-y-3 text-sm text-emerald-800">
          <li class="flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex-shrink-0">1</span>
            <span>Kasus dikirim dari <strong>PA Management</strong> setelah review dan persetujuan</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex-shrink-0">2</span>
            <span>Status kasus berubah menjadi <strong>Validasi Disdukcapil</strong></span>
          </li>
          <li class="flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex-shrink-0">3</span>
            <span>Periksa dokumen dan data untuk <strong>validasi akhir</strong></span>
          </li>
          <li class="flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex-shrink-0">4</span>
            <span>Validasi (selesai) atau Tolak (kembali ke PA)</span>
          </li>
        </ol>
      </div>

      {{-- Status Information --}}
      <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-6">
        <div class="flex items-start gap-4 mb-4">
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-info-circle text-blue-600"></i>
          </div>
          <h4 class="font-semibold text-blue-900">Data Transfer dari PA Management</h4>
        </div>
        <div class="space-y-3 text-sm text-blue-800">
          <div class="flex items-start gap-2">
            <i class="fas fa-check-circle text-blue-600 mt-0.5"></i>
            <span>Kasus yang ditampilkan adalah yang <strong>disetujui oleh PA Management</strong></span>
          </div>
          <div class="flex items-start gap-2">
            <i class="fas fa-check-circle text-blue-600 mt-0.5"></i>
            <span>Data sudah melewati <strong>review OCR</strong> dan <strong>validasi manual PA</strong></span>
          </div>
          <div class="flex items-start gap-2">
            <i class="fas fa-check-circle text-blue-600 mt-0.5"></i>
            <span>Dokumen rekam medis sudah <strong>tersedia dan verified</strong></span>
          </div>
          <div class="flex items-start gap-2">
            <i class="fas fa-check-circle text-blue-600 mt-0.5"></i>
            <span>Validasi akhir adalah tanggung jawab <strong>Disdukcapil Staff</strong></span>
          </div>
        </div>
      </div>
    </div>

  @else

  {{-- Welcome Banner --}}
  @unless(auth()->user()->hasRole('pa_assistant') || auth()->user()->hasRole('pa_management') || auth()->user()->hasRole('pa_staff'))
  <div class="bg-gradient-to-r from-primary-dark to-primary rounded-2xl p-6 text-white shadow-lg">
    <h1 class="text-2xl font-bold">Selamat datang, {{ auth()->user()->name }} 👋</h1>
    <p class="text-blue-200 mt-1">
      {{ auth()->user()->institution?->name ?? 'Sistem SiPadu' }}
      &nbsp;·&nbsp;
      @php
        $roleLabels = [
          'super_admin'       => 'Administrator',
          'pa_assistant'      => 'PA Assistant',
          'pa_management'     => 'PA Management',
          'pa_staff'          => 'PA Staff',
          'disdukcapil_staff' => 'Disdukcapil Staff',
        ];
        $currentRole = auth()->user()->getRoleNames()->first();
        $roleLabel   = $roleLabels[$currentRole] ?? $currentRole;
      @endphp
      <span class="bg-white/20 rounded-full px-2 py-0.5 text-xs font-medium">{{ $roleLabel }}</span>
    </p>
  </div>
  @endunless

  {{-- Stat Cards & Chart Card untuk PA Assistant --}}
  <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
    {{-- Left: Stat Cards --}}
    <div class="lg:col-span-2">
      <div class="grid grid-cols-2 gap-4">
        {{-- Buat Pengajuan (PA Assistant) --}}
        @if(auth()->user()->hasRole('pa_assistant'))
        <a href="{{ route('dashboard.cases.create') }}" class="bg-blue-100 rounded-xl shadow-sm border border-blue-400 border-dashed p-4 flex flex-col items-center justify-center text-center hover:bg-blue-200 transition">
          <div class="mb-2">
            <svg class="w-10 h-10 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
          </div>
          <p class="text-xs text-gray-800 font-semibold">Buat Pengajuan</p>
        </a>
        @endif

        {{-- Draft --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center justify-center text-center">
          <div class="mb-2">
            <svg class="w-8 h-8 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
          </div>
          <p class="text-lg font-bold text-gray-800 leading-tight">{{ $stats['draft'] ?? 0 }}</p>
          <p class="text-xs text-gray-500 font-medium">Draft</p>
        </div>

        {{-- Submitted / Diproses --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center justify-center text-center">
          <div class="mb-2">
            <svg class="w-8 h-8 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <p class="text-lg font-bold text-gray-800 leading-tight">{{ $stats['in_progress'] ?? 0 }}</p>
          <p class="text-xs text-gray-500 font-medium">{{ auth()->user()->hasRole('pa_assistant') ? 'Submitted' : 'Diproses' }}</p>
        </div>

        {{-- Selesai --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center justify-center text-center">
          <div class="mb-2">
            <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <p class="text-lg font-bold text-gray-800 leading-tight">{{ $stats['completed'] ?? 0 }}</p>
          <p class="text-xs text-gray-500 font-medium">Selesai</p>
        </div>

        {{-- Ditolak --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center justify-center text-center">
          <div class="mb-2">
            <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <p class="text-lg font-bold text-gray-800 leading-tight">{{ $stats['rejected'] ?? 0 }}</p>
          <p class="text-xs text-gray-500 font-medium">Ditolak</p>
        </div>

        {{-- Total Kasus --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center justify-center text-center">
          <div class="mb-2">
            <svg class="w-8 h-8 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7a2 2 0 012-2h14a2 2 0 012 2m0 0V5a2 2 0 00-2-2H5a2 2 0 00-2 2v2"></path>
            </svg>
          </div>
          <p class="text-lg font-bold text-gray-800 leading-tight">{{ $stats['total'] ?? 0 }}</p>
          <p class="text-xs text-gray-500 font-medium">Total Kasus</p>
        </div>
      </div>
    </div>

    {{-- Right: PA Staff Cards (sejajar dengan stat cards) --}}
    @role('pa_staff')
    <div class="lg:col-span-3">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 h-full">
        <a href="{{ route('dashboard.cases') }}?status=COMPLETED"
           class="bg-gradient-to-br from-[#D4633C]/80 to-[#D4633C] rounded-2xl p-5 flex flex-col items-center justify-center text-center shadow-lg hover:shadow-xl transition-all text-white group">
          <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
            <i class="fas fa-archive text-white text-2xl"></i>
          </div>
          <p class="font-bold text-base">Arsip & Serah-Terima</p>
          <p class="text-white/80 text-xs mt-1.5">Kasus selesai siap diarsipkan</p>
        </a>
        <a href="{{ route('dashboard.cases') }}"
           class="bg-gradient-to-br from-[#0D1F08]/90 to-[#0D1F08] rounded-2xl p-5 flex flex-col items-center justify-center text-center shadow-lg hover:shadow-xl transition-all text-white group">
          <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
            <i class="fas fa-folder text-white text-2xl"></i>
          </div>
          <p class="font-bold text-base">Semua Kasus</p>
          <p class="text-white/80 text-xs mt-1.5">Pantau dokumen yang beredar</p>
        </a>
      </div>
    </div>
    @endrole

    {{-- Right: Chart Card untuk PA Assistant --}}
    @role('pa_assistant')
    <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
      <div class="mb-3">
        <h3 class="text-sm font-semibold text-gray-800">
          <i class="fas fa-chart-bar text-primary mr-2"></i>Statistik Pengajuan
        </h3>
      </div>

      @php
        // Initialize chart data
        $chartData = $chartData ?? [];
        $labels = $chartData['labels'] ?? [];
        $datasets = $chartData['datasets'] ?? [];
        
        // Debug: Check if chart data is populated
        if (empty($labels)) {
            \Log::warning('Chart data is empty! Using fallback.', [
                'chartData' => $chartData,
                'labels' => $labels,
                'datasets' => $datasets,
            ]);
        } else {
            \Log::info('Chart data received successfully', [
                'labels' => $labels,
                'datasetCount' => count($datasets),
            ]);
        }
      @endphp

      <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="flex flex-col items-center p-2 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
          <div class="text-base font-bold text-blue-600 mb-0.5">{{ $stats['total'] ?? 0 }}</div>
          <div class="text-[10px] text-blue-700 font-medium">Total Inputan</div>
        </div>
        
        <div class="flex flex-col items-center p-2 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg">
          <div class="text-base font-bold text-purple-600 mb-0.5">{{ $stats['public_submissions'] ?? 0 }}</div>
          <div class="text-[10px] text-purple-700 font-medium">Inputan Publik</div>
        </div>
        
        <div class="flex flex-col items-center p-2 bg-gradient-to-br from-teal-50 to-teal-100 rounded-lg">
          <div class="text-base font-bold text-teal-600 mb-0.5">{{ $stats['internal_cases'] ?? 0 }}</div>
          <div class="text-[10px] text-teal-700 font-medium">Inputan PA</div>
        </div>
      </div>

      {{-- SVG Area Chart (Smaller) --}}
      <div class="relative h-44">
        <svg viewBox="0 0 800 170" class="w-full h-full" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
          @php
            // Set default fallback
            if (empty($labels)) {
                $labels = ['Bulan 1', 'Bulan 2', 'Bulan 3', 'Bulan 4', 'Bulan 5', 'Bulan 6', 'Bulan 7'];
            }
            if (empty($datasets)) {
                // Default fallback datasets
                $datasets = [
                    ['label' => 'Total', 'data' => [50, 60, 55, 70, 80, 90, 100], 'color' => '#3b82f6'],
                    ['label' => 'Publik', 'data' => [20, 25, 28, 30, 35, 38, 40], 'color' => '#a855f7'],
                    ['label' => 'Internal', 'data' => [30, 35, 27, 40, 45, 52, 60], 'color' => '#14b8a6'],
                ];
            }
            
            // Calculate scale
            $maxValue = 0;
            foreach ($datasets as $dataset) {
                foreach ($dataset['data'] as $value) {
                    $maxValue = max($maxValue, $value);
                }
            }
            
            // Smart scaling: if max value is too small, scale up to make chart readable
            // But respect the actual max value if data has good distribution
            if ($maxValue == 0) {
                $maxValue = 100; // All zeros, use default scale
            } elseif ($maxValue < 10) {
                $maxValue = $maxValue * 10; // Scale up small values for visibility
            }
            // If maxValue >= 10, use actual value (good distribution)
            
            // Chart dimensions
            $chartWidth = 700;
            $chartHeight = 120;
            $xStart = 50;
            $yStart = 20;
            $xEnd = $xStart + $chartWidth;
            $yEnd = $yStart + $chartHeight;
            
            // Helper function to convert data to Y coordinate
            $dataToY = function($value) use ($maxValue, $yStart, $yEnd, $chartHeight) {
                if ($maxValue == 0) return $yEnd;
                return $yEnd - ($value / $maxValue) * $chartHeight;
            };
            
            // Helper function to convert index to X coordinate
            $indexToX = function($index, $total) use ($xStart, $chartWidth) {
                if ($total <= 1) return $xStart;
                return $xStart + ($index / ($total - 1)) * $chartWidth;
            };
            
            $dataCount = count($labels);
          @endphp

          <!-- Grid Lines -->
          <line x1="50" y1="140" x2="750" y2="140" stroke="#e5e7eb" stroke-width="2" />
          @php
            $gridSteps = 4;
            for ($i = 1; $i < $gridSteps; $i++) {
                $y = 140 - ($i / $gridSteps) * 120;
                $gridValue = (int)(($i / $gridSteps) * $maxValue);
                echo "<line x1=\"50\" y1=\"$y\" x2=\"750\" y2=\"$y\" stroke=\"#e5e7eb\" stroke-width=\"1\" stroke-dasharray=\"5,5\" />";
            }
          @endphp
          
          <!-- Y-axis labels -->
          @php
            for ($i = 0; $i <= $gridSteps; $i++) {
                $y = 140 - ($i / $gridSteps) * 120;
                $gridValue = (int)(($i / $gridSteps) * $maxValue);
                echo "<text x=\"35\" y=\"" . ($y + 4) . "\" text-anchor=\"end\" font-size=\"11\" fill=\"#9ca3af\">$gridValue</text>";
            }
          @endphp
          
          <!-- Axes -->
          <line x1="50" y1="20" x2="50" y2="140" stroke="#d1d5db" stroke-width="2" />
          <line x1="50" y1="140" x2="750" y2="140" stroke="#d1d5db" stroke-width="2" />
          
          <!-- X-axis labels -->
          @php
            for ($i = 0; $i < count($labels); $i++) {
                $x = $indexToX($i, count($labels));
                $label = $labels[$i];
                echo "<text x=\"$x\" y=\"155\" text-anchor=\"middle\" font-size=\"10\" fill=\"#9ca3af\">$label</text>";
            }
          @endphp

          <!-- Defs for gradients -->
          <defs>
            <linearGradient id="grad1" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.3" />
              <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:0" />
            </linearGradient>
            <linearGradient id="grad2" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" style="stop-color:#a855f7;stop-opacity:0.3" />
              <stop offset="100%" style="stop-color:#a855f7;stop-opacity:0" />
            </linearGradient>
            <linearGradient id="grad3" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" style="stop-color:#14b8a6;stop-opacity:0.3" />
              <stop offset="100%" style="stop-color:#14b8a6;stop-opacity:0" />
            </linearGradient>
          </defs>

          @php
            // Render each dataset
            $colors = ['#3b82f6', '#a855f7', '#14b8a6'];
            $gradIds = ['grad1', 'grad2', 'grad3'];
            
            // Debug visualization
            echo "<!-- DEBUG: Rendering ". count($datasets) . " datasets with max value: $maxValue -->";
            foreach ($datasets as $idx => $ds) {
                $dataStr = implode(',', $ds['data']);
                echo "<!-- Dataset $idx ({$ds['label']}): [$dataStr] -->";
            }
            
            foreach ($datasets as $datasetIndex => $dataset) {
                $color = $colors[$datasetIndex] ?? '#666';
                $gradId = $gradIds[$datasetIndex] ?? 'grad' . ($datasetIndex + 1);
                $data = $dataset['data'];
                
                // Build polygon and polyline paths
                $polygonPoints = '';
                $polylinePoints = '';
                
                for ($i = 0; $i < count($data); $i++) {
                    $x = $indexToX($i, count($data));
                    $y = $dataToY($data[$i]);
                    
                    if ($i == 0) {
                        $polygonPoints .= "$x,$y ";
                        $polylinePoints .= "$x,$y ";
                    } else {
                        $polygonPoints .= "$x,$y ";
                        $polylinePoints .= "$x,$y ";
                    }
                }
                
                // Close polygon by adding Y-axis points
                $lastX = $indexToX(count($data) - 1, count($data));
                $polygonPoints .= "$lastX,140 50,140";
                
                echo "<polygon points=\"$polygonPoints\" fill=\"url(#$gradId)\" />";
                echo "<polyline points=\"$polylinePoints\" stroke=\"$color\" stroke-width=\"3\" fill=\"none\" stroke-linejoin=\"round\" stroke-linecap=\"round\" />";
            }
          @endphp

          <!-- Data Points -->
          @php
            foreach ($datasets as $datasetIndex => $dataset) {
                $color = $colors[$datasetIndex] ?? '#666';
                $data = $dataset['data'];
                
                echo "<g fill=\"$color\">";
                for ($i = 0; $i < count($data); $i++) {
                    $x = $indexToX($i, count($data));
                    $y = $dataToY($data[$i]);
                    echo "<circle cx=\"$x\" cy=\"$y\" r=\"3\" />";
                }
                echo "</g>";
            }
          @endphp
        </svg>
      </div>

      <!-- Legend (Hidden) -->
      <div class="hidden">
        @foreach($datasets as $datasetIndex => $dataset)
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full" style="background-color: {{ $dataset['color'] }}"></div>
          </div>
        @endforeach
      </div>
    </div>
    @endrole

    {{-- Right: OCR Match/Mismatch Chart untuk PA Management --}}
    @role('pa_management')
    <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-6">
        <i class="fas fa-chart-pie text-primary mr-2"></i>Statistik Validasi OCR
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-full">
        {{-- Chart Donut --}}
        <div class="md:col-span-1 flex items-center justify-center">
          <svg viewBox="0 0 200 200" class="w-40 h-40">
            @php
              $match = $stats['ocr_match'] ?? 0;
              $partial = $stats['ocr_partial'] ?? 0;
              $mismatch = $stats['ocr_mismatch'] ?? 0;
              $totalData = $match + $partial + $mismatch;

              $circumference = 2 * pi() * 90; // r=90

              // Percentages (as fraction of 1)
              $matchPct = $totalData > 0 ? $match / $totalData : 0;
              $partialPct = $totalData > 0 ? $partial / $totalData : 0;
              $mismatchPct = $totalData > 0 ? $mismatch / $totalData : 0;

              // SVG arc math:
              // dasharray = "segment_length gap"
              // dashoffset = 0 (starts at path beginning = top)
              // Segments stack sequentially: match → partial → mismatch
              // gap of each segment = circumference - segment_length

              // Match: starts at 0, length = matchPct * circumference
              $matchLen = $matchPct * $circumference;
              $matchGap = $circumference - $matchLen;

              // Partial: starts after match, length = partialPct * circumference
              $partialLen = $partialPct * $circumference;
              $partialGap = $circumference - $partialLen;

              // Mismatch: starts after match+partial, length = mismatchPct * circumference
              $mismatchLen = $mismatchPct * $circumference;
              $mismatchGap = $circumference - $mismatchLen;
            @endphp

            @if($totalData == 0)
              <!-- Empty state: gray circle with centered message -->
              <circle cx="100" cy="100" r="90" fill="none" stroke="#e5e7eb" stroke-width="20" />
              <text x="100" y="97" text-anchor="middle" font-size="20" font-weight="bold" fill="#9ca3af">0</text>
              <text x="100" y="115" text-anchor="middle" font-size="11" fill="#9ca3af">Belum ada data</text>
            @else
              <!-- Donut segments: each segment fills its portion, gap hides the rest -->
              <!-- Match (green) -->
              @if($match > 0)
              <circle cx="100" cy="100" r="90" fill="none" stroke="#10b981" stroke-width="20"
                      stroke-dasharray="{{ $matchLen }} {{ $matchGap }}"
                      stroke-linecap="butt"
                      transform="rotate(-90 100 100)"/>
              @endif

              <!-- Partial (yellow) -->
              @if($partial > 0)
              <circle cx="100" cy="100" r="90" fill="none" stroke="#f59e0b" stroke-width="20"
                      stroke-dasharray="{{ $partialLen }} {{ $partialGap }}"
                      stroke-linecap="butt"
                      transform="rotate({{ -90 + ($matchPct * 360) }} 100 100)"/>
              @endif

              <!-- Mismatch (red) -->
              @if($mismatch > 0)
              <circle cx="100" cy="100" r="90" fill="none" stroke="#ef4444" stroke-width="20"
                      stroke-dasharray="{{ $mismatchLen }} {{ $mismatchGap }}"
                      stroke-linecap="butt"
                      transform="rotate({{ -90 + (($matchPct + $partialPct) * 360) }} 100 100)"/>
              @endif

              <!-- Center text -->
              <text x="100" y="97" text-anchor="middle" font-size="28" font-weight="bold" fill="#1f2937">{{ $totalData }}</text>
              <text x="100" y="115" text-anchor="middle" font-size="11" fill="#6b7280">Total</text>
            @endif
          </svg>
        </div>

        {{-- Stats --}}
        <div class="md:col-span-2 space-y-4">
          <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-green-700 font-medium">Data Match</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ $match }}</p>
              </div>
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
              </div>
            </div>
            <div class="mt-2 text-xs text-green-600">{{ $totalData > 0 ? round($matchPct * 100, 1) : 0 }}% dari total</div>
          </div>

          <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-lg p-4 border border-yellow-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-yellow-700 font-medium">Data Partial Match</p>
                <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $partial }}</p>
              </div>
              <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
              </div>
            </div>
            <div class="mt-2 text-xs text-yellow-600">{{ $totalData > 0 ? round($partialPct * 100, 1) : 0 }}% dari total</div>
          </div>

          <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-lg p-4 border border-red-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-red-700 font-medium">Data Mismatch</p>
                <p class="text-3xl font-bold text-red-600 mt-1">{{ $mismatch }}</p>
              </div>
              <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
              </div>
            </div>
            <div class="mt-2 text-xs text-red-600">{{ $totalData > 0 ? round($mismatchPct * 100, 1) : 0 }}% dari total</div>
          </div>
        </div>
      </div>
    </div>
    @endrole
  </div>
  {{-- ── Super Admin Cards ────────────────────────────────────────── --}}
  @role('super_admin')
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10">
    <a href="{{ route('dashboard.admin.users') }}"
         class="bg-white border-2 border-brand rounded-2xl p-6 flex items-center gap-4 hover:bg-brand/10 transition group shadow-md">
        <div class="w-12 h-12 bg-brand rounded-xl flex items-center justify-center group-hover:scale-110 transition">
          <i class="fas fa-users text-white text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-darktext">Manajemen User</p>
          <p class="text-xs text-gray-600">Kelola akun & hak akses</p>
        </div>
      </a>
      <a href="{{ route('dashboard.admin.audit') }}"
         class="bg-white border-2 border-coral rounded-2xl p-6 flex items-center gap-4 hover:bg-coral/10 transition group shadow-md">
        <div class="w-12 h-12 bg-coral rounded-xl flex items-center justify-center">
          <i class="fas fa-shield-halved text-white text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-darktext">Audit Log</p>
          <p class="text-xs text-gray-600">Pantau aktivitas sistem</p>
        </div>
      </a>
      <a href="{{ route('dashboard.admin.logs') }}"
         class="bg-white border-2 border-green-sm rounded-2xl p-6 flex items-center gap-4 hover:bg-green-sm/20 transition group shadow-md">
        <div class="w-12 h-12 bg-green-sm rounded-xl flex items-center justify-center">
          <i class="fas fa-list-check text-white text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-gray-800">Access Log</p>
          <p class="text-xs text-gray-500">Rekaman seluruh request HTTP</p>
        </div>
      </a>
    @endrole
  </div>
  {{-- end super admin grid --}}

  {{-- Aktivitas Terbaru (shared) --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-sm text-gray-800"><i class="fas fa-history mr-2 text-gray-400"></i>Aktivitas Terbaru</h2>
      <a href="{{ route('dashboard.cases') }}" class="text-xs font-medium text-gray-500 hover:text-gray-800">Lihat semua →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-gray-500 text-left text-xs">
            <th class="px-4 py-3 font-medium">Jenis</th>
            <th class="px-4 py-3 font-medium">No/Token</th>
            <th class="px-4 py-3 font-medium">Pemohon</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium">Tanggal</th>
            <th class="px-4 py-3 font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($recentItems as $item)
          <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3">
              @if($item->source_type === 'internal')
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-gray-800 text-white">
                  <i class="fas fa-briefcase mr-1"></i> Kasus
                </span>
              @else
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-orange-500 text-white">
                  <i class="fas fa-users mr-1"></i> Publik
                </span>
              @endif
            </td>
            <td class="px-4 py-3 font-mono text-xs font-semibold text-cyan-700">
              {{ Str::limit($item->case_number, 20) }}
              @if($item->source_type === 'public')
                <div class="text-gray-500 mt-1">{{ Str::limit($item->tracking_token, 20) }}</div>
              @endif
            </td>
            <td class="px-4 py-3 font-medium text-gray-800">
              {{ $item->petitioner_name ?? '-' }}
            </td>
            <td class="px-4 py-3">
              @include('components.status-badge', ['status' => $item->status])
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->updated_at->diffForHumans() }}</td>
            <td class="px-4 py-3">
              <a href="{{ auth()->user()->hasAnyRole(['pa_management', 'super_admin']) ? route('dashboard.review.show', $item->id) : route('dashboard.cases.show', $item->id) }}"
                 class="font-semibold hover:underline text-xs text-orange-500">
                <i class="fas fa-eye mr-1"></i>Review
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center py-8 text-gray-400">
              <i class="fas fa-inbox text-3xl mb-2 block"></i>
              Belum ada aktivitas.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @endrole

</div>
@endsection
