<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class KycVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('documentType', ChoiceType::class, [
                'choices' => [
                    'Carte d\'identité nationale' => 'national_id',
                    'Passeport' => 'passport',
                    'Permis de conduire' => 'drivers_license',
                    'Titre de séjour' => 'residence_permit'
                ],
                'attr' => [
                    'class' => 'select',
                ],
                'placeholder' => 'Choisir le type de document',
                'label' => 'Type de Document',
                'required' => true
            ])
            ->add('documentNumber', TextType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Numéro du document'
                ],
                'label' => 'Numéro du Document',
                'required' => true
            ])
            ->add('issuingCountry', CountryType::class, [
                'attr' => [
                    'class' => 'select',
                    'placeholder' => 'Sélectionner le pays'
                ],
                'label' => 'Pays Émetteur',
                'required' => true
            ])
            ->add('expiryDate', DateType::class, [
                'attr' => [
                    'class' => 'input',
                ],
                'widget' => 'single_text',
                'label' => 'Date d\'Expiration',
                'required' => true
            ])
            ->add('frontImage', FileType::class, [
                'attr' => [
                    'accept' => 'image/jpeg,image/png'
                ],
                'label' => 'Recto du Document',
                'required' => true,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png'
                        ],
                    ])
                ]
            ])
            ->add('backImage', FileType::class, [
                'attr' => [
                    'accept' => 'image/jpeg,image/png'
                ],
                'label' => 'Verso du Document',
                'required' => true,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png'
                        ],
                    ])
                ]
            ])
            ->add('selfieImage', FileType::class, [
                'attr' => [
                    'accept' => 'image/jpeg,image/png'
                ],
                'label' => 'Selfie avec Document',
                'required' => true,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png'
                        ],
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null
        ]);
    }
}
