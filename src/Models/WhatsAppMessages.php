<?php

declare(strict_types=1);

namespace Marvin\Ask\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessages extends Model
{
    protected $table = 'whatsapp_messages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }
}
