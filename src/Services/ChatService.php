<?php

declare(strict_types=1);

namespace Marvin\Ask\Services;

use Illuminate\Database\Eloquent\Model;
use Marvin\Ask\Enums\MessageType;
use Marvin\Ask\Models\Chat;

class ChatService
{
    public static function addMessage(string $message, MessageType $type, ?Chat $chat = null): Chat
    {
        $chat ??= self::getOrCreateCurrent();
        $chat->messages()->create([
            'content' => $message,
            'type' => $type,
        ])->save();
        $chat->loadmissing('messages');

        return $chat;
    }

    public static function getOrCreateCurrent(): Chat|Model
    {
        return Chat::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  bool  $forceCreationOfNewChat  Force the creation of a new chat even if the active one has no messages
     */
    public static function disableActiveThenGetNewCurrent(bool $forceCreationOfNewChat = false): Chat
    {
        $current = Chat::where([
            'user_id' => auth()->id(),
            'is_active' => true,
        ])->with('messages')->first();

        if ($current !== null && $current->messages()->count() > 0 || $forceCreationOfNewChat) {
            $current->update(['is_active' => false]);
        }

        return self::getOrCreateCurrent();
    }
}
