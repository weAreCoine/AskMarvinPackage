<?php

declare(strict_types=1);

namespace Marvin\Ask\Handlers;

use Illuminate\Support\Facades\Log;

final class ExceptionsHandler
{
    public static function handle(\Throwable $throwable, array $contextEnrichment = []): void
    {
        Log::error($throwable->getMessage(), [
            'trace' => $throwable->getTrace(),
            'file' => basename($throwable->getFile()),
            'line' => $throwable->getLine(),
            'code' => $throwable->getCode(),
            'message' => str($throwable->getMessage())->limit(500),
            ...$contextEnrichment,
        ]);

        if (config('app.debug') && config('app.env') === 'local') {
            dd($throwable);
        }
    }
}
