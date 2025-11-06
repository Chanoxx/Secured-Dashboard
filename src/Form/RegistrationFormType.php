<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Regex;



class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
$builder
    ->add('firstName', TextType::class, [
        'label' => 'First Name',
    ])
    ->add('lastName', TextType::class, [
        'label' => 'Last Name',
    ])
    ->add('contactNumber', TextType::class, [
    'label' => 'Contact Number',
    'required' => true,
    'constraints' => [
        new NotBlank(['message' => 'Please enter your contact number']),
        new Regex([
            'pattern' => '/^(09|\+639)\d{9}$/',
            'message' => 'Contact number must start with 09 or +63 and contain 11 digits total.',
        ]),
    ],
])

    ->add('address', TextType::class, [
        'label' => 'Address',
        'required' => false,
    ])
    ->add('email', EmailType::class)
    ->add('plainPassword', PasswordType::class, [
        'mapped' => false,
        'attr' => ['autocomplete' => 'new-password'],
        'constraints' => [
            new NotBlank(['message' => 'Please enter a password']),
            new Length([
                'min' => 6,
                'minMessage' => 'Your password should be at least {{ limit }} characters',
                'max' => 4096,
            ]),
        ],
    ]);

    }
    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
