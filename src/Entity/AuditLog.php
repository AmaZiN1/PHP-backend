<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(name: 'idx_actor', columns: ['actor_type', 'actor_id'])]
#[ORM\Index(name: 'idx_entity', columns: ['entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_event_type', columns: ['event_type'])]
#[ORM\Index(name: 'idx_created_at', columns: ['created_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $actor_type; // 'user', 'mailbox', 'system'

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $actor_id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $event_type;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $entity_type; // 'user', 'mailbox', 'domain', 'alias', 'autoresponder', 'user_domain'

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $entity_id = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $old_value = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $new_value = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ip_address = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $user_agent = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = 'success'; // 'success', 'failure'

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActorType(): string
    {
        return $this->actor_type;
    }

    public function setActorType(string $actor_type): self
    {
        $this->actor_type = $actor_type;
        return $this;
    }

    public function getActorId(): ?int
    {
        return $this->actor_id;
    }

    public function setActorId(?int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getEventType(): string
    {
        return $this->event_type;
    }

    public function setEventType(string $event_type): self
    {
        $this->event_type = $event_type;
        return $this;
    }

    public function getEntityType(): string
    {
        return $this->entity_type;
    }

    public function setEntityType(string $entity_type): self
    {
        $this->entity_type = $entity_type;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entity_id;
    }

    public function setEntityId(?int $entity_id): self
    {
        $this->entity_id = $entity_id;
        return $this;
    }

    public function getOldValue(): ?array
    {
        return $this->old_value;
    }

    public function setOldValue(?array $old_value): self
    {
        $this->old_value = $old_value;
        return $this;
    }

    public function getNewValue(): ?array
    {
        return $this->new_value;
    }

    public function setNewValue(?array $new_value): self
    {
        $this->new_value = $new_value;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(?string $ip_address): self
    {
        $this->ip_address = $ip_address;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(?string $user_agent): self
    {
        $this->user_agent = $user_agent;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }
}
