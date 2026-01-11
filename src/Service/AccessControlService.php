<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\Mailbox;
use App\Entity\User;
use App\Enum\UserRole;

class AccessControlService
{
    public function canAccessDomain(User|Mailbox $authEntity, Domain $domain): bool
    {
        if ($authEntity instanceof Mailbox) {
            return false;
        }

        if ($authEntity->getRole() === UserRole::ADMINISTRATOR) {
            return true;
        }

        return $authEntity->getDomains()->contains($domain);
    }

    public function canManageMailbox(User|Mailbox $authEntity, Mailbox $mailbox): bool
    {
        if ($authEntity instanceof Mailbox) {
            return $authEntity->getId() === $mailbox->getId();
        }

        return $this->canAccessDomain($authEntity, $mailbox->getDomain());
    }

    public function isAdministrator(User|Mailbox $authEntity): bool
    {
        return $authEntity instanceof User && $authEntity->getRole() === UserRole::ADMINISTRATOR;
    }
}
