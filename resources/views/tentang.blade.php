@extends('layouts.public')

@section('title', 'Tentang SiPadu - Sistem Pembaruan Dokumen Pasca Perceraian')

@section('content')

{{-- Hero Section --}}
<section class="gradient-hero text-white py-16 px-4 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-4xl font-bold mb-4">Tentang SiPadu</h1>
    <p class="text-blue-100 text-lg">Memahami misi dan visi kami dalam melayani Anda</p>
  </div>
</section>

{{-- Main Content --}}
<section class="py-20 px-4 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto">
    {{-- Tentang Section --}}
    <div class="mb-16">
      <h2 class="text-3xl font-bold text-gray-900 mb-6">Apa Itu SiPadu?</h2>
      <p class="text-gray-600 text-lg leading-relaxed mb-4">
        <strong>Sistem Pembaruan Dokumen Pasca Perceraian (SiPadu)</strong> adalah platform terintegrasi yang menghubungkan Pengadilan Agama dan Dinas Kependudukan dan Pencatatan Sipil (Disdukcapil) untuk memproses pembaruan dokumen kependudukan secara digital.
      </p>
      <p class="text-gray-600 text-lg leading-relaxed">
        Kami bekerja untuk memastikan bahwa setiap warga mendapatkan akses mudah dan aman dalam memperbarui dokumen pribadi mereka pasca perceraian, tanpa harus mengurus dokumen fisik atau bolak-balik ke kantor.
      </p>
    </div>

    {{-- Visi Misi --}}
    <div class="grid md:grid-cols-2 gap-12 mb-16">
      <div class="bg-blue-50 p-8 rounded-lg">
        <h3 class="text-2xl font-bold text-gray-900 mb-4">
          <i class="fas fa-eye text-brand mr-3"></i>Visi
        </h3>
        <p class="text-gray-600 leading-relaxed">
          Menjadi sistem terpadu yang memberikan kepastian hukum, kecepatan pemrosesan, dan transparansi penuh dalam pembaruan dokumen kependudukan pasca perceraian.
        </p>
      </div>
      <div class="bg-blue-50 p-8 rounded-lg">
        <h3 class="text-2xl font-bold text-gray-900 mb-4">
          <i class="fas fa-target text-brand mr-3"></i>Misi
        </h3>
        <p class="text-gray-600 leading-relaxed">
          Menghubungkan institusi pemerintah, mempercepat proses administratif, dan memberikan pengalaman terbaik kepada masyarakat dalam mengurus pembaruan dokumen.
        </p>
      </div>
    </div>

    {{-- Fitur Utama --}}
    <div class="mb-16">
      <h2 class="text-3xl font-bold text-gray-900 mb-8">Fitur & Keunggulan</h2>
      <div class="grid md:grid-cols-2 gap-8">
        <div class="flex gap-4">
          <i class="fas fa-check-circle text-brand text-2xl flex-shrink-0 pt-1"></i>
          <div>
            <h4 class="font-bold text-gray-900 mb-2">Pengajuan Tanpa Akun</h4>
            <p class="text-gray-600">Masyarakat dapat mengajukan tanpa perlu membuat akun, cukup dengan NIK dan nomor WhatsApp.</p>
          </div>
        </div>
        <div class="flex gap-4">
          <i class="fas fa-check-circle text-brand text-2xl flex-shrink-0 pt-1"></i>
          <div>
            <h4 class="font-bold text-gray-900 mb-2">OCR Otomatis</h4>
            <p class="text-gray-600">Ekstraksi data dari dokumen secara otomatis dengan teknologi OCR terkini.</p>
          </div>
        </div>
        <div class="flex gap-4">
          <i class="fas fa-check-circle text-brand text-2xl flex-shrink-0 pt-1"></i>
          <div>
            <h4 class="font-bold text-gray-900 mb-2">Verifikasi berlapis</h4>
            <p class="text-gray-600">Setiap dokumen diverifikasi oleh PA dan Disdukcapil untuk kepastian hukum.</p>
          </div>
        </div>
        <div class="flex gap-4">
          <i class="fas fa-check-circle text-brand text-2xl flex-shrink-0 pt-1"></i>
          <div>
            <h4 class="font-bold text-gray-900 mb-2">Pelacakan Real-Time</h4>
            <p class="text-gray-600">Lacak status pengajuan kapan saja melalui token yang dikirim via WhatsApp.</p>
          </div>
        </div>
      </div>
    </div>

    {{-- Institusi --}}
    <div class="mb-16 bg-gray-50 p-8 rounded-lg">
      <h2 class="text-3xl font-bold text-gray-900 mb-8">Institusi Pendukung</h2>
      <div class="grid md:grid-cols-2 gap-8">
        <div>
          <h4 class="font-bold text-lg text-gray-900 mb-2 flex items-center gap-2">
            <i class="fas fa-balance-scale text-brand"></i> Pengadilan Agama
          </h4>
          <p class="text-gray-600">Menerbitkan putusan dan surat keterangan perceraian, mengelola kasus perceraian, dan verifikasi awal dokumen.</p>
        </div>
        <div>
          <h4 class="font-bold text-lg text-gray-900 mb-2 flex items-center gap-2">
            <i class="fas fa-id-card text-brand"></i> Disdukcapil
          </h4>
          <p class="text-gray-600">Melakukan validasi data kependudukan, pembaruan data di sistem PIK, dan penerbitan dokumen resmi.</p>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection