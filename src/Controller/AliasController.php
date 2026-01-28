<?php

namespace App\Controller;

use App\Attribute\RequireAuth;
use App\DTO\CreateAliasDTO;
use App\DTO\UpdateAliasDTO;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Service\AccessControlService;
use App\Service\AuditLogService;
use App\Service\EntityMapperService;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/domains/{domainId}/aliases', name: 'aliases_')]
class AliasController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogService $auditLogService,
        private EntityMapperService $mapper,
        private ValidationService $validationService,
        private AccessControlService $accessControl
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[RequireAuth]
    public function list(
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $aliasesData = array_map(
            fn(Alias $alias) => $this->mapper->mapAlias($alias),
            $domain->getAliases()->toArray()
        );

        return new JsonResponse([
            'domain_id' => $domain->getId(),
            'domain_name' => $domain->getName(),
            'aliases' => $aliasesData,
            'total' => count($aliasesData)
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[RequireAuth]
    public function create(
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $dto = $this->validationService->mapRequestToDTO($data, CreateAliasDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $alias = new Alias();
        $alias->setDomain($domain);
        $alias->setName($dto->name);
        $alias->setTo($dto->to);
        $alias->setActive($dto->active ?? true);

        $this->entityManager->persist($alias);
        $this->entityManager->flush();

        $this->auditLogService->log(
            eventType: 'alias.created',
            entityType: 'alias',
            entityId: $alias->getId(),
            actor: $authEntity,
            request: $request,
            newValue: [
                'name' => $alias->getName(),
                'to' => $alias->getTo(),
                'active' => $alias->isActive(),
                'domain_id' => $alias->getDomain()->getId()
            ],
            status: 'success'
        );

        return new JsonResponse([
            'message' => 'Alias created successfully',
            'alias' => $this->mapper->mapAlias($alias)
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[RequireAuth]
    public function update(
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        #[MapEntity(expr: 'repository.findOneBy({"id": id, "domain": domainId})')]  Alias $alias,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $dto = $this->validationService->mapRequestToDTO($data, UpdateAliasDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $oldValues = [
            'name' => $alias->getName(),
            'to' => $alias->getTo(),
            'active' => $alias->isActive()
        ];

        $hasChanges = false;
        $oldActiveState = $alias->isActive();

        if ($dto->to !== null) {
            $alias->setTo($dto->to);
            $hasChanges = true;
        }

        if ($dto->active !== null) {
            $alias->setActive($dto->active);
            $hasChanges = true;
        }

        $this->entityManager->flush();

        if ($hasChanges) {
            $newValues = [
                'name' => $alias->getName(),
                'to' => $alias->getTo(),
                'active' => $alias->isActive()
            ];

            $this->auditLogService->log(
                eventType: 'alias.updated',
                entityType: 'alias',
                entityId: $alias->getId(),
                actor: $authEntity,
                request: $request,
                oldValue: $oldValues,
                newValue: $newValues,
                status: 'success'
            );

            if ($oldActiveState !== $alias->isActive()) {
                $eventType = $alias->isActive() ? 'alias.activated' : 'alias.deactivated';
                $this->auditLogService->log(
                    eventType: $eventType,
                    entityType: 'alias',
                    entityId: $alias->getId(),
                    actor: $authEntity,
                    request: $request,
                    oldValue: ['active' => $oldActiveState],
                    newValue: ['active' => $alias->isActive()],
                    status: 'success'
                );
            }
        }

        return new JsonResponse([
            'message' => 'Alias updated successfully',
            'alias' => $this->mapper->mapAlias($alias)
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[RequireAuth]
    public function delete(
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        #[MapEntity(expr: 'repository.findOneBy({"id": id, "domain": domainId})')]  Alias $alias,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $oldValues = [
            'name' => $alias->getName(),
            'to' => $alias->getTo(),
            'active' => $alias->isActive(),
            'domain_id' => $alias->getDomain()->getId()
        ];
        $aliasId = $alias->getId();

        $this->entityManager->remove($alias);
        $this->entityManager->flush();

        $this->auditLogService->log(
            eventType: 'alias.deleted',
            entityType: 'alias',
            entityId: $aliasId,
            actor: $authEntity,
            request: $request,
            oldValue: $oldValues,
            status: 'success'
        );

        return new JsonResponse(['message' => 'Alias deleted successfully']);
    }
}
