<?php

namespace App\Form;

use App\DTO\RegistrationDTO;
use App\Service\SecurityService;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
                    'Particulier' => 'PRIVATE',
                    'Entreprise' => 'ENTERPRISE',
                    'Association' => 'ASSOCIATION'
                ],
                'label' => 'Type de Compte',
                'label_attr' => ['class' => 'form-label mb-3'],
                'expanded' => true,
                'multiple' => false,
                'choice_attr' => function ($choice, $key, $value) {
                    return [
                        'class' => 'form-check-input',
                        'data-icon' => match ($value) {
                            'PRIVATE' => 'fa-user',
                            'ENTERPRISE' => 'fa-building',
                            'ASSOCIATION' => 'fa-users'
                        },
                        'data-description' => match ($value) {
                            'PRIVATE' => 'Parfait pour les membres individuels rejoignant notre communauté',
                            'ENTERPRISE' => 'Conçu pour les entreprises et entités corporatives',
                            'ASSOCIATION' => 'Idéal pour les associations et organisations à but non lucratif'
                        }
                    ];
                },
                'row_attr' => ['class' => 'account-type-selector mb-4'],
                'label_html' => true,
            ])
            ->add('username', TextType::class, [
                'attr' => ['placeholder' => 'Nom d\'utilisateur*'],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('firstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Prénom*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('organizationName', TextType::class, [
                'attr' => ['placeholder' => 'Nom de l\'Organisation*'],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box organization-field'],
                'required' => false
            ])
            ->add('organizationNumber', TextType::class, [
                'attr' => ['placeholder' => 'Numéro d\'Identification*'],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box organization-field'],
                'required' => false
            ])
            ->add('country', CountryType::class, [
                'placeholder' => 'Sélectionnez votre pays*',
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('phone', TelType::class, [
                'attr' => [
                    'placeholder' => 'Numéro de téléphone*',
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le numéro de téléphone est requis'
                    ]),
                    new Callback([
                        'callback' => function ($phone, ExecutionContextInterface $context) {
                            $phoneUtil = PhoneNumberUtil::getInstance();
                            try {
                                $countryCode = $context->getRoot()->get('country')->getData();
                                if (!$countryCode) {
                                    $context->buildViolation('Veuillez d\'abord sélectionner un pays')
                                        ->addViolation();
                                    return;
                                }

                                $phoneNumber = $phoneUtil->parse($phone, $countryCode);
                                if (!$phoneUtil->isValidNumber($phoneNumber)) {
                                    $context->buildViolation('Le numéro de téléphone n\'est pas valide pour le pays sélectionné')
                                        ->addViolation();
                                }
                            } catch (NumberParseException $e) {
                                $context->buildViolation('Le format du numéro de téléphone n\'est pas valide')
                                    ->addViolation();
                            }
                        }
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'placeholder' => 'Adresse email*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box'],
                'required' => true
            ])
            ->add('password', PasswordType::class, [
                'attr' => [
                    'placeholder' => 'Mot de passe*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('confirmPassword', PasswordType::class, [
                'attr' => [
                    'placeholder' => 'Confirmer le mot de passe*'
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
//            ->add('recaptcha', HiddenType::class, [
//                'attr' => [
//                    'class' => 'g-recaptcha-response'
//                ]
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationDTO::class,
            'referral_code' => null,
            'csrf_protection' => false,
        ]);
    }
}
