<?php

namespace App\Form;

use App\Entity\BlogCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlogCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Category Title',
                'attr' => [
                    'placeholder' => 'Enter category title...',
                    'class' => 'w-full border-4 border-black p-3 font-bold text-lg focus:outline-none focus:ring-4 focus:ring-yellow-300 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] bg-white mb-4'
                ],
                'label_attr' => [
                    'class' => 'block font-black uppercase mb-2 text-lg'
                ]
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'attr' => [
                    'placeholder' => 'category-slug-example',
                    'class' => 'w-full border-4 border-black p-3 font-bold text-lg focus:outline-none focus:ring-4 focus:ring-yellow-300 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] bg-white mb-4'
                ],
                'label_attr' => [
                    'class' => 'block font-black uppercase mb-2 text-lg'
                ],
                'help' => 'Leave empty to generate from title automatically (if handled in entity/JS)',
                'help_attr' => [
                    'class' => 'text-sm font-bold text-gray-600 mb-6 block italic'
                ],
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogCategory::class,
        ]);
    }
}
