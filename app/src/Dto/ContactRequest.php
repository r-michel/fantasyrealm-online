<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContactRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    public ?string $username = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 150)]
    public ?string $subject = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 20, max: 3000)]
    public ?string $message = null;
}
