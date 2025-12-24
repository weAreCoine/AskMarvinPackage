<?php

declare(strict_types=1);

namespace Marvin\Ask\Contracts;

interface DtoContract
{
    public function toEntity(): object;
}
