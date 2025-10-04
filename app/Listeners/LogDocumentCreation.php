<?php

namespace App\Listeners;

use App\Events\DocumentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogDocumentCreation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DocumentCreated $event): void
    {
        Log::info('Document created', [
            'document_id' => $event->document->id,
            'document_number' => $event->document->document_number,
            'company_id' => $event->document->company_id,
            'user_id' => $event->document->user_id,
            'status' => $event->document->status,
            'total' => $event->document->total,
        ]);
    }
}
