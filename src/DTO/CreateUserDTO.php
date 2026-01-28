<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDTO
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters')]
    public string $password;

    #[Assert\NotBlank(message: 'Firstname is required')]
    #[Assert\Length(min: 2, max: 100)]
    public string $firstname;

    #[Assert\NotBlank(message: 'Lastname is required')]
    #[Assert\Length(min: 2, max: 100)]
    public string $lastname;

    #[Assert\NotBlank(message: 'Role is required')]
    #[Assert\Choice(choices: ['administrator', 'user'], message: 'Role must be "administrator" or "user"')]
    public string $role;
}
