<?php

namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /*
            ->add('title')
            ->add('creator')
            ->add('contributor')
            ->add('coverage')
            ->add('date')
            ->add('description')
            ->add('subject')
            ->add('type')
            ->add('format')
            ->add('identifier')
            ->add('language')
            ->add('publisher')
            ->add('relation')
            ->add('rights')
            ->add('source')
            ->add('createdAt')
            ->add('project')
            ->add('owner')
            */
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'rows' => 25
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
