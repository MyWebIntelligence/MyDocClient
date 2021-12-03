<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LexiconType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('minCount', IntegerType::class, [
                'label' => 'Occurrences mini',
                'data' => 100,
                'empty_data' => 100,
                'attr' => [
                    'min' => 1,
                ]
            ])
            ->add('sort', ChoiceType::class, [
                'label' => 'Tri',
                'data' => 'word',
                'empty_data' => 'word',
                'choices' => [
                    'Mot' => 'word',
                    'Occurrences' => 'count',
                ]
            ])
            ->add('limit', IntegerType::class, [
                'label' => 'Limite résultats',
                'data' => 100,
                'empty_data' => 100,
                'attr' => [
                    'min' => 1,
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
