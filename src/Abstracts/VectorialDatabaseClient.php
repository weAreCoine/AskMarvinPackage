<?php

declare(strict_types=1);

namespace Marvin\Ask\Abstracts;

use Illuminate\Support\Collection;
use Marvin\Ask\Contracts\VectorialDatabaseClientContract;

abstract class VectorialDatabaseClient
    implements VectorialDatabaseClientContract
{
    public mixed $client {
        get => $this->client;
    }
    public ?string $metric {
        get => $this->describeIndex(true)->get('metric');
    }

    public ?int $dimension {
        get => $this->describeIndex(true)->get('dimension');
    }

    public ?string $name {
        get => $this->describeIndex(true)->get('name');
    }

    public ?string $host {
        get => $this->describeIndex(true)->get('host');
    }

    public bool $isReady {
        get => $this->describeIndex(true)->get('ready', false);
    }

    public ?array $hostSpecifics {
        get => $this->describeIndex(true)->get('spec');
    }

    public string $rerankModel {
        get => $this->rerankModel;
    }


    abstract public function describeIndex(bool $safe = false
    ): Collection|false;

    abstract public function search(
        array $vector,
        int   $topK = 10,
        bool  $filterResults = true,
        float $minScore = 0.7,
    ): Collection;

    abstract public function rerank(
        string     $userPrompt,
        Collection $results,
        int        $topN = 3,
        array      $rankFields = ['content'],
    ): Collection;

    abstract public function rankedSearch(
        string $userPrompt,
        array  $vector,
        int    $topK = 3,
        bool   $filterResults = true,
        float  $minScore = 0.7,
        int    $topN = 3,
        array  $rankFields = ['content'],
    ): Collection;
}
