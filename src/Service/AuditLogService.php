<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Mailbox;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class AuditLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Log an event to audit_logs
     *
     * @param string $eventType Event type ('user.created', 'mailbox.login.success')
     * @param string $entityType Entity type ('user', 'mailbox', 'domain')
     * @param int|null $entityId ID of the affected entity
     * @param User|Mailbox|null $actor Action performer
     * @param Request|null $request HTTP request
     * @param array|null $oldValue Previous state
     * @param array|null $newValue New state
     * @param string $status success or failure
     */
    public function log(
        string $eventType,
        string $entityType,
        ?int $entityId,
        User|Mailbox|null $actor,
        ?Request $request = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        string $status = 'success'
    ): void {
        $log = new AuditLog();
        $log->setEventType($eventType);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setStatus($status);

        if ($actor instanceof User) {
            $log->setActorType('user');
            $log->setActorId($actor->getId());
        } elseif ($actor instanceof Mailbox) {
            $log->setActorType('mailbox');
            $log->setActorId($actor->getId());
        } else {
            $log->setActorType('system');
            $log->setActorId(null);
        }

        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
        }

        if ($oldValue !== null) {
            $log->setOldValue($oldValue);
        }
        if ($newValue !== null) {
            $log->setNewValue($newValue);
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logLogin(
        string $email,
        bool $success,
        string $actorType,
        ?int $actorId,
        ?Request $request
    ): void {
        $log = new AuditLog();
        $log->setEventType($actorType . '.login.' . ($success ? 'success' : 'failed'));
        $log->setEntityType($actorType);
        $log->setEntityId($actorId);
        $log->setActorType($actorType);
        $log->setActorId($actorId);
        $log->setStatus($success ? 'success' : 'failure');
        $log->setNewValue(['email' => $email]);

        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
