<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaymentSelectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('payment_method', ChoiceType::class, [
                'choices' => [
                    'Credit Card' => 'stripe',
                    'Cryptocurrency' => 'crypto'
                ],
                'expanded' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please select a payment method']),
                    new Choice([
                        'choices' => ['stripe', 'crypto'],
                        'message' => 'Invalid payment method selected'
                    ])
                ],
                'attr' => ['class' => 'payment-method-selection']
            ])
            ->add('csrf_token', HiddenType::class, [
                'mapped' => false,
                'data' => 'payment_selection'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'payment_selection',
            'allow_extra_fields' => true
        ]);
    }
}
