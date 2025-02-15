<?php

namespace App\Form;

use App\Entity\FAQ;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FaqType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, [
                'label' => 'Question',
                'attr' => [
                    'placeholder' => 'Entrez la question',
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La reponse est requis']),
                ]
            ])
            ->add('answer', TextareaType::class, [
                'label' => 'Réponse',
                'attr' => [
                    'placeholder' => 'Entrez la reponse',
                    'class' => 'textarea',
                    'rows' => 6
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La reponse est requis']),
                ]
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position du slide',
                'attr' => [
                    'placeholder' => 'Entrez la position',
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
            'data_class' => FAQ::class,
        ]);
    }
}
