@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center py-16">
  <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-800 to-cyan-600 flex items-center justify-center mb-6 shadow-lg">
    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
  </div>
  <h1 class="text-4xl font-extrabold text-gray-900 mb-3">
    Selamat Datang di <span class="text-blue-700">SiPadu</span>
  </h1>
  <p class="text-gray-500 text-lg max-w-xl mb-8">
    Sistem Pembaruan Dokumen Pasca Perceraian — terintegrasi antara
    <strong>Pengadilan Agama</strong> dan <strong>Disdukcapil</strong>.
  </p>
  <div class="flex flex-wrap gap-4 justify-center">
    <a href="{{ route('auth.login') }}"
      class="px-6 py-3 bg-blue-700 text-white font-semibold rounded-xl shadow hover:bg-blue-800 transition">
      Masuk ke Sistem
    </a>
    <a href="{{ route('public.submit.create') }}"
      class="px-6 py-3 bg-emerald-600 text-white font-semibold rounded-xl shadow hover:bg-emerald-700 transition">
      <i class="fas fa-file-alt mr-1"></i> Ajukan Pembaruan Dokumen
    </a>
    <a href="{{ route('tracking.public') }}"
      class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
      Lacak Pengajuan
    </a>
  </div>
</div>
@endsection
