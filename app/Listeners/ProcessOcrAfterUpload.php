<?php

namespace App\Listeners;

use App\Events\DocumentUploaded;
use App\Services\OCRService;
use Illuminate\Support\Facades\Log;

class ProcessOcrAfterUpload
{
    private OCRService $ocrService;

    /**
     * Create the event listener.
     */
    public function __construct(OCRService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Handle the event.
     */
    public function handle(DocumentUploaded $event): void
    {
        $document = $event->document;

        // OCR identitas hanya untuk dokumen KTP (suami/istri).
        $processableTypes = ['KTP', 'KTP_SUAMI', 'KTP_ISTRI'];

        if (!in_array($document->document_type, $processableTypes)) {
            Log::channel('ocr')->info('Document type not processable for OCR', [
                'document_id' => $document->id,
                'type'        => $document->document_type,
            ]);
            return;
        }

        // Always use async queue to avoid blocking form submission
        // This ensures submissions complete quickly even if OCR is slow/unavailable
        $this->ocrService->dispatch($document);

        Log::channel('ocr')->info('OCR job dispatched after upload', [
            'document_id' => $document->id,
            'type'        => $document->document_type,
            'queue'       => 'ocr',
        ]);
    }
}
