<?php

namespace App\Form;

use App\DTO\RegistrationDTO;
use App\Service\SecurityService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegistrationType extends AbstractType
{
    public function __construct(
        private readonly SecurityService $securityService
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('accountType', ChoiceType::class, [
                'choices' => [
                    'Private Individual' => 'PRIVATE',
                    'Enterprise/Company' => 'ENTERPRISE',
                    'Non-Profit Association' => 'ASSOCIATION'
                ],
                'label' => 'Account Type',
                'label_attr' => ['class' => 'form-label mb-3'],
                'expanded' => true,
                'multiple' => false,
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'form-check-input'];
                },
                'row_attr' => ['class' => 'account-type-selector mb-4']
            ])
            ->add('username', TextType::class, [
                'attr' => ['placeholder' => 'Username*'],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('firstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'First Name*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Last Name*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('organizationName', TextType::class, [
                'attr' => ['placeholder' => 'Organization Name*'],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box organization-field'],
                'required' => false
            ])
            ->add('organizationNumber', TextType::class, [
                'attr' => ['placeholder' => 'Organization Number*'],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box organization-field'],
                'required' => false
            ])
            ->add('country', CountryType::class, [
                'placeholder' => 'Select your country*',
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('phone', TelType::class, [
                'attr' => ['placeholder' => 'Phone Number*'],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'placeholder' => 'Email Address*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box'],
                'required' => true
            ])
            ->add('password', PasswordType::class, [
                'attr' => [
                    'placeholder' => 'Password*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('confirmPassword', PasswordType::class, [
                'attr' => [
                    'placeholder' => 'Confirm Password*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'class' => 'custom-control-input'
                ],
                'row_attr' => ['class' => 'login-register__checkbox']
            ])
            ->add('referralCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'Referral Code*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box'],
                'disabled' => !empty($options['referral_code'])
            ])
            ->add('projectDescription', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Describe your project or initiative (minimum 100 characters)*',
                    'rows' => 13,
                    'class' => 'h-auto'
                ],
                'label' => false,
                'help' => 'Tell us about your project, its goals, and how you plan to use the funds.',
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('recaptcha', HiddenType::class, [
                'attr' => [
                    'class' => 'g-recaptcha-response'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationDTO::class,
            'referral_code' => null,
            'csrf_protection' => true,
        ]);
    }
}
