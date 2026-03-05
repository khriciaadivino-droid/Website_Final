<?php

namespace App\Form;

use App\Entity\PetOwners;
use App\Entity\PetProfileManagement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PetProfileManagementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('owner', EntityType::class, [
                'class' => PetOwners::class,
                'choice_label' => 'fullName',
                'placeholder' => 'Select Pet Owner',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('Species', ChoiceType::class, [
                'choices' => [
                    'Dog' => 'Dog',
                    'Cat' => 'Cat',
                ],
                'placeholder' => 'Choose a species',
            ])
            ->add('Breed')
            ->add('Age', NumberType::class, [
                'label' => 'Age',
                'html5' => true,
                'attr' => ['min' => 0, 'step' => 0.1],
            ])
            ->add('dateofbirth', DateType::class, [
                'label' => 'Date of Birth',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'type' => 'date',
                    'max' => (new \DateTime())->format('Y-m-d')
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Pet Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PetProfileManagement::class,
        ]);
    }
}
