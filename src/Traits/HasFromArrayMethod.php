<?php

declare(strict_types=1);

namespace Marvin\Ask\Traits;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Marvin\Ask\Handlers\ExceptionsHandler;
use ReflectionClass;
use ReflectionException;

trait HasFromArrayMethod
{
    public static function fromArray(array $data): static|false
    {
        $data = static::mapDataBeforeCreatingNewInstance($data);
        $reflection = new ReflectionClass(static::class);
        $params = [];
        foreach ($reflection->getConstructor()->getParameters() as $param) {
            $name = $param->getName();
            $snakeName = Str::snake($name);

            if (array_key_exists($snakeName, $data)) {
                $params[$name] = $data[$snakeName];
            } elseif (array_key_exists($name, $data)) {
                $params[$name] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $params[$name] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException("Missing required parameter '$name' for ".static::class);
            }
        }
        try {
            return $reflection->newInstanceArgs($params);
        } catch (ReflectionException $e) {
            ExceptionsHandler::handle($e);

            return false;
        }
    }

    protected static function mapDataBeforeCreatingNewInstance(array $data): array
    {
        return $data;
    }
}
