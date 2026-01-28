<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateMailboxDTO
{
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters')]
    public ?string $password = null;

    public ?string $footer_text = null;

    #[Assert\Type('bool')]
    public ?bool $active = null;
}
