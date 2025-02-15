<?php

namespace App\Form;

use App\Entity\MainSlider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MainSliderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Delete image',
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
                'label' => 'Image'
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
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrez la position du slide',
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
