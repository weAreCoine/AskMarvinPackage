<?php

declare(strict_types=1);

namespace Marvin\Ask\Clients;

use Illuminate\Support\Collection;
use Marvin\Ask\Abstracts\VectorialDatabaseClient;
use Marvin\Ask\DataTransferObjects\Pinecone\PineconeSearchMatch;
use Marvin\Ask\Entities\VectorialDB\SearchMatch;
use Marvin\Ask\Handlers\ExceptionsHandler;
use Marvin\Ask\Http\Requests\PineconeRerankVectorsRequest;
use Probots\Pinecone\Client as Pinecone;
use Probots\Pinecone\Requests\Exceptions\MissingHostException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

class PineconeClient extends VectorialDatabaseClient
{
    public function __construct(Pinecone $pinecone)
    {
        $this->client = $pinecone;
    }

    public function getStats(): Collection
    {
        try {
            $response = $this->client->data()->vectors()->stats();
            if ($response->successful()) {
                return $response->collect();
            }
            return collect();
        } catch (MissingHostException $e) {
            ExceptionsHandler::handle($e);
            return collect();
        }
    }

    public function describeIndex(bool $safe = false): Collection|false
    {
        $response = $this->client->control()->index(config('services.pinecone.index_name'))->describe();
        if ($response->successful()) {
            return $response->collect();
        }
        return $safe ? collect() : false;
    }

    public function rankedSearch(
        string $userPrompt,
        array  $vector,
        int    $topK = 10,
        bool   $filterResults = true,
        float  $minScore = 0.3,
        int    $topN = 3,
        array  $rankFields = ['content'],
    ): Collection
    {
        $results = $this->search($vector, $topK, $filterResults, $minScore);
        return $this->rerank($userPrompt, $results, $topN, $rankFields);
    }


    /**
     * @param array<float> $vector
     * @return Collection<SearchMatch>
     */
    public function search(
        array $vector,
        int   $topK = 10,
        bool  $filterResults = true,
        float $minScore = 0.3,
    ): Collection
    {
        try {
            $results = $this->client
                ->data()
                ->vectors()
                ->query(vector: $vector, namespace: config('services.pinecone.namespace'), topK: $topK);
            if (!$results->successful()) {
                return collect();
            }

            $results = $results->collect('matches')
                ->map(function (array $match): ?SearchMatch {
                    try {
                        return PineconeSearchMatch::fromArray($match)->toEntity();
                    } catch (Throwable $e) {
                        ExceptionsHandler::handle($e);
                        return null;
                    }
                })->filter();

            if ($filterResults) {
                $results = $results->filter(fn(SearchMatch $match) => $match->score >= $minScore);
            }
            return $results;
        } catch (Throwable $e) {
            ExceptionsHandler::handle($e);
            return collect();
        }
    }

    /**
     * @param Collection<SearchMatch> $results
     */
    public function rerank(
        string     $userPrompt,
        Collection $results,
        int        $topN = 3,
        array      $rankFields = ['content'],
    ): Collection
    {
        try {
            $request = new PineconeRerankVectorsRequest(
                queryText: $userPrompt,
                results: $results->map(fn(SearchMatch $match) => $match->toArray())->toArray(),
                topN: $topN,
                rankFields: $rankFields,
            );

            $results = $this->client->send($request);
            if (!$results->successful()) {
                return collect();
            }
            return $results->collect('data')
                ->map(function (array $match): ?SearchMatch {
                    try {
                        return PineconeSearchMatch::fromArray($match['document'])->toEntity();
                    } catch (Throwable $e) {
                        ExceptionsHandler::handle($e);
                        return null;
                    }
                })->filter();
        } catch (FatalRequestException|RequestException $e) {
            ExceptionsHandler::handle($e);
            return collect();
        }
    }
}
