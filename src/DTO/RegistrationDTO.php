<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationDTO
{
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public string $firstName;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $username;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long')]
    public string $password;

    #[Assert\EqualTo(propertyPath: 'password', message: 'The password fields must match.')]
    public string $confirmPassword;

    #[Assert\NotBlank(message: 'Referral code is required')]
    #[Assert\Length(exactly: 32)]
    public string $referralCode;

    #[Assert\NotBlank(message: 'Project description is required')]
    #[Assert\Length(
        min: 100,
        max: 2000,
        minMessage: 'Your project description must be at least {{ limit }} characters long',
        maxMessage: 'Your project description cannot be longer than {{ limit }} characters'
    )]
    public string $projectDescription;

    #[Assert\IsTrue(message: 'You must agree to our terms.')]
    public bool $agreeTerms;
}
