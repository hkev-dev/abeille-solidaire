<?php

namespace App\Form;

use App\DTO\RegistrationDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'First Name*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Last Name*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('username', TextType::class, [
                'attr' => [
                    'placeholder' => 'Username*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'placeholder' => 'Email Address*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('password', PasswordType::class, [
                'attr' => [
                    'placeholder' => 'Password*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('confirmPassword', PasswordType::class, [
                'attr' => [
                    'placeholder' => 'Confirm Password*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box']
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'class' => 'custom-control-input'
                ],
                'row_attr' => ['class' => 'login-register__checkbox']
            ])
            ->add('referralCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'Referral Code*'
                ],
                'label' => false,
                'row_attr' => ['class' => 'contact-form__input-box'],
                'disabled' => !empty($options['referral_code'])
            ])
            ->add('projectDescription', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Describe your project or initiative (minimum 100 characters)*',
                    'rows' => 6,
                    'class' => 'h-auto'
                ],
                'label' => false,
                'help' => 'Tell us about your project, its goals, and how you plan to use the funds.',
                'row_attr' => ['class' => 'contact-form__input-box']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationDTO::class,
            'referral_code' => null,
        ]);
    }
}
