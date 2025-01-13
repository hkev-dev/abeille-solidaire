<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
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
            ->add('documentType', TextType::class, [
                'attr' => [
                    'placeholder' => 'Type de document*',
                    'class' => 'form-control'
                ],
                'label' => false,
                'required' => true
            ])
            ->add('documentNumber', TextType::class, [
                'attr' => [
                    'placeholder' => 'Numéro du document*',
                    'class' => 'form-control'
                ],
                'label' => false,
                'required' => true
            ])
            ->add('issuingCountry', CountryType::class, [
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Pays émetteur*',
                'label' => false,
                'required' => true
            ])
            ->add('expiryDate', DateType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'widget' => 'single_text',
                'label' => false,
                'required' => true
            ])
            ->add('frontImage', FileType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'label' => false,
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
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'label' => false,
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
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'label' => false,
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
            'data_class' => null,
            'csrf_protection' => true,
        ]);
    }
}
