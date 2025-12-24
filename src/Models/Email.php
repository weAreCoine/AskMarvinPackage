<?php

declare(strict_types=1);

namespace Marvin\Ask\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'is_sent' => 'boolean',
        ];
    }
}
