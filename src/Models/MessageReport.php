<?php

declare(strict_types=1);

namespace Marvin\Ask\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReport extends Model
{
    /** @use HasFactory<\Database\Factories\MessageReportFactory> */
    use HasFactory;

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }
}
