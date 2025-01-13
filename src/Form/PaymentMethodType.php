<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Carte bancaire' => 'card',
                    'Cryptomonnaie' => 'crypto'
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'attr' => [
                    'class' => 'payment-method-selector'
                ]
            ])
            ->add('stripeToken', HiddenType::class, [
                'required' => false
            ])
            ->add('cryptoCurrency', ChoiceType::class, [
                'choices' => $options['crypto_currencies'],
                'required' => false,
                'placeholder' => 'Sélectionnez une cryptomonnaie',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('walletAddress', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Adresse du portefeuille'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'crypto_currencies' => [],
            'csrf_protection' => true,
        ]);
    }
}
