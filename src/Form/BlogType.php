<?php

namespace App\Form;

use App\Entity\Blog;
use App\Entity\BlogCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClass = 'w-full border-4 border-black p-4 font-bold text-lg focus:outline-none focus:ring-4 focus:ring-yellow-300 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] bg-white mb-6';
        $labelClass = 'block font-black uppercase mb-2 text-lg tracking-tight';

        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'placeholder' => 'Enter a punchy title...',
                    'class' => $inputClass
                ],
                'label_attr' => ['class' => $labelClass]
            ])
            ->add('slug', TextType::class, [
                'attr' => [
                    'placeholder' => 'post-slug-example',
                    'class' => $inputClass
                ],
                'label_attr' => ['class' => $labelClass],
                'required' => false,
                'help' => 'Leave empty to auto-generate',
                'help_attr' => ['class' => 'text-sm font-bold text-gray-500 mb-6 block italic']
            ])
            ->add('category', EntityType::class, [
                'class' => BlogCategory::class,
                'choice_label' => 'title',
                'attr' => ['class' => $inputClass],
                'label_attr' => ['class' => $labelClass]
            ])
            ->add('body', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Write your masterpiece here...',
                    'class' => $inputClass . ' resize-none'
                ],
                'label_attr' => ['class' => $labelClass]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Draft' => 'draft',
                    'Published' => 'published',
                    'Archived' => 'archived',
                ],
                'attr' => ['class' => $inputClass],
                'label_attr' => ['class' => $labelClass]
            ])
            ->add('thumbnail', FileType::class, [
                'label' => 'Thumbnail Image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => $inputClass
                ],
                'label_attr' => ['class' => $labelClass],
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Please upload a valid image (JPEG, PNG, WEBP)',
                    )
                ],
            ])
            ->add('delete_thumbnail', CheckboxType::class, [
                'label' => 'Remove current thumbnail?',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'w-6 h-6 border-4 border-black text-red-500 focus:ring-0 cursor-pointer shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]'
                ],
                'label_attr' => [
                    'class' => 'font-black uppercase text-sm ml-3 cursor-pointer text-red-600'
                ],
                'row_attr' => [
                    'class' => 'flex items-center mb-4 mt-2'
                ]
            ])
            ->add('isEnable', CheckboxType::class, [
                'label' => 'Enable this post?',
                'required' => false,
                'attr' => [
                    'class' => 'w-8 h-8 border-4 border-black text-lime-400 focus:ring-0 cursor-pointer shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]'
                ],
                'label_attr' => [
                    'class' => 'font-black uppercase text-lg ml-3 cursor-pointer'
                ],
                'row_attr' => [
                    'class' => 'flex items-center mb-8'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
        ]);
    }
}
