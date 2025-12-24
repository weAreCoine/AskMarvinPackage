<?php

namespace Marvin\Ask\Observers;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Marvin\Ask\Models\Document;
use Storage;

class DocumentObserver
{
    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "updated" event.
     */
    public function updated(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "restored" event.
     */
    public function restored(Document $document): void
    {
        if (! Storage::disk($document->disk)->exists($document->path)) {
            Notification::make()
                ->title(__('Impossibile ripristinare il documento'))
                ->body(__('Il file associato non esiste più sul disco.'))
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'errorMessage' => __('File does not exist anymore on disk'),
                'path' => $document->path,
            ]);
        }
    }

    /**
     * Handle the Document "force deleted" event.
     */
    public function forceDeleted(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
    }
}
