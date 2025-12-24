<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities;

use Illuminate\Support\Collection;
use Marvin\Ask\Traits\HasFromArrayMethod;

/**
 * This class is typically instantiated based on the structured response of the LLM model and is used to represent the
 * intent expressed by the user in the text of a message sent to Marvin. The extracted information
 * is used to query the vector database for useful chunks to pass to the LLM model to generate a response
 * to the originally sent message.
 */
class UserMessageIntent
{
    use HasFromArrayMethod;

    public function __construct(
        protected string $topic,
        protected array $intents,
        protected array $semanticQueries,
        protected float $confidence
    ) {}

    public function getQueriesCollection(): Collection
    {
        return collect($this->getQueries());
    }

    public function getQueries(): array
    {
        return $this->semanticQueries;
    }
}
