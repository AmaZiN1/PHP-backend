<?php

namespace App\Enum;

enum UserRole: string
{
    case ADMINISTRATOR = 'administrator';
    case USER = 'user';
}
