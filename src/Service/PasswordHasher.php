<?php

namespace App\Service;

class PasswordHasher
{
    private const COST = 10;
    private const PREFIX = '{CRYPT}';

    public function hash(string $password): string
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, [
            'cost' => self::COST
        ]);

        return self::PREFIX . $hash;
    }

    public function verify(string $password, string $hash): bool
    {
        if (str_starts_with($hash, self::PREFIX)) {
            $hash = substr($hash, strlen(self::PREFIX));
        }

        return password_verify($password, $hash);
    }
}
