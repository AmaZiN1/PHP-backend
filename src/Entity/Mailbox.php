<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'mailboxes')]
#[ORM\HasLifecycleCallbacks]
class Mailbox
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: 'mailboxes')]
    #[ORM\JoinColumn(name: 'domain_id', nullable: false, onDelete: 'CASCADE')]
    private Domain $domain;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $password;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $footer_text = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updated_at;

    #[ORM\OneToMany(targetEntity: MailboxToken::class, mappedBy: 'mailbox', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $tokens;

    #[ORM\OneToOne(targetEntity: MailboxAutoresponder::class, mappedBy: 'mailbox', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?MailboxAutoresponder $autoresponder = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->tokens = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }

    public function setDomain(Domain $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getFooterText(): ?string
    {
        return $this->footer_text;
    }

    public function setFooterText(?string $footer_text): self
    {
        $this->footer_text = $footer_text;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updated_at;
    }

    /**
     * @return Collection<int, MailboxToken>
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function addToken(MailboxToken $token): self
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens->add($token);
            $token->setMailbox($this);
        }
        return $this;
    }

    public function removeToken(MailboxToken $token): self
    {
        if ($this->tokens->removeElement($token)) {
            if ($token->getMailbox() === $this) {
                $token->setMailbox(null);
            }
        }
        return $this;
    }

    public function getAutoresponder(): ?MailboxAutoresponder
    {
        return $this->autoresponder;
    }

    public function setAutoresponder(?MailboxAutoresponder $autoresponder): self
    {
        $this->autoresponder = $autoresponder;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->name . '@' . $this->domain->getName();
    }
}
