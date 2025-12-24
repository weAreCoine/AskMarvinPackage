<?php

declare(strict_types=1);

namespace Marvin\Ask\Enums;

enum MessageType: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
}
