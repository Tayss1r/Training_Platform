<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Course;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('duration')
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('photo', FileType::class, [
                'label' => 'Insert product image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/gif',
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid Image',
                    ])
                ],
            ])
            ->add('syllabus', FileType::class, [
                'label' => 'Syllabus (PDF file)',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Please upload a valid PDF file.',
                    ])
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
