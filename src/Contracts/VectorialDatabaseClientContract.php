<?php

declare(strict_types=1);

namespace Marvin\Ask\Contracts;

use Illuminate\Support\Collection;

interface VectorialDatabaseClientContract
{
    public function search(
        array $vector,
        int $topK = 10,
        bool $filterResults = true,
        float $minScore = 0.7,
    ): Collection;

    public function rerank(
        string $userPrompt,
        Collection $results,
        int $topN = 3,
        array $rankFields = ['content'],
    ): Collection;

    /**
     * Describes the Pinecone index and retrieves its meta-information.
     * The response is cached to reduce repetitive API calls.
     *
     * @param  bool  $safe  Determines whether to return an empty collection instead of false when no data is available.
     * @return Collection|false The index description as a collection, or false if the operation fails.
     */
    public function describeIndex(bool $safe = false): Collection|false;

    public function rankedSearch(
        string $userPrompt,
        array $vector,
        int $topK = 3,
        bool $filterResults = true,
        float $minScore = 0.7,
        int $topN = 3,
        array $rankFields = ['content'],
    ): Collection;
}
