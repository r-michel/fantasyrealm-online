<?php

namespace App\Form;

use App\Document\ApplicationSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ApplicationSettingsType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('announcementEnabled', CheckboxType::class, [
                'label' => 'Afficher le bandeau d’annonce',
                'required' => false,
            ])
            ->add('announcementMessage', TextareaType::class, [
                'label' => 'Message de l’annonce',
                'required' => false,
                'constraints' => [
                    new Length(max: 500),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer les paramètres',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApplicationSettings::class,
        ]);
    }
}
