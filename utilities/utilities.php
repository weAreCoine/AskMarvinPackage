<?php
declare(strict_types=1);

namespace Marvin\Utilities;

function compress(string $string): string
{
    return preg_replace('/\s+/', ' ', trim($string));
}
