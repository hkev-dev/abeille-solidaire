<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType; 
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class PaymentMethodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Carte bancaire' => 'card',
                    'Cryptomonnaie' => 'crypto'
                ],
                'choice_attr' => function($choice, $key, $value) {
                    $attrs = [
                        'card' => [
                            'icon' => 'ki-duotone ki-credit-cart',
                            'description' => 'Paiement sécurisé par carte bancaire'
                        ],
                        'crypto' => [
                            'icon' => 'ki-duotone ki-bitcoin',
                            'description' => 'Paiement en cryptomonnaie via CoinPayments'
                        ]
                    ];

                    return [
                        'data-icon' => $attrs[$choice]['icon'], 
                        'data-description' => $attrs[$choice]['description']
                    ];
                }
            ])
            ->add('stripeToken', HiddenType::class, [
                'required' => false
            ])
            ->add('cryptoCurrency', ChoiceType::class, [
                'label' => 'Cryptomonnaie',
                'required' => false,
                'placeholder' => 'Sélectionnez une cryptomonnaie',
                'choices' => array_combine($options['crypto_currencies'], $options['crypto_currencies']),
                'choice_attr' => function($choice, $key, $value) {
                    return [
                        'data-icon' => 'ki-duotone ki-'.strtolower($choice),
                        'data-symbol' => strtoupper($choice)
                    ];
                },
                'attr' => [
                    'class' => 'select',
                    'data-control' => 'select2',
                    'data-placeholder' => 'Sélectionnez une cryptomonnaie'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une cryptomonnaie',
                        'groups' => ['crypto']
                    ])
                ]
            ])
            ->add('walletAddress', TextType::class, [
                'label' => 'Adresse du portefeuille',
                'required' => false,
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Ex: 0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
                    'maxlength' => 64,
                    'data-validation' => 'crypto-address'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir une adresse de portefeuille',
                        'groups' => ['crypto']
                    ]),
                    new Length([
                        'min' => 26,
                        'max' => 64,
                        'minMessage' => 'L\'adresse doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères',
                        'groups' => ['crypto']
                    ]),
                    new Regex([
                        'pattern' => '/^(0x)?[0-9a-fA-F]{40}$/',
                        'message' => 'L\'adresse n\'est pas valide',
                        'groups' => ['crypto']
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'crypto_currencies' => [],
            'csrf_protection' => true,
            'validation_groups' => function($form) {
                $data = $form->getData();
                return $data['type'] === 'crypto' ? ['Default', 'crypto'] : ['Default'];
            }
        ]);
    }
}
