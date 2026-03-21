<?php

namespace App\Http\Controllers\Web;

use Illuminate\Support\Facades\Cache;

class HomeOptimizationController
{
    /**
     * Get homepage data with caching
     */
    public static function getCachedData()
    {
        return Cache::remember('homepage_data', 3600, function () {
            return [
                'features' => self::getFeatures(),
                'process_steps' => self::getProcessSteps(),
                'user_types' => self::getUserTypes(),
                'statistics' => self::getStatistics(),
                'technology' => self::getTechnology(),
            ];
        });
    }

    private static function getFeatures()
    {
        return [
            [
                'icon' => 'fa-robot',
                'title' => 'OCR Otomatis',
                'description' => 'Ekstraksi dokumen secara otomatis dengan AI advanced',
                'color' => 'from-purple-500 to-pink-500'
            ],
            [
                'icon' => 'fa-shield-alt',
                'title' => 'Verifikasi Ganda',
                'description' => 'Sistem verifikasi berlapis untuk keamanan maksimal',
                'color' => 'from-orange-500 to-red-500'
            ],
            [
                'icon' => 'fa-tracking',
                'title' => 'Pelacakan Real-Time',
                'description' => 'Monitor progres dokumen Anda setiap saat',
                'color' => 'from-yellow-500 to-amber-500'
            ],
        ];
    }

    private static function getProcessSteps()
    {
        return [
            [
                'number' => '1',
                'title' => 'Pengajuan',
                'description' => 'Ajukan dokumen melalui sistem kami',
                'icon' => 'fa-file-upload'
            ],
            [
                'number' => '2',
                'title' => 'Ekstraksi OCR',
                'description' => 'Sistem otomatis ekstrak data dokumen',
                'icon' => 'fa-scanner'
            ],
            [
                'number' => '3',
                'title' => 'Verifikasi',
                'description' => 'Tim verifikasi memeriksa hasil',
                'icon' => 'fa-check-circle'
            ],
            [
                'number' => '4',
                'title' => 'Penerbitan',
                'description' => 'Dokumen resmi diterbitkan',
                'icon' => 'fa-certificate'
            ],
        ];
    }

    private static function getUserTypes()
    {
        return [
            [
                'title' => 'Masyarakat Umum',
                'description' => 'Ajukan dokumen pasca perceraian dengan mudah',
                'icon' => 'fa-users',
                'color' => 'text-blue-600',
                'benefits' => ['Pengajuan gratis', 'Proses cepat', 'Transparan']
            ],
            [
                'title' => 'Pengadilan Agama',
                'description' => 'Kelola dokumen dan verifikasi dengan efisien',
                'icon' => 'fa-gavel',
                'color' => 'text-purple-600',
                'benefits' => ['Admin panel', 'Laporan detail', 'Integrasi API']
            ],
            [
                'title' => 'Disdukcapil',
                'description' => 'Koordinasi dan monitoring proses penerbitan',
                'icon' => 'fa-building',
                'color' => 'text-green-600',
                'benefits' => ['Statistik real-time', 'Kontrol kualitas', 'Dashboard']
            ],
        ];
    }

    private static function getStatistics()
    {
        return [
            ['label' => 'Kasus Diproses', 'value' => '1,200+', 'icon' => 'fa-file'],
            ['label' => 'Akurasi', 'value' => '98%', 'icon' => 'fa-percent'],
            ['label' => 'Layanan', 'value' => '24/7', 'icon' => 'fa-clock'],
            ['label' => 'Waktu Proses', 'value' => '2.5 hari', 'icon' => 'fa-hourglass'],
        ];
    }

    private static function getTechnology()
    {
        return [
            [
                'name' => 'Laravel 11',
                'description' => 'Framework backend modern',
                'icon' => 'fa-code',
                'color' => 'text-red-500'
            ],
            [
                'name' => 'MySQL + Neo4j',
                'description' => 'Database relasional & graph',
                'icon' => 'fa-database',
                'color' => 'text-green-500'
            ],
            [
                'name' => 'Python OCR',
                'description' => 'Microservice ekstraksi dokumen',
                'icon' => 'fa-robot',
                'color' => 'text-blue-500'
            ],
        ];
    }
}
