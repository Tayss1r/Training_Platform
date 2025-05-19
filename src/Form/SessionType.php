<?php

namespace App\Form;

use App\Entity\Session;
use App\Entity\Course;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'title',
                'label' => 'Course',
                'placeholder' => 'Select a course',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a course',
                    ]),
                ],
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a start date',
                    ]),
                    new GreaterThan([
                        'value' => 'today',
                        'message' => 'The start date must be in the future',
                    ]),
                ],
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'End Date',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select an end date',
                    ]),
                ],
            ])
            ->add('time', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Time',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a time',
                    ]),
                ],
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacity',
                'required' => true,
                'attr' => [
                    'min' => 1,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a capacity',
                    ]),
                    new Positive([
                        'message' => 'Capacity must be a positive number',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
}
