<?php

namespace App\Controller;

use App\Attribute\RequireAuth;
use App\DTO\UpdateAutoresponderDTO;
use App\Entity\Mailbox;
use App\Entity\MailboxAutoresponder;
use App\Service\AuditLogService;
use App\Service\EntityMapperService;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mailbox')]
class MailboxProfileController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogService $auditLogService,
        private EntityMapperService $mapper,
        private ValidationService $validationService
    ) {
    }

    #[Route('/footer', methods: ['GET'])]
    #[RequireAuth]
    public function getFooter(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!($authEntity instanceof Mailbox)) {
            return new JsonResponse(['error' => 'This endpoint is only for mailboxes'], 403);
        }

        return new JsonResponse([
            'id' => $authEntity->getId(),
            'footer_text' => $authEntity->getFooterText()
        ]);
    }

    #[Route('/footer', methods: ['PUT'])]
    #[RequireAuth]
    public function updateFooter(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!($authEntity instanceof Mailbox)) {
            return new JsonResponse(['error' => 'This endpoint is only for mailboxes'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $oldFooter = $authEntity->getFooterText();

        $authEntity->setFooterText($data['footer_text'] ?? null);
        $this->entityManager->flush();

        $this->auditLogService->log(
            'mailbox.footer_updated',
            'mailbox',
            $authEntity->getId(),
            $authEntity,
            $request,
            ['footer_text' => $oldFooter],
            ['footer_text' => $authEntity->getFooterText()],
            'success'
        );

        return new JsonResponse([
            'id' => $authEntity->getId(),
            'footer_text' => $authEntity->getFooterText()
        ]);
    }

    #[Route('/autoresponder', methods: ['GET'])]
    #[RequireAuth]
    public function getAutoresponder(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!($authEntity instanceof Mailbox)) {
            return new JsonResponse(['error' => 'This endpoint is only for mailboxes'], 403);
        }

        $autoresponder = $authEntity->getAutoresponder();

        if (!$autoresponder) {
            return new JsonResponse([
                'autoresponder' => null,
                'message' => 'No autoresponder configured'
            ]);
        }

        return new JsonResponse([
            'autoresponder' => $this->mapper->mapAutoresponder($autoresponder)
        ]);
    }

    #[Route('/autoresponder', methods: ['PUT'])]
    #[RequireAuth]
    public function updateAutoresponder(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!($authEntity instanceof Mailbox)) {
            return new JsonResponse(['error' => 'This endpoint is only for mailboxes'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $dto = $this->validationService->mapRequestToDTO($data, UpdateAutoresponderDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $autoresponder = $authEntity->getAutoresponder();
        $isNew = !$autoresponder;

        $oldValue = null;
        if (!$isNew) {
            $oldValue = [
                'active' => $autoresponder->isActive(),
                'subject' => $autoresponder->getSubject(),
                'body' => $autoresponder->getBody(),
                'start_date' => $autoresponder->getStartDate()?->format('Y-m-d H:i:s'),
                'end_date' => $autoresponder->getEndDate()?->format('Y-m-d H:i:s')
            ];
        }

        if ($isNew) {
            $autoresponder = new MailboxAutoresponder();
            $autoresponder->setMailbox($authEntity);
            $this->entityManager->persist($autoresponder);
        }

        $autoresponder->setSubject($dto->subject);
        $autoresponder->setBody($dto->body);

        if ($dto->active !== null) {
            $autoresponder->setActive($dto->active);
        }

        if ($dto->start_date !== null) {
            try {
                $startDate = new \DateTimeImmutable($dto->start_date);
                $autoresponder->setStartDate($startDate);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid start_date format'], 400);
            }
        }

        if ($dto->end_date !== null) {
            try {
                $endDate = new \DateTimeImmutable($dto->end_date);
                $autoresponder->setEndDate($endDate);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid end_date format'], 400);
            }
        }

        $this->entityManager->flush();

        $newValue = [
            'active' => $autoresponder->isActive(),
            'subject' => $autoresponder->getSubject(),
            'body' => $autoresponder->getBody(),
            'start_date' => $autoresponder->getStartDate()?->format('Y-m-d H:i:s'),
            'end_date' => $autoresponder->getEndDate()?->format('Y-m-d H:i:s')
        ];

        $eventType = $isNew ? 'autoresponder.created' : 'autoresponder.updated';

        $this->auditLogService->log(
            $eventType,
            'autoresponder',
            $authEntity->getId(),
            $authEntity,
            $request,
            $oldValue,
            $newValue,
            'success'
        );

        return new JsonResponse([
            'message' => 'Autoresponder updated successfully',
            'autoresponder' => $this->mapper->mapAutoresponder($autoresponder)
        ]);
    }

    #[Route('/autoresponder', methods: ['DELETE'])]
    #[RequireAuth]
    public function deleteAutoresponder(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if (!($authEntity instanceof Mailbox)) {
            return new JsonResponse(['error' => 'This endpoint is only for mailboxes'], 403);
        }

        $autoresponder = $authEntity->getAutoresponder();

        if (!$autoresponder) {
            return new JsonResponse(['error' => 'No autoresponder to delete'], 404);
        }

        $oldValue = [
            'active' => $autoresponder->isActive(),
            'subject' => $autoresponder->getSubject(),
            'body' => $autoresponder->getBody(),
            'start_date' => $autoresponder->getStartDate()?->format('Y-m-d H:i:s'),
            'end_date' => $autoresponder->getEndDate()?->format('Y-m-d H:i:s')
        ];

        $authEntity->setAutoresponder(null);
        $this->entityManager->remove($autoresponder);
        $this->entityManager->flush();

        $this->auditLogService->log(
            'autoresponder.deleted',
            'autoresponder',
            $authEntity->getId(),
            $authEntity,
            $request,
            $oldValue,
            null,
            'success'
        );

        return new JsonResponse(['message' => 'Autoresponder deleted successfully']);
    }
}
