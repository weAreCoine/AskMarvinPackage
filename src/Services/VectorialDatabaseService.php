<?php

declare(strict_types=1);

namespace Marvin\Ask\Services;

use Illuminate\Support\Collection;
use Marvin\Ask\Abstracts\VectorialDatabaseClient;
use Marvin\Ask\Clients\PineconeClient;
use Marvin\Ask\Entities\VectorialDB\SearchMatch;

class VectorialDatabaseService
{
    /**
     * @var PineconeClient
     */
    public VectorialDatabaseClient $client {
        get => $this->client;
    }

    public function __construct(VectorialDatabaseClient $databaseClient)
    {
        $this->client = $databaseClient;
    }

    /**
     * Retrieves and describes the current Pinecone index metadata.
     *
     * The result is cached to avoid redundant API requests.
     *
     * @param  bool  $safe
     *                      If true, returns an empty Collection when the index metadata is unavailable.
     *                      If false, returns `false` in that case.
     * @return Collection|false
     *                          A collection containing the index metadata on success, or `false` if retrieval fails
     *                          and `$safe` is false.
     *
     * @example Example of the returned collection structure:
     * [
     *     "name"    => "marvin-embedder",
     *     "metric"  => "cosine",
     *     "dimension" => 768,
     *     "status" => [
     *         "ready" => true,
     *         "state" => "Ready"
     *     ],
     *     "host" => "marvin-embedder-pmk455k.svc.aped-4627-b74a.pinecone.io",
     *     "spec" => [
     *         "serverless" => [
     *             "region" => "us-east-1",
     *             "cloud"  => "aws"
     *         ]
     *     ]
     * ]
     */
    public function describeIndex(bool $safe = false): Collection|false
    {
        return cache()->remember(
            'marvin:describe-index',
            now()->addMinutes(10),
            fn () => $this->client->describeIndex($safe)
        );
    }

    /**
     * Executes a vector search on the Pinecone index and returns the top K results.
     *
     * @param  float[]  $vector  The query vector (e.g., 768-dimensional).
     * @param  int  $topK  The number of top-matching results to retrieve.
     * @param  bool  $filterResults  Whether to filter results based on a minimum score threshold.
     * @param  float  $minScore  Minimum similarity score required for a match to be accepted.
     *                           Ignored if $filterResults is false.
     * @return Collection<SearchMatch> A collection of matching results.
     */
    public function search(
        array $vector,
        int $topK = 10,
        bool $filterResults = true,
        float $minScore = 0.3,
    ): Collection {
        return $this->client->search($vector, $topK, $filterResults, $minScore);
    }

    public function rerank(
        string $userPrompt,
        Collection $results,
        int $topN = 3,
        array $rankFields = ['content'],
    ): Collection {
        return $this->client->rerank($userPrompt, $results, $topN, $rankFields);
    }

    public function rankedSearch(
        string $userPrompt,
        array $vector,
        int $topK = 10,
        bool $filterResults = true,
        float $minScore = 0.3,
        int $topN = 3,
        array $rankFields = ['content'],
    ): Collection {
        return $this->client->rankedSearch($userPrompt, $vector, $topK, $filterResults, $minScore, $topN, $rankFields);
    }
}
