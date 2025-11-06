<?php

namespace App\Form;

use App\Entity\Item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('itemName', TextType::class, [
                'label' => 'Item Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'attr' => ['class' => 'form-control']
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantity',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
        'required' => true,
        'label' => 'Description',
    ])
            ->add('imageFile', FileType::class, [
                'label' => 'Upload Image',
                'mapped' => false, // ❗ not linked directly to Entity
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
        ]);
    }
}

