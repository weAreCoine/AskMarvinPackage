<?php

declare(strict_types=1);

namespace Marvin\Ask\Services;

use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\MarkdownConverter;
use Marvin\Ask\Handlers\ExceptionsHandler;

class MarkdownConversionService
{
    public function __construct(protected MarkdownConverter $markdownConverter)
    {
    }

    public function mdToHtml(string $markdown): string
    {
        try {
            return $this->markdownConverter->convert($markdown)->getContent();
        } catch (CommonMarkException $e) {
            ExceptionsHandler::handle($e);
            return '';
        }
    }
}
