<?php
declare(strict_types=1);

namespace Marvin\Ask\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Marvin\Ask\Enums\MessageType;

/**
 * @property MessageType $type
 * @property string $content
 */
class Message extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function messageReport(): HasOne
    {
        return $this->hasOne(MessageReport::class);
    }

    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
        ];
    }
}
