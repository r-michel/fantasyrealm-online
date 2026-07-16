<?php

namespace App\Form;

use App\Entity\Character;
use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

final class CharacterType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du personnage',
                'attr' => [
                    'placeholder' => 'Donnez un nom à votre héros…',
                    'autocomplete' => 'off',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez donner un nom à votre personnage.',
                    ),
                    new Length(
                        min: 3,
                        max: 50,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ),
                ],
            ])

            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Masculin' => 'male',
                    'Féminin' => 'female',
                    'Non-binaire' => 'non_binary',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('skinColor', ChoiceType::class, [
                'label' => 'Teinte de peau',
                'choices' => [
                    'Clair' => 'light',
                    'Brun' => 'brown',
                    'Sombre' => 'dark',
                    'Vert' => 'green',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('hairColor', ChoiceType::class, [
                'label' => 'Couleur des cheveux',
                'choices' => [
                    'Noir' => 'black',
                    'Brun' => 'brown',
                    'Blond' => 'blond',
                    'Roux' => 'orange',
                    'Blanc' => 'white'
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('eyeColor', ChoiceType::class, [
                'label' => 'Couleur des yeux',
                'choices' => [
                    'Marron' => 'brown',
                    'Bleu' => 'blue',
                    'Vert' => 'green',
                    'Ambre' => 'amber',
                    'Violet' => 'purple',
                    'Rouge' => 'red',
                    'Jaune' => 'yellow',
                    'Gris' => 'gray',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('eyeShape', ChoiceType::class, [
                'label' => 'Forme des yeux',
                'choices' => [
                    'Ronds' => 'round',
                    'En amande' => 'almond',
                    'Bridés' => 'narrow',
                    'Grands' => 'large',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('noseShape', ChoiceType::class, [
                'label' => 'Forme du nez',
                'choices' => [
                    'Petit' => 'small',
                    'Large' => 'wide',
                    'Retroussé' => 'upturned',
                    'Droit' => 'straight',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('mouthShape', ChoiceType::class, [
                'label' => 'Forme de la bouche',
                'choices' => [
                    'Fine' => 'thin',
                    'Pulpeuse' => 'full',
                    'Souriante' => 'smiling',
                    'Neutre' => 'neutral',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('equipment', EntityType::class, [
                'class' => Equipment::class,
                'label' => 'Équipements',
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
                'by_reference' => false,

                'query_builder' => static fn (
                    EquipmentRepository $repository,
                ) => $repository
                    ->createQueryBuilder('equipment')
                    ->innerJoin('equipment.category', 'category')
                    ->addSelect('category')
                    ->andWhere('equipment.active = :active')
                    ->setParameter('active', true)
                    ->orderBy('category.name', 'ASC')
                    ->addOrderBy('equipment.name', 'ASC'
                ),

                'choice_attr' => function (Equipment $equipment): array {
                    return [
                        'data-category-id' => (string) $equipment
                            ->getCategory()
                            ?->getId(),

                        'data-category-code' => $equipment
                            ->getCategory()
                            ?->getCode() ?? '',

                        'data-category-name' => $equipment
                            ->getCategory()
                            ?->getName() ?? '',

                        'data-image-url' => $this->urlGenerator->generate(
                            'app_equipment_image',
                            [
                                'id' => $equipment->getId(),
                                'v' => $equipment->getUpdatedAt()?->getTimestamp()
                                    ?? $equipment->getCreatedAt()?->getTimestamp()
                                    ?? 1,
                            ],
                        ),
                    ];
                },
            ])
            ->add('generatedImage', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(
        OptionsResolver $resolver,
    ): void {
        $resolver->setDefaults([
            'data_class' => Character::class,
        ]);
    }
}
