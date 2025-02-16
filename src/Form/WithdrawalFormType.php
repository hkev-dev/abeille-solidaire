<?php

namespace App\Form;

use App\Entity\PaymentMethod;
use App\Entity\Withdrawal;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
            ->add('withdrawalMethod', EntityType::class, [
                'label' => 'Méthode de retrait',
                'class' => PaymentMethod::class,
                'choices' => $options['payment_methods'], // Injecté dynamiquement
                'choice_label' => function (PaymentMethod $method) {
                    if ($method->getMethodType() === 'rib') {
                        return sprintf('Virement bancaire (IBAN: %s, BIC: %s)',
                            $method->getRibIban(),
                            $method->getRibBic()
                        );
                    } elseif ($method->getMethodType() === 'crypto') {
                        return sprintf('Crypto (%s: %s)',
                            $method->getCryptoCurrency(),
                            substr($method->getCryptoAddress(), 0, 16) . '...'
                        );
                    }
                    return 'Méthode inconnue';
                },
                'expanded' => false,
                'multiple' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Withdrawal::class,
            'payment_methods' => [],
        ]);
    }
}
