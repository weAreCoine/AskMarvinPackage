<?php

declare(strict_types=1);

namespace Marvin\Ask\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Marvin\Ask\Database\Factories\MessageFactory;

class MessageReport extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }
}
