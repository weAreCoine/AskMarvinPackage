<?php

declare(strict_types=1);

namespace Marvin\Ask\Enums;

enum Role: string
{
    case Developer = 'developer';
    case Admin = 'admin';
    case Manager = 'manager';
    case User = 'user';
}
