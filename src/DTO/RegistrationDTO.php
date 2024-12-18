<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $username;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;

    #[Assert\EqualTo(propertyPath: 'password', message: 'The password fields must match.')]
    public string $confirmPassword;

    #[Assert\IsTrue(message: 'You must agree to our terms.')]
    public bool $agreeTerms;
}
