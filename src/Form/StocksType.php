<?php

namespace App\Form;

use App\Entity\Productss;
use App\Entity\Stocks;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StocksType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantityChange', null, [
                'required' => false,
                'label' => 'Quantity Change',
            ])
            ->add('productss', EntityType::class, [
                'class' => Productss::class,
                'choice_label' => 'name', // use 'name' so users see product names, not IDs
                'label' => 'Product',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stocks::class,
        ]);
    }
}
