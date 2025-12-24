<?php

declare(strict_types=1);

namespace Marvin\Ask\Support;

use Marvin\Ask\Handlers\ExceptionsHandler;
use Throwable;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\EncoderProvider;

final class TokenCounter
{
    protected readonly EncoderProvider $provider;

    protected Encoder $encoder;

    public function __construct(public string $model)
    {
        $this->provider = new EncoderProvider;

        try {
            $this->encoder = $this->provider->getForModel($this->model);
        } catch (Throwable $e) {
            ExceptionsHandler::handle($e);
            $this->model = 'gpt-4.1';
            $this->encoder = $this->provider->getForModel($this->model);
        }

    }

    public function count(string $text): int
    {
        return count($this->encoder->encode($text));
    }
}
