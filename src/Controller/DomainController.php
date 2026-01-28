<?php

namespace App\Controller;

use App\Attribute\RequireAuth;
use App\Attribute\RequireRole;
use App\DTO\CreateDomainDTO;
use App\DTO\UpdateDomainDTO;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\AccessControlService;
use App\Service\AuditLogService;
use App\Service\EntityMapperService;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/domains', name: 'domains_')]
class DomainController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogService $auditLogService,
        private EntityMapperService $mapper,
        private ValidationService $validationService,
        private AccessControlService $accessControl
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function list(): JsonResponse
    {
        $domains = $this->entityManager->getRepository(Domain::class)->findAll();

        $domainsData = array_map(
            fn(Domain $domain) => $this->mapper->mapDomain($domain),
            $domains
        );

        return new JsonResponse([
            'domains' => $domainsData,
            'total' => count($domainsData)
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = $this->validationService->mapRequestToDTO($data, CreateDomainDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $existingDomain = $this->entityManager->getRepository(Domain::class)
            ->findOneBy(['name' => $dto->name]);

        if ($existingDomain) {
            return new JsonResponse([
                'error' => 'Domain with this name already exists'
            ], 409);
        }

        $domain = new Domain();
        $domain->setName($dto->name);

        $this->entityManager->persist($domain);
        $this->entityManager->flush();

        $authUser = $request->attributes->get('auth_user');
        $this->auditLogService->log(
            eventType: 'domain.created',
            entityType: 'domain',
            entityId: $domain->getId(),
            actor: $authUser,
            request: $request,
            newValue: ['name' => $domain->getName(), 'active' => $domain->isActive()],
            status: 'success'
        );

        return new JsonResponse([
            'message' => 'Domain created successfully',
            'domain' => $this->mapper->mapDomain($domain)
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function update(
        #[MapEntity] Domain $domain,
        Request $request
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = $this->validationService->mapRequestToDTO($data, UpdateDomainDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $oldValue = ['name' => $domain->getName(), 'active' => $domain->isActive()];
        $eventTypes = [];

        if ($dto->active !== null) {
            $oldActive = $domain->isActive();
            $domain->setActive($dto->active);

            if ($oldActive === false && $dto->active === true) {
                $eventTypes[] = 'domain.activated';
            } elseif ($oldActive === true && $dto->active === false) {
                $eventTypes[] = 'domain.deactivated';
            }
        }

        $this->entityManager->flush();

        $authUser = $request->attributes->get('auth_user');
        $newValue = ['name' => $domain->getName(), 'active' => $domain->isActive()];

        $this->auditLogService->log(
            eventType: 'domain.updated',
            entityType: 'domain',
            entityId: $domain->getId(),
            actor: $authUser,
            request: $request,
            oldValue: $oldValue,
            newValue: $newValue,
            status: 'success'
        );

        foreach ($eventTypes as $eventType) {
            $this->auditLogService->log(
                eventType: $eventType,
                entityType: 'domain',
                entityId: $domain->getId(),
                actor: $authUser,
                request: $request,
                oldValue: $oldValue,
                newValue: $newValue,
                status: 'success'
            );
        }

        return new JsonResponse([
            'message' => 'Domain updated successfully',
            'domain' => $this->mapper->mapDomain($domain)
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function delete(
        #[MapEntity] Domain $domain,
        Request $request
    ): JsonResponse
    {
        $oldValue = ['name' => $domain->getName(), 'active' => $domain->isActive()];
        $domainId = $domain->getId();

        $this->entityManager->remove($domain);
        $this->entityManager->flush();

        $authUser = $request->attributes->get('auth_user');
        $this->auditLogService->log(
            eventType: 'domain.deleted',
            entityType: 'domain',
            entityId: $domainId,
            actor: $authUser,
            request: $request,
            oldValue: $oldValue,
            status: 'success'
        );

        return new JsonResponse([
            'message' => 'Domain deleted successfully'
        ]);
    }

    #[Route('/{id}/managers', name: 'managers', methods: ['GET'])]
    #[RequireAuth]
    public function getManagers(
        #[MapEntity] Domain $domain,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->join('u.domains', 'd')
            ->where('d.id = :domainId')
            ->setParameter('domainId', $domain->getId())
            ->getQuery()
            ->getResult();

        $managersData = array_map(
            fn(User $user) => $this->mapper->mapUser($user),
            $users
        );

        return new JsonResponse([
            'domain_id' => $domain->getId(),
            'domain_name' => $domain->getName(),
            'managers' => $managersData,
            'total' => count($managersData)
        ]);
    }
}
