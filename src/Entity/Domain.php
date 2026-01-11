<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'domains')]
#[ORM\HasLifecycleCallbacks]
class Domain
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $backup = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updated_at;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'domains')]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: Alias::class, mappedBy: 'domain', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $aliases;

    #[ORM\OneToMany(targetEntity: Mailbox::class, mappedBy: 'domain', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $mailboxes;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->users = new ArrayCollection();
        $this->aliases = new ArrayCollection();
        $this->mailboxes = new ArrayCollection();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updated_at;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addDomain($this);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeDomain($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Alias>
     */
    public function getAliases(): Collection
    {
        return $this->aliases;
    }

    public function addAlias(Alias $alias): self
    {
        if (!$this->aliases->contains($alias)) {
            $this->aliases->add($alias);
            $alias->setDomain($this);
        }
        return $this;
    }

    public function removeAlias(Alias $alias): self
    {
        if ($this->aliases->removeElement($alias)) {
            if ($alias->getDomain() === $this) {
                $alias->setDomain(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Mailbox>
     */
    public function getMailboxes(): Collection
    {
        return $this->mailboxes;
    }

    public function addMailbox(Mailbox $mailbox): self
    {
        if (!$this->mailboxes->contains($mailbox)) {
            $this->mailboxes->add($mailbox);
            $mailbox->setDomain($this);
        }
        return $this;
    }

    public function removeMailbox(Mailbox $mailbox): self
    {
        if ($this->mailboxes->removeElement($mailbox)) {
            if ($mailbox->getDomain() === $this) {
                $mailbox->setDomain(null);
            }
        }
        return $this;
    }
}
