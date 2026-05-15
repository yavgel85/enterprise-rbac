<?php

declare(strict_types=1);

namespace App\Enums;

enum DirectPermissionType: string
{
    case Grant = 'grant';
    case Deny = 'deny';
}
