<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserDTO
{
    #[Assert\Email(message: 'Invalid email format')]
    public ?string $email = null;

    #[Assert\Length(min: 2, max: 100)]
    public ?string $firstname = null;

    #[Assert\Length(min: 2, max: 100)]
    public ?string $lastname = null;

    #[Assert\Choice(choices: ['administrator', 'user'], message: 'Role must be "administrator" or "user"')]
    public ?string $role = null;

    #[Assert\Type('bool')]
    public ?bool $active = null;
}
