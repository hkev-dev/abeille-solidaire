<?php

namespace App\Form;

use App\Entity\Testimonial;
use App\Entity\Withdrawal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichImageType;

class TestimonialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Entrez le nom',
                    'maxlength' => 255,
                    'data-control' => 'input'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis']),
                    new Length([
                        'min' => 4,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Message',
                    'data-control' => 'textarea'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le message est requis']),
                    new Length([
                        'min' => 50,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('position', TextType::class, [
                'label' => 'Position',
                'attr' => [
                    'placeholder' => 'Entrez la position',
                    'maxlength' => 255,
                    'data-control' => 'input'
                ],
                'required' => false,
            ])
            ->add('rating', NumberType::class, [
                'label' => 'Note',
                'attr' => [
                    'placeholder' => 'Entrez la note',
                    'maxlength' => 255,
                    'data-control' => 'input'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La note est requise']),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 5,
                    ]),
                ]
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image ',
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
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png'
                        ],
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Testimonial::class,
        ]);
    }
}