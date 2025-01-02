<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationDTO
{
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Email(message:'Please enter a valid email address', mode: Assert\Email::VALIDATION_MODE_HTML5)]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long')]
    public ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\EqualTo(propertyPath: 'password', message: 'The password fields must match.')]
    public ?string $confirmPassword = null;

    #[Assert\NotBlank(message: 'Referral code is required')]
    #[Assert\Length(min: 6)]
    public ?string $referralCode = null;

    #[Assert\NotBlank(message: 'Project description is required')]
    #[Assert\Length(
        min: 100,
        max: 2000,
        minMessage: 'Your project description must be at least {{ limit }} characters long',
        maxMessage: 'Your project description cannot be longer than {{ limit }} characters'
    )]
    public ?string $projectDescription = null;

    #[Assert\IsTrue(message: 'You must agree to our terms.')]
    public bool $agreeTerms = false;

    #[Assert\NotBlank(message: 'Please complete the reCAPTCHA verification')]
    public ?string $recaptcha = null;
}
