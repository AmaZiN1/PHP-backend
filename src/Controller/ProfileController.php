<?php

namespace App\Controller;

use App\Attribute\RequireAuth;
use App\Entity\Mailbox;
use App\Entity\User;
use App\Service\AuditLogService;
use App\Service\EntityMapperService;
use App\Service\PasswordHasher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ProfileController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogService $auditLogService,
        private EntityMapperService $mapper,
        private PasswordHasher $passwordHasher
    ) {}

    #[Route('/me', name: 'profile', methods: ['GET'])]
    #[RequireAuth]
    public function getProfile(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if ($authEntity instanceof Mailbox) {
            return new JsonResponse($this->mapper->mapMailbox($authEntity));
        }

        return new JsonResponse($this->mapper->mapUser($authEntity));
    }

    #[Route('/me/password', name: 'profile_change_password', methods: ['PUT'])]
    #[RequireAuth]
    public function changePassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['current_password']) || empty($data['current_password'])) {
            return new JsonResponse([
                'error' => 'Field "current_password" is required'
            ], 400);
        }

        if (!isset($data['new_password']) || empty($data['new_password'])) {
            return new JsonResponse([
                'error' => 'Field "new_password" is required'
            ], 400);
        }

        $authEntity = $request->attributes->get('auth_user');

        if (!$this->passwordHasher->verify($data['current_password'], $authEntity->getPassword())) {
            return new JsonResponse([
                'error' => 'Current password is incorrect'
            ], 401);
        }

        $authEntity->setPassword($this->passwordHasher->hash($data['new_password']));
        $this->entityManager->flush();

        if ($authEntity instanceof User) {
            $this->auditLogService->log(
                'user.password_changed',
                'user',
                $authEntity->getId(),
                $authEntity,
                $request,
                null,
                null,
                'success'
            );
        } elseif ($authEntity instanceof Mailbox) {
            $this->auditLogService->log(
                'mailbox.password_changed',
                'mailbox',
                $authEntity->getId(),
                $authEntity,
                $request,
                null,
                null,
                'success'
            );
        }

        return new JsonResponse([
            'message' => 'Password changed successfully'
        ]);
    }

    #[Route('/me/domains', name: 'profile_domains', methods: ['GET'])]
    #[RequireAuth]
    public function getMyDomains(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if ($authEntity instanceof Mailbox) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $user = $authEntity;

        $domainsData = array_map(
            fn($domain) => $this->mapper->mapDomain($domain),
            $user->getDomains()->toArray()
        );

        return new JsonResponse([
            'domains' => $domainsData,
            'total' => count($domainsData)
        ]);
    }
}
