@extends('layouts.admin')

@section('title', 'Kelola Konten')
@section('page-title', 'Kelola Konten')

@section('content')
<div class="space-y-8">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

        {{-- Card: Home --}}
        <a href="{{ route('dashboard.admin.cms.kelola-konten.home.create') }}"
           class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-200 hover:shadow-md hover:border-stone-300 transition-all">
            <div class="flex flex-col gap-4">
            <div class="w-12 h-12 rounded-xl bg-stone-100 text-stone-700 flex items-center justify-center group-hover:bg-stone-200 transition">
                <i class="fas fa-home text-xl"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-semibold text-stone-800">Home</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Kelola konten halaman utama (hero, statistik, CTA, dll).</p>
            </div>
            </div>
        </a>

        {{-- Card: Layanan --}}
        <a href="{{ route('dashboard.admin.cms.layan.index') }}"
           class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-200 hover:shadow-md hover:border-stone-300 transition-all">
            <div class="flex flex-col gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center group-hover:bg-blue-100 transition">
                <i class="fas fa-concierge-bell text-xl"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-semibold text-stone-800">Layanan</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Kelola daftar layanan dan fitur yang ditampilkan.</p>
            </div>
            </div>
        </a>

        {{-- Card: Tentang --}}
        <a href="{{ route('dashboard.admin.cms.kelola-konten.about.create') }}"
           class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-200 hover:shadow-md hover:border-stone-300 transition-all">
            <div class="flex flex-col gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-100 transition">
                <i class="fas fa-info-circle text-xl"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-semibold text-stone-800">Tentang</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Kelola halaman tentang (visi, misi, institusi).</p>
            </div>
            </div>
        </a>

        {{-- Card: Berita --}}
        <a href="{{ route('dashboard.admin.cms.kelola-konten.blog.create') }}"
           class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-200 hover:shadow-md hover:border-stone-300 transition-all">
            <div class="flex flex-col gap-4">
            <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-700 flex items-center justify-center group-hover:bg-orange-100 transition">
                <i class="fas fa-newspaper text-xl"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-semibold text-stone-800">Berita</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Kelola artikel dan informasi terbaru.</p>
            </div>
            </div>
        </a>

    </div>

</div>
@endsection


