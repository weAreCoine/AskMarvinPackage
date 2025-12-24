<?php

declare(strict_types=1);

namespace Marvin\Ask\DataTransferObjects\Langfuse;

use Marvin\Ask\Abstracts\AbstractDataTransferObject;

final class LangfuseHealth extends AbstractDataTransferObject
{
    public function __construct(public readonly bool $status, public readonly string $version)
    {
    }

    protected static function mapDataBeforeCreatingNewInstance(array $data): array
    {
        $data['status'] = $data['status'] === 'OK';
        return $data;
    }
}
