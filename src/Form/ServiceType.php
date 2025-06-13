<?php

namespace App\Form;

use App\Entity\Cause;
use App\Entity\Service;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du service',
                'attr' => [
                    'placeholder' => 'Entrez le titre du service',
                    'maxlength' => 255,
                    'data-control' => 'input'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est requis']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('about', TextareaType::class, [
                'label' => 'A propos',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'A propos',
                    'data-control' => 'textarea'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'A propos est requise']),
                    new Length([
                        'min' => 50,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Décrivez la le service en détail',
                    'data-control' => 'textarea'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La description est requise']),
                    new Length([
                        'min' => 100,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Logo',
                'required' => false,
                'allow_delete' => false,
                'delete_label' => 'Supprimer l\'image',
                'download_label' => false,
                'image_uri' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png'
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '1M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png'
                        ],
                    ])
                ]
            ])
            ->add('website', UrlType::class, [
                'label' => 'Site web',
                'attr' => [
                    'placeholder' => 'Entrez le site web',
                    'maxlength' => 255,
                    'data-control' => 'input'
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Email Address'
                ]
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
                                $countryCode = "fr";
                                if (!$countryCode) {
                                    $context->buildViolation('Veuillez d\'abord sélectionner un pays')
                                        ->addViolation();
                                    return;
                                }

                                $phoneNumber = $phoneUtil->parse($phone, $countryCode);
                                if (!$phoneUtil->isValidNumber($phoneNumber)) {
                                    $context->buildViolation('Le numéro de téléphone n\'est pas valide pour France')
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'service_update',
        ]);
    }
}
