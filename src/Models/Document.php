<?php

declare(strict_types=1);

namespace Marvin\Ask\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Marvin\Ask\Enums\DocumentStatus;
use Marvin\Ask\Enums\DocumentType;
use Marvin\Ask\Observers\DocumentObserver;

#[ObservedBy(DocumentObserver::class)]
class Document extends Model
{
    use SoftDeletes;

    public static string $disk = 'hetzner';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'processed' => 'boolean',
            'metadata' => 'json',
            'status' => DocumentStatus::class,
            'type' => DocumentType::class,
        ];
    }
}
