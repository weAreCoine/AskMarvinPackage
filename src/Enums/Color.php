<?php
declare(strict_types=1);

namespace Marvin\Ask\Enums;

enum Color: string
{
    case Danger = 'danger';
    case Success = 'success';
    case Warning = 'warning';
    case Info = 'info';
    case Primary = 'primary';
    case Gray = 'gray';
}
