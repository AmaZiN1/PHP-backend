<?php

namespace App\Service;

use App\Entity\Alias;
use App\Entity\AuditLog;
use App\Entity\Domain;
use App\Entity\Mailbox;
use App\Entity\MailboxAutoresponder;
use App\Entity\User;

class EntityMapperService
{
    public function mapDomain(Domain $domain): array
    {
        return [
            'id' => $domain->getId(),
            'name' => $domain->getName(),
            'active' => $domain->isActive(),
            'created_at' => $domain->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $domain->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function mapUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'role' => $user->getRole()->value,
            'active' => $user->isActive(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function mapMailbox(Mailbox $mailbox): array
    {
        $autoresponder = $mailbox->getAutoresponder();
        $hasActiveAutoresponder = $autoresponder !== null && $autoresponder->isActive();

        return [
            'id' => $mailbox->getId(),
            'domain_id' => $mailbox->getDomain()->getId(),
            'name' => $mailbox->getName(),
            'active' => $mailbox->isActive(),
            'footer_text' => $mailbox->getFooterText(),
            'has_active_autoresponder' => $hasActiveAutoresponder,
            'created_at' => $mailbox->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $mailbox->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function mapAlias(Alias $alias): array
    {
        return [
            'id' => $alias->getId(),
            'domain_id' => $alias->getDomain()->getId(),
            'name' => $alias->getName(),
            'to' => $alias->getTo(),
            'active' => $alias->isActive(),
            'created_at' => $alias->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $alias->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function mapAutoresponder(MailboxAutoresponder $autoresponder): array
    {
        return [
            'id' => $autoresponder->getId(),
            'active' => $autoresponder->isActive(),
            'subject' => $autoresponder->getSubject(),
            'body' => $autoresponder->getBody(),
            'start_date' => $autoresponder->getStartDate()?->format('Y-m-d H:i:s'),
            'end_date' => $autoresponder->getEndDate()?->format('Y-m-d H:i:s'),
            'created_at' => $autoresponder->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $autoresponder->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function mapAuditLog(AuditLog $log): array
    {
        return [
            'id' => $log->getId(),
            'actor_type' => $log->getActorType(),
            'actor_id' => $log->getActorId(),
            'event_type' => $log->getEventType(),
            'entity_type' => $log->getEntityType(),
            'entity_id' => $log->getEntityId(),
            'old_value' => $log->getOldValue(),
            'new_value' => $log->getNewValue(),
            'ip_address' => $log->getIpAddress(),
            'user_agent' => $log->getUserAgent(),
            'status' => $log->getStatus(),
            'created_at' => $log->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }
}

