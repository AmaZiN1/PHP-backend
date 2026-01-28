<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateMailboxDTO
{
    #[Assert\NotBlank(message: 'Mailbox name is required')]
    #[Assert\Length(min: 1, max: 255)]
    public string $name;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters')]
    public string $password;

    public ?string $footer_text = null;
}
