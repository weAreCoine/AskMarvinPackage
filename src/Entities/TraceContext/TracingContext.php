<?php

declare(strict_types=1);

namespace Marvin\Ask\Entities\TraceContext;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class TracingContext
{
    public string $traceId {
        get {
            return $this->traceId;
        }
    }

    /** @var Collection<int, Span> */
    public Collection $stack {
        get => $this->stack;
    }

    public string $sessionId {
        get => $this->sessionId;
    }
    public ?int $userId {
        get => $this->userId;
    }

    public Carbon $timestamp {
        get => $this->timestamp;
    }
    public string $name {
        get => $this->name;
    }

    public function __construct()
    {
        $this->stack = collect();
    }


    public function getLastOpenedSpan(): ?Span
    {
        return $this->getLastOpened(Span::class);
    }

    public function getLastOpened(?string $type = null): Span|Generation|null
    {
        return $this->stack
            ->when($type !== null, fn($q) => $q->whereInstanceOf($type))
            ->reject(fn(Event|Span|Generation $observation) => $observation instanceof Event)
            ->whereNull('endTime')
            ->last();
    }

    public function getLastOpenedGeneration(): ?Generation
    {
        return $this->getLastOpened(Generation::class);
    }

    public function hasOpenTraces(): bool
    {
        return $this->stack->whereNull('endTime')
            ->reject(fn(Event|Span|Generation $observation) => $observation instanceof Event)
            ->isNotEmpty();
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function isEmpty(): bool
    {
        return $this->stack->isEmpty();
    }


}
