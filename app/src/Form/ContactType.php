<?php

namespace App\Form;

use App\Dto\ContactRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => [
                    'placeholder' => 'votre@email.com',
                    'autocomplete' => 'email',
                ],
            ])

            ->add('username', TextType::class, [
                'label' => 'Pseudo',
                'attr' => [
                    'placeholder' => 'Votre pseudo',
                    'autocomplete' => 'username',
                ],
            ])

            ->add('subject', TextType::class, [
                'label' => 'Objet',
                'attr' => [
                    'placeholder' => 'Ex : Problème de connexion',
                ],
            ])

            ->add('message', TextareaType::class, [
                'label' => 'Détail de la demande',
                'attr' => [
                    'placeholder' => 'Décrivez votre demande...',
                    'rows' => 6,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactRequest::class,
        ]);
    }
}
