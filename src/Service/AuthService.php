<?php

namespace App\Service;

use App\Entity\Mailbox;
use App\Entity\MailboxToken;
use App\Entity\Token;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function getUserFromToken(string $tokenString): ?User
    {
        $tokenRepository = $this->entityManager->getRepository(Token::class);
        $token = $tokenRepository->findOneBy(['token' => $tokenString]);

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        if (!$user->isActive()) {
            return null;
        }

        return $user;
    }

    public function getMailboxFromToken(string $tokenString): ?Mailbox
    {
        $tokenRepository = $this->entityManager->getRepository(MailboxToken::class);
        $token = $tokenRepository->findOneBy(['token' => $tokenString]);

        if (!$token) {
            return null;
        }

        $mailbox = $token->getMailbox();

        if (!$mailbox->isActive()) {
            return null;
        }

        return $mailbox;
    }

    public function getAuthenticatedEntity(string $tokenString): User|Mailbox|null
    {
        $user = $this->getUserFromToken($tokenString);
        if ($user) {
            return $user;
        }

        return $this->getMailboxFromToken($tokenString);
    }

    public function extractTokenFromHeader(?string $authHeader): ?string
    {
        if (!$authHeader) {
            return null;
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }
}
