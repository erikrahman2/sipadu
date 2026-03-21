<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUploaded
{
    use Dispatchable, SerializesModels;

    public Document $document;

    /**
     * Create a new event instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }
}
