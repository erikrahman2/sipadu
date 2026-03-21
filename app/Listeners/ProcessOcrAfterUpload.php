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
        
        // Hanya proses dokumen identitas (KTP, KK, Akta)
        $processableTypes = ['KTP', 'KK', 'AKTA_KELAHIRAN', 'AKTA_CERAI'];
        
        if (!in_array($document->document_type, $processableTypes)) {
            Log::channel('ocr')->info('Document type not processable for OCR', [
                'document_id' => $document->id,
                'type'        => $document->document_type,
            ]);
            return;
        }
        
        // Dispatch OCR job ke queue
        $this->ocrService->dispatch($document);
        
        Log::channel('ocr')->info('OCR job dispatched after upload', [
            'document_id' => $document->id,
            'type'        => $document->document_type,
        ]);
    }
}
