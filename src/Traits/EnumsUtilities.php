<?php

declare(strict_types=1);

namespace Marvin\Ask\Traits;

use Illuminate\Support\Str;

trait EnumsUtilities
{
    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->asLabel();
        }

        return $options;
    }

    public function asLabel(): string
    {
        return Str::ucfirst(Str::lower($this->name));
    }
}
