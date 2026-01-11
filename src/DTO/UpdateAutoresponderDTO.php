<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateAutoresponderDTO
{
    #[Assert\NotBlank(message: 'Subject is required')]
    #[Assert\Length(min: 1, max: 255, minMessage: 'Subject must be at least {{ limit }} characters', maxMessage: 'Subject must not exceed {{ limit }} characters')]
    public ?string $subject = null;

    #[Assert\NotBlank(message: 'Body is required')]
    public ?string $body = null;

    #[Assert\Type('bool', message: 'Active must be a boolean')]
    public ?bool $active = null;

    public ?string $start_date = null;

    public ?string $end_date = null;
}
