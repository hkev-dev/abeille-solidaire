<?php

namespace App\Form;

use App\Entity\MainSlider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MainSliderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image du slide',
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
            ->add('subtitle', TextType::class,[
                'label' => 'Sous-titre',
                'attr' => [
                    'placeholder' => 'Entrez le sous-titre',
                    'class' => 'input'
                ]
            ])
            ->add('title', TextType::class,[
                'label' => 'Titre du slide',
                'attr' => [
                    'placeholder' => 'Entrez le titre du slide',
                    'maxlength' => 255,
                    'class' => 'input'
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
            ->add('position', IntegerType::class,[
                'label' => 'Position du slide',
                'attr' => [
                    'placeholder' => 'Entrez la position du slide',
                    'maxlength' => 255,
                    'class' => 'input'
                ]
            ])
            ->add('isActive', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
                'expanded' => false,
                'multiple' => false,
                'attr' => [
                    'placeholder' => 'Selectionner un status',
                    'maxlength' => 255,
                    'class' => 'select'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MainSlider::class,
        ]);
    }
}
