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
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a date',
                    ]),
                    new GreaterThan([
                        'value' => 'today',
                        'message' => 'The date must be in the future',
                    ]),
                ],
            ])
            ->add('startTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Start Time',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a start time',
                    ]),
                ],
            ])
            ->add('endTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'End Time',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select an end time',
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