<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAliasDTO
{
    #[Assert\NotBlank(message: 'Alias name is required')]
    #[Assert\Length(min: 1, max: 255)]
    public string $name;

    #[Assert\NotBlank(message: 'Alias "to" field is required')]
    #[Assert\Email(message: 'Invalid email address')]
    public string $to;

    #[Assert\Type('bool')]
    public ?bool $active = true;
}
