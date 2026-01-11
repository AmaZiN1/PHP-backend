<?php

namespace App\Controller;

use App\Attribute\RequireAuth;
use App\DTO\CreateMailboxDTO;
use App\DTO\UpdateMailboxDTO;
use App\Entity\Domain;
use App\Entity\Mailbox;
use App\Service\AccessControlService;
use App\Service\AuditLogService;
use App\Service\EntityMapperService;
use App\Service\PasswordHasher;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/domains/{domainId}/mailboxes', name: 'mailboxes_')]
class MailboxController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogService $auditLogService,
        private EntityMapperService $mapper,
        private ValidationService $validationService,
        private AccessControlService $accessControl,
        private ValidatorInterface $validator,
        private PasswordHasher $passwordHasher
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

        $mailboxesData = array_map(
            fn(Mailbox $mailbox) => $this->mapper->mapMailbox($mailbox),
            $domain->getMailboxes()->toArray()
        );

        return new JsonResponse([
            'domain_id' => $domain->getId(),
            'domain_name' => $domain->getName(),
            'mailboxes' => $mailboxesData,
            'total' => count($mailboxesData)
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
        $dto = $this->validationService->mapRequestToDTO($data, CreateMailboxDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $mailbox = new Mailbox();
        $mailbox->setDomain($domain);
        $mailbox->setName($dto->name);
        $mailbox->setPassword($this->passwordHasher->hash($dto->password));
        $mailbox->setActive(true);

        if ($dto->footer_text !== null) {
            $mailbox->setFooterText($dto->footer_text);
        }

        $this->entityManager->persist($mailbox);
        $this->entityManager->flush();

        $this->auditLogService->log(
            eventType: 'mailbox.created',
            entityType: 'mailbox',
            entityId: $mailbox->getId(),
            actor: $authEntity,
            request: $request,
            newValue: [
                'domain_id' => $mailbox->getDomain()->getId(),
                'name' => $mailbox->getName(),
                'active' => $mailbox->isActive(),
                'footer_text' => $mailbox->getFooterText()
            ],
            status: 'success'
        );

        return new JsonResponse([
            'message' => 'Mailbox created successfully',
            'mailbox' => $this->mapper->mapMailbox($mailbox)
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[RequireAuth]
    public function update(
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        #[MapEntity(expr: 'repository.findOneBy({"id": id, "domain": domainId})')]  Mailbox $mailbox,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $dto = $this->validationService->mapRequestToDTO($data, UpdateMailboxDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $oldState = [
            'name' => $mailbox->getName(),
            'active' => $mailbox->isActive(),
            'footer_text' => $mailbox->getFooterText()
        ];
        $wasActive = $mailbox->isActive();

        $stateChanged = false;

        if ($dto->password !== null) {
            $mailbox->setPassword($this->passwordHasher->hash($dto->password));
            $stateChanged = true;
        }

        if ($dto->active !== null) {
            $mailbox->setActive($dto->active);
            $stateChanged = true;
        }

        if ($dto->footer_text !== null) {
            $mailbox->setFooterText($dto->footer_text);
            $stateChanged = true;
        }

        $this->entityManager->flush();

        if ($stateChanged) {
            $newState = [
                'name' => $mailbox->getName(),
                'active' => $mailbox->isActive(),
                'footer_text' => $mailbox->getFooterText()
            ];

            $this->auditLogService->log(
                eventType: 'mailbox.updated',
                entityType: 'mailbox',
                entityId: $mailbox->getId(),
                actor: $authEntity,
                request: $request,
                oldValue: $oldState,
                newValue: $newState,
                status: 'success'
            );

            if ($wasActive !== $mailbox->isActive()) {
                $eventType = $mailbox->isActive() ? 'mailbox.activated' : 'mailbox.deactivated';
                $this->auditLogService->log(
                    eventType: $eventType,
                    entityType: 'mailbox',
                    entityId: $mailbox->getId(),
                    actor: $authEntity,
                    request: $request,
                    oldValue: ['active' => $wasActive],
                    newValue: ['active' => $mailbox->isActive()],
                    status: 'success'
                );
            }
        }

        return new JsonResponse([
            'message' => 'Mailbox updated successfully',
            'mailbox' => $this->mapper->mapMailbox($mailbox)
        ]);
    }

    #[Route('/{id}/password', name: 'change_password', methods: ['PUT'])]
    #[RequireAuth]
    public function changePassword(
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        #[MapEntity(expr: 'repository.findOneBy({"id": id, "domain": domainId})')]  Mailbox $mailbox,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $constraint = new Assert\NotBlank(message: 'Password is required');
        $errors = $this->validator->validate($data['password'] ?? null, $constraint);

        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'Password is required'], 400);
        }

        $mailbox->setPassword($this->passwordHasher->hash($data['password']));
        $this->entityManager->flush();

        $this->auditLogService->log(
            eventType: 'mailbox.password_changed_by_admin',
            entityType: 'mailbox',
            entityId: $mailbox->getId(),
            actor: $authEntity,
            request: $request,
            newValue: [
                'mailbox_name' => $mailbox->getName(),
                'domain_id' => $mailbox->getDomain()->getId()
            ],
            status: 'success'
        );

        return new JsonResponse(['message' => 'Password changed successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[RequireAuth]
    public function delete(
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        #[MapEntity(expr: 'repository.findOneBy({"id": id, "domain": domainId})')]  Mailbox $mailbox,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $deletedMailboxData = [
            'id' => $mailbox->getId(),
            'domain_id' => $mailbox->getDomain()->getId(),
            'name' => $mailbox->getName(),
            'active' => $mailbox->isActive(),
            'footer_text' => $mailbox->getFooterText()
        ];

        $this->entityManager->remove($mailbox);
        $this->entityManager->flush();

        $this->auditLogService->log(
            eventType: 'mailbox.deleted',
            entityType: 'mailbox',
            entityId: $deletedMailboxData['id'],
            actor: $authEntity,
            request: $request,
            oldValue: $deletedMailboxData,
            status: 'success'
        );

        return new JsonResponse(['message' => 'Mailbox deleted successfully']);
    }

    #[Route('/{id}/logout-all', name: 'logout_all', methods: ['POST'])]
    #[RequireAuth]
    public function logoutAll(
        #[MapEntity(mapping: ['domainId' => 'id'])] Domain $domain,
        #[MapEntity(expr: 'repository.findOneBy({"id": id, "domain": domainId})')]  Mailbox $mailbox,
        Request $request
    ): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!$this->accessControl->canAccessDomain($authEntity, $domain)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $tokenRepo = $this->entityManager->getRepository(\App\Entity\MailboxToken::class);
        $tokens = $tokenRepo->findBy(['mailbox' => $mailbox]);

        $tokenCount = count($tokens);
        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }

        $this->entityManager->flush();

        $this->auditLogService->log(
            eventType: 'mailbox.logout_all_by_admin',
            entityType: 'mailbox',
            entityId: $mailbox->getId(),
            actor: $authEntity,
            request: $request,
            newValue: [
                'mailbox_name' => $mailbox->getName(),
                'domain_id' => $mailbox->getDomain()->getId(),
                'tokens_removed' => $tokenCount
            ],
            status: 'success'
        );

        return new JsonResponse(['message' => 'All mailbox sessions logged out successfully']);
    }
}
