<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

class PDonationPaymentSelectionType extends AbstractType
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
            ]);

        $builder
            ->add('amount', MoneyType::class, [
                'currency' => '', // Change selon la devise souhaitée
                'required' => true,
                'label' => 'Montant du don',
                'attr' => [
                    'placeholder' => 'Entrez le montant',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un montant']),
                ]
        ]);
        if (true) {
            $builder->add('annonymous', CheckboxType::class, [
                'required' => false,
                'label' => 'Donation annonyme',
                'label_attr' => ['class' => 'form-check-label'],
                /*'attr' => [
                    'class' => 'form-check-input',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'right',
                    'title' => 'L\'adhésion annuelle est obligatoire pour les retraits et certaines fonctionnalités. Vous pouvez la payer maintenant ou plus tard.'
                ],*/
                'row_attr' => ['class' => 'form-check annual-membership-option mb-4'],
                'help' => 'Faire un don annonyme',
                'help_attr' => ['class' => 'form-text text-muted'],
                'mapped' => false
            ]);
        }

        $builder->add('csrf_token', HiddenType::class, [
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
            'allow_extra_fields' => true,
            'show_annual_membership' => true
        ]);
    }
}
