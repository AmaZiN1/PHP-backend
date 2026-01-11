<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateAliasDTO
{
    #[Assert\Length(min: 1, max: 255)]
    public ?string $to = null;

    #[Assert\Type('bool')]
    public ?bool $active = null;
}
