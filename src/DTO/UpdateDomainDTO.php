<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateDomainDTO
{
    #[Assert\Type('bool', message: 'Active field must be boolean')]
    public ?bool $active = null;
}
