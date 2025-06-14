<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Tytuł zadania',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Opis zadania',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Oczekuje' => Task::PENDING,
                    'W trakcie' => Task::IN_PROGRESS,
                    'Ukończone' => Task::COMPLETED,
                    'Odrzucone' => Task::REJECTED,
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('estimatedHours', IntegerType::class, [
                'label' => 'Szacowane godziny',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => '1'
                ]
            ])
            ->add('actualHours', IntegerType::class, [
                'label' => 'Rzeczywiste godziny',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => '1'
                ]
            ])
            ->add('assignedUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Przypisz do użytkownika',
                'required' => false,
                'placeholder' => 'Wybierz użytkownika',
                'attr' => ['class' => 'form-control'],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.email', 'ASC');
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}