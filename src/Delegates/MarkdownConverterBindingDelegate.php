<?php

declare(strict_types=1);

namespace Marvin\Ask\Delegates;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\MarkdownConverter;

class MarkdownConverterBindingDelegate
{
    public static function getConcrete(): MarkdownConverter
    {
        $environment = new Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addEventListener(DocumentParsedEvent::class,
            function (DocumentParsedEvent $event) {
                $walker = $event->getDocument()->walker();
                $base = config('marvin.town_site_base_url');

                while ($eventNode = $walker->next()) {
                    $node = $eventNode->getNode();
                    if ($node instanceof Link
                        && str_starts_with($node->getUrl(), '/')
                    ) {
                        $node->setUrl($base.$node->getUrl());
                    }
                }
            });

        return new MarkdownConverter($environment);
    }
}
