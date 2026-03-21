<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppService
 *
 * Abstraksi pengiriman pesan WhatsApp melalui gateway pihak ketiga.
 * Driver dikonfigurasi via env WA_DRIVER dan config/whatsapp.php.
 *
 * Driver tersedia: fonnte | wablas | log
 */
class WhatsAppService
{
    /**
     * Kirim pesan teks ke nomor WA.
     *
     * @param  string $phone   Nomor tujuan format 62xxx
     * @param  string $message Isi pesan
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    public function send(string $phone, string $message): array
    {
        $driver = config('whatsapp.driver', 'log');

        return match ($driver) {
            'fonnte' => $this->sendFonnte($phone, $message),
            'wablas' => $this->sendWablas($phone, $message),
            default  => $this->sendLog($phone, $message),
        };
    }

    // ── Private: Fonnte ───────────────────────────────────────────────────────

    private function sendFonnte(string $phone, string $message): array
    {
        try {
            $response = Http::timeout(config('whatsapp.timeout', 15))
                ->withHeaders(['Authorization' => config('whatsapp.fonnte.token')])
                ->post(config('whatsapp.fonnte.api_url'), [
                    'target'  => $phone,
                    'message' => $message,
                ]);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? false)) {
                return [
                    'success'    => true,
                    'message_id' => (string) ($body['id'] ?? ''),
                    'error'      => null,
                ];
            }

            $errMsg = $body['reason'] ?? $body['message'] ?? 'Unknown Fonnte error';
            Log::warning('[WA-Fonnte] Gagal kirim ke ' . $phone . ': ' . $errMsg);
            return ['success' => false, 'message_id' => null, 'error' => $errMsg];

        } catch (\Throwable $e) {
            Log::error('[WA-Fonnte] Exception: ' . $e->getMessage());
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }

    // ── Private: Wablas ───────────────────────────────────────────────────────

    private function sendWablas(string $phone, string $message): array
    {
        try {
            $domain = config('whatsapp.wablas.domain');
            $url    = str_replace('{domain}', $domain, config('whatsapp.wablas.api_url'));

            $response = Http::timeout(config('whatsapp.timeout', 15))
                ->withHeaders([
                    'Authorization' => config('whatsapp.wablas.token'),
                    'Content-Type'  => 'application/json',
                ])
                ->post($url, [
                    'phone'   => $phone,
                    'message' => $message,
                ]);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? false)) {
                return [
                    'success'    => true,
                    'message_id' => (string) ($body['data']['id'] ?? ''),
                    'error'      => null,
                ];
            }

            $errMsg = $body['reason'] ?? $body['messages'] ?? 'Unknown Wablas error';
            Log::warning('[WA-Wablas] Gagal kirim ke ' . $phone . ': ' . $errMsg);
            return ['success' => false, 'message_id' => null, 'error' => (string) $errMsg];

        } catch (\Throwable $e) {
            Log::error('[WA-Wablas] Exception: ' . $e->getMessage());
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }

    // ── Private: Log (dev / fallback) ─────────────────────────────────────────

    private function sendLog(string $phone, string $message): array
    {
        Log::channel('stack')->info('[WA-LOG] Pesan ke ' . $phone, [
            'message' => $message,
        ]);

        // Simulasi berhasil untuk keperluan dev/testing
        return [
            'success'    => true,
            'message_id' => 'LOG-' . time(),
            'error'      => null,
        ];
    }

    // ── Template pesan ────────────────────────────────────────────────────────

    /**
     * Template notifikasi pengajuan berhasil diterima.
     */
    public function templatePengajuanDiterima(
        string $name,
        string $token,
        string $trackingUrl
    ): string {
        $appName = config('app.name', 'SiPadu');
        return <<<MSG
        Halo, *{$name}*!

        Pengajuan pembaruan dokumen Anda telah *berhasil diterima* oleh *{$appName}*.

        📋 *Token Tracking:* `{$token}`
        🔗 Lacak status pengajuan Anda di:
        {$trackingUrl}

        Simpan token ini baik-baik. Anda tidak memerlukan password — cukup gunakan token ini untuk memantau status pengajuan.

        _Jika ada pertanyaan, hubungi petugas Disdukcapil setempat._

        Terima kasih 🙏
        MSG;
    }

    /**
     * Template notifikasi perubahan status kasus.
     */
    public function templateStatusBerubah(
        string $name,
        string $statusLabel,
        string $token,
        string $trackingUrl
    ): string {
        $appName = config('app.name', 'SiPadu');
        return <<<MSG
        Halo, *{$name}*!

        Status pengajuan Anda di *{$appName}* telah diperbarui:

        📌 *Status Terbaru:* {$statusLabel}
        📋 *Token:* `{$token}`
        🔗 Lihat detail: {$trackingUrl}

        _Pesan otomatis dari sistem — harap tidak membalas._
        MSG;
    }
}
