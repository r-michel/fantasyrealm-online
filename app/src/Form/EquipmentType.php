<?php

namespace App\Form;

use App\Entity\Equipment;
use App\Entity\EquipmentCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

final class EquipmentType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l’équipement',
                'attr' => [
                    'placeholder' => 'Nom de l’équipement...',
                    'autocomplete' => 'off',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir un nom.',
                    ),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => EquipmentCategory::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Catégorie',
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez sélectionner une catégorie.',
                    ),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'accept' => 'image/png',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez sélectionner une image.',
                    ),
                    new File(
                        maxSize: '3M',
                        mimeTypes: [
                            'image/png',
                        ],
                        mimeTypesMessage: 'L’image doit être au format PNG.',
                    ),
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver,
    ): void {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}
