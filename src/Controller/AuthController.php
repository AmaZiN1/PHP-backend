<?php

namespace App\Controller;

use App\Attribute\RequireAuth;
use App\Entity\Domain;
use App\Entity\Mailbox;
use App\Entity\MailboxToken;
use App\Entity\Token;
use App\Entity\User;
use App\Service\AuditLogService;
use App\Service\AuthService;
use App\Service\PasswordHasher;
use App\Service\TokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'auth_')]
class AuthController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenGenerator $tokenGenerator,
        private AuthService $authService,
        private AuditLogService $auditLogService,
        private PasswordHasher $passwordHasher
    ) {}

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Email and password are required'
            ], 400);
        }

        $email = $data['email'];

        if (str_contains($email, '@')) {
            [$localPart, $domainPart] = explode('@', $email, 2);

            $domain = $this->entityManager->getRepository(Domain::class)
                ->findOneBy(['name' => $domainPart]);

            if ($domain) {
                return $this->loginMailbox($localPart, $domain, $data['password'], $request);
            }
        }

        return $this->loginUser($email, $data['password'], $request);
    }

    private function loginUser(string $email, string $password, Request $request): JsonResponse
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->auditLogService->logLogin($email, false, 'user', null, $request);
            return new JsonResponse([
                'error' => 'Invalid credentials'
            ], 401);
        }

        if (!$this->passwordHasher->verify($password, $user->getPassword())) {
            $this->auditLogService->logLogin($email, false, 'user', $user->getId(), $request);
            return new JsonResponse([
                'error' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->isActive()) {
            $this->auditLogService->log(
                'user.login.failed',
                'user',
                $user->getId(),
                $user,
                $request,
                null,
                ['reason' => 'account_not_active'],
                'failure'
            );
            return new JsonResponse([
                'error' => 'Account is not active'
            ], 403);
        }

        $tokenString = $this->tokenGenerator->generate();

        $token = new Token();
        $token->setToken($tokenString);
        $token->setUser($user);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->auditLogService->logLogin($email, true, 'user', $user->getId(), $request);

        return new JsonResponse([
            'token' => $tokenString
        ]);
    }

    private function loginMailbox(string $name, Domain $domain, string $password, Request $request): JsonResponse
    {
        $email = $name . '@' . $domain->getName();
        $mailbox = $this->entityManager->getRepository(Mailbox::class)
            ->findOneBy(['name' => $name, 'domain' => $domain]);

        if (!$mailbox) {
            $this->auditLogService->logLogin($email, false, 'mailbox', null, $request);
            return new JsonResponse([
                'error' => 'Invalid credentials'
            ], 401);
        }

        if (!$this->passwordHasher->verify($password, $mailbox->getPassword())) {
            $this->auditLogService->logLogin($email, false, 'mailbox', $mailbox->getId(), $request);
            return new JsonResponse([
                'error' => 'Invalid credentials'
            ], 401);
        }

        if (!$mailbox->isActive()) {
            $this->auditLogService->log(
                'mailbox.login.failed',
                'mailbox',
                $mailbox->getId(),
                $mailbox,
                $request,
                null,
                ['reason' => 'account_not_active'],
                'failure'
            );
            return new JsonResponse([
                'error' => 'Account is not active'
            ], 403);
        }

        $tokenString = $this->tokenGenerator->generate();

        $mailboxToken = new MailboxToken();
        $mailboxToken->setToken($tokenString);
        $mailboxToken->setMailbox($mailbox);

        $this->entityManager->persist($mailboxToken);
        $this->entityManager->flush();

        $this->auditLogService->logLogin($email, true, 'mailbox', $mailbox->getId(), $request);

        return new JsonResponse([
            'token' => $tokenString
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[RequireAuth]
    public function logout(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        $authHeader = $request->headers->get('Authorization');
        $tokenString = $this->authService->extractTokenFromHeader($authHeader);

        if (!$tokenString) {
            return new JsonResponse([
                'error' => 'Missing or invalid authorization token'
            ], 401);
        }

        $tokenRepository = $this->entityManager->getRepository(Token::class);
        $token = $tokenRepository->findOneBy(['token' => $tokenString]);

        if ($token) {
            $user = $token->getUser();
            $this->entityManager->remove($token);
            $this->entityManager->flush();

            $this->auditLogService->log(
                'user.logout',
                'user',
                $user->getId(),
                $user,
                $request,
                null,
                null,
                'success'
            );

            return new JsonResponse([
                'message' => 'Logged out successfully'
            ]);
        }

        $mailboxTokenRepository = $this->entityManager->getRepository(MailboxToken::class);
        $mailboxToken = $mailboxTokenRepository->findOneBy(['token' => $tokenString]);

        if ($mailboxToken) {
            $mailbox = $mailboxToken->getMailbox();
            $this->entityManager->remove($mailboxToken);
            $this->entityManager->flush();

            $this->auditLogService->log(
                'mailbox.logout',
                'mailbox',
                $mailbox->getId(),
                $mailbox,
                $request,
                null,
                null,
                'success'
            );

            return new JsonResponse([
                'message' => 'Logged out successfully'
            ]);
        }

        return new JsonResponse([
            'message' => 'Token not found'
        ]);
    }

    #[Route('/logout-all', name: 'logout_all', methods: ['POST'])]
    #[RequireAuth]
    public function logoutAll(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if ($authEntity instanceof User) {
            $tokenRepository = $this->entityManager->getRepository(Token::class);
            $tokens = $tokenRepository->findBy(['user' => $authEntity]);
            $count = count($tokens);

            foreach ($tokens as $token) {
                $this->entityManager->remove($token);
            }

            $this->entityManager->flush();

            $this->auditLogService->log(
                'user.logout_all',
                'user',
                $authEntity->getId(),
                $authEntity,
                $request,
                null,
                ['sessions_count' => $count],
                'success'
            );
        } elseif ($authEntity instanceof Mailbox) {
            $mailboxTokenRepository = $this->entityManager->getRepository(MailboxToken::class);
            $tokens = $mailboxTokenRepository->findBy(['mailbox' => $authEntity]);
            $count = count($tokens);

            foreach ($tokens as $token) {
                $this->entityManager->remove($token);
            }

            $this->entityManager->flush();

            $this->auditLogService->log(
                'mailbox.logout_all',
                'mailbox',
                $authEntity->getId(),
                $authEntity,
                $request,
                null,
                ['sessions_count' => $count],
                'success'
            );
        }

        return new JsonResponse([
            'message' => 'All sessions logged out successfully'
        ]);
    }
}
