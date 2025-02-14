<?php

namespace App\Form;

use App\Entity\Withdrawal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class WithdrawalFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => false,
                'divisor' => 1,
                'grouping' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'min' => Withdrawal::MIN_AMOUNT,
                        'max' => Withdrawal::MAX_AMOUNT,
                    ]),
                ],
            ])
            ->add('withdrawalMethod', ChoiceType::class, [
                'label' => 'Méthode de retrait',
                'choices' => [
                    'Virement bancaire' => Withdrawal::METHOD_STRIPE,
                    'Crypto-monnaie' => Withdrawal::METHOD_CRYPTO,
                ],
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('cryptoAddress', TextType::class, [
                'label' => 'Adresse crypto',
                'required' => false,
                'constraints' => [
                    new Assert\When([
                        'expression' => 'this.getParent().get("withdrawalMethod").getData() === "crypto"',
                        'constraints' => [
                            new Assert\NotBlank(),
                            new Assert\Length(['min' => 26, 'max' => 100]),
                        ],
                    ]),
                ],
            ])
            ->add('cryptoCurrency', ChoiceType::class, [
                'label' => 'Crypto-monnaie',
                'required' => false,
                'choices' => $options['crypto_currencies'],
                'constraints' => [
                    new Assert\When([
                        'expression' => 'this.getParent().get("withdrawalMethod").getData() === "crypto"',
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Withdrawal::class,
            'crypto_currencies' => [],
        ]);
    }
}
