<?php

namespace App\Form;

use App\Entity\Productss;       
use App\Entity\Category; 
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ProductssType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'required' => true,
                
            ])
            ->add('quantity', IntegerType::class, [
    'label' => 'Quantity in stock',
    'attr' => ['min' => 0],
])

            ->add('imagefilename', FileType::class, [
                'label' => 'Product Image (JPEG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new FileConstraint([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPEG or PNG image',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Productss::class,  // THIS MUST BE Productss::class NOT Category::class
        ]);
    }
}