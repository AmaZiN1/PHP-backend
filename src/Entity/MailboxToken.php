<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'mailbox_tokens')]
class MailboxToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Mailbox::class, inversedBy: 'tokens')]
    #[ORM\JoinColumn(name: 'mailbox_id', nullable: false, onDelete: 'CASCADE')]
    private Mailbox $mailbox;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $token;

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

    public function getMailbox(): Mailbox
    {
        return $this->mailbox;
    }

    public function setMailbox(Mailbox $mailbox): self
    {
        $this->mailbox = $mailbox;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }
}
