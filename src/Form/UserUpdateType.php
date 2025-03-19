<?php

namespace App\Form;

use App\Entity\User;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('accountType', ChoiceType::class, [
                'choices' => [
                    'Particulier' => 'PRIVATE',
                    'Entreprise' => 'ENTERPRISE',
                    'Association' => 'ASSOCIATION'
                ],
                'attr' => [
                    'class' => 'select',
                    'placeholder' => 'Type de compte'
                ],
                'label' => 'Type de Compte',
                'label_attr' => ['class' => 'form-label mb-3'],
                'expanded' => false,
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
                'label_html' => true,
            ])
            ->add('firstName', TextType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Prénom'
                ],
                'label' => 'Prenom',
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Nom'
                ],
                'label' => 'Nom',
            ])
            ->add('organizationName', TextType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Nom de l\'Organisation*'
                ],
                'label' => 'Nom de l\'Organisation',
                'required' => false
            ])
            ->add('organizationNumber', TextType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Numéro d\'Identification*'
                ],
                'label' => 'Numéro d\'Identification',
                'required' => false
            ])
            ->add('country', CountryType::class, [
                'placeholder' => 'Sélectionnez votre pays*',
                'attr' => [
                    'class' => 'select',
                    'placeholder' => 'Pays'
                ],
                'label' => 'Pays',
            ])
            ->add('phone', TelType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Numéro de téléphone*',
                ],
                'label' => 'Numéro de téléphone',
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
            ]);

        if (!$options['data']->isKycVerified()) {
            $builder->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Email'
                ],
                'label' => 'Email',
            ])
                ->add('username', TextType::class, [
                    'attr' => [
                        'class' => 'input',
                        'placeholder' => 'Nom d\'utilisateur'
                    ],
                    'label' => 'Nom d\'utilisateur',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'referral_code' => null,
            'csrf_protection' => true,
        ]);
    }
}
