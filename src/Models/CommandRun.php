<?php

declare(strict_types=1);

namespace Marvin\Ask\Models;

use Illuminate\Database\Eloquent\Model;

class CommandRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'options' => 'json',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'success' => 'boolean',
        ];
    }
}
