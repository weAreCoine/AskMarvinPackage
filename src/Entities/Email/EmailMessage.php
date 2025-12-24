<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\Email;

use Google\Service\Gmail\Message;
use Illuminate\Support\Carbon;

final  class EmailMessage
{
    // TODO Add CC,CCN to the message.
    public function __construct(
        public string  $id,
        public string  $threadId,
        public string  $from,
        public string  $to,
        public string  $subject,
        public Carbon  $date,
        public string  $snippet,
        public ?string $html,
        public ?string $text,
        public Message $originalMessage, // FIXME Gmail coupled implementation. Use DTO or Union Types to decouple.
    )
    {
    }

}
