<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\ProjectCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du projet',
                'attr' => [
                    'placeholder' => 'Entrez le titre de votre projet',
                    'maxlength' => 255,
                    'data-control' => 'input'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est requis']),
                    new Length([
                        'min' => 10,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Décrivez votre projet en détail',
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
            ->add('goal', MoneyType::class, [
                'label' => 'Objectif',
                'currency' => false,  // Disable built-in currency symbol
                'attr' => [
                    'placeholder' => '0.00',
                    'min' => '0',
                    'data-control' => 'input'
                ],
                'divisor' => 1,
                'grouping' => true,
                'constraints' => [
                    new NotBlank(['message' => 'L\'objectif est requis']),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'L\'objectif doit être supérieur à 0'
                    ])
                ]
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable'
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'input' => 'datetime_immutable'
            ])
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => ProjectCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisissez une catégorie'
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image du projet',
                'required' => true,
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
            'data_class' => Project::class,
        ]);
    }
}