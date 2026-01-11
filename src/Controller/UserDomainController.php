<?php

namespace App\Controller;

use App\Attribute\RequireRole;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users/{userId}/domains', name: 'user_domains_')]
class UserDomainController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogService $auditLogService
    ) {}

    #[Route('/{domainId}', name: 'assign', methods: ['POST'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function assign(
        #[MapEntity(mapping: ['userId' => 'id'])] User $user,
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        Request $request
    ): JsonResponse
    {
        if ($user->getDomains()->contains($domain)) {
            return new JsonResponse(['error' => 'User already assigned to this domain'], 409);
        }

        $user->addDomain($domain);
        $this->entityManager->flush();

        $authUser = $request->attributes->get('auth_user');
        $this->auditLogService->log(
            'user_domain.assigned',
            'user_domain',
            $domain->getId(),
            $authUser,
            $request,
            null,
            [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'domain_id' => $domain->getId(),
                'domain_name' => $domain->getName()
            ],
            'success'
        );

        return new JsonResponse([
            'message' => 'User assigned to domain successfully',
            'user_id' => $user->getId(),
            'domain_id' => $domain->getId(),
            'domain_name' => $domain->getName()
        ], 201);
    }

    #[Route('/{domainId}', name: 'unassign', methods: ['DELETE'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function unassign(
        #[MapEntity(mapping: ['userId' => 'id'])] User $user,
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        Request $request
    ): JsonResponse
    {
        if (!$user->getDomains()->contains($domain)) {
            return new JsonResponse(['error' => 'User is not assigned to this domain'], 404);
        }

        $user->removeDomain($domain);
        $this->entityManager->flush();

        $authUser = $request->attributes->get('auth_user');
        $this->auditLogService->log(
            'user_domain.unassigned',
            'user_domain',
            $domain->getId(),
            $authUser,
            $request,
            [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'domain_id' => $domain->getId(),
                'domain_name' => $domain->getName()
            ],
            null,
            'success'
        );

        return new JsonResponse([
            'message' => 'User unassigned from domain successfully'
        ]);
    }
}
