<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateDomainDTO
{
    #[Assert\NotBlank(message: 'Domain name is required')]
    #[Assert\Length(min: 3, max: 255)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i',
        message: 'Invalid domain name format'
    )]
    public string $name;
}
