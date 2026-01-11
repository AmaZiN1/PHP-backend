<?php

namespace App\Service;

class TokenGenerator
{
    public function generate(int $length = 18): string
    {
        return bin2hex(random_bytes($length));
    }
}
