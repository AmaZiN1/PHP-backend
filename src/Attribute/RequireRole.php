<?php

namespace App\Attribute;

use App\Enum\UserRole;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class RequireRole
{
    public function __construct(
        public readonly UserRole $role
    ) {}
}
