<?php

namespace App\DTO;

use libphonenumber\PhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

class RegistrationDTO
{
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Email(message: 'Please enter a valid email address', mode: Assert\Email::VALIDATION_MODE_HTML5)]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long')]
    public ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\EqualTo(propertyPath: 'password', message: 'The password fields must match.')]
    public ?string $confirmPassword = null;

    #[Assert\IsTrue(message: 'You must agree to our terms.')]
    public bool $agreeTerms = false;

    #[Assert\NotBlank(message: 'Please complete the reCAPTCHA verification')]
    public ?string $recaptcha = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: 'Username can only contain letters, numbers, underscores and dashes')]
    public ?string $username = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    public ?string $country = null;

    #[Assert\NotBlank(message: 'Le numéro de téléphone est requis')]
    #[AssertPhoneNumber(message: 'Veuillez entrer un numéro de téléphone valide')]
    public ?string $phone = null;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: 'getValidAccountTypes')]
    public string $accountType = 'PRIVATE';

    #[Assert\Length(max: 255)]
    public ?string $organizationName = null;

    #[Assert\Length(max: 50)]
    public ?string $organizationNumber = null;

    public static function getValidAccountTypes(): array
    {
        return ['PRIVATE', 'ENTERPRISE', 'ASSOCIATION'];
    }
}
