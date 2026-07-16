<?php

namespace App\DataFixtures;

use App\Entity\Equipment;
use App\Entity\EquipmentCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class EquipmentFixtures extends Fixture implements FixtureGroupInterface
{
    private const CATALOGUE = [
        'clothing' => [
            'name' => 'Vêtements',
            'equipment' => [
                [
                    'name' => 'Tunique de soie',
                    'image' => 'clothing/silk-tunic.png',
                ],
                [
                    'name' => 'Cape du chasseur',
                    'image' => 'clothing/hunter-cape.png',
                ],
                [
                    'name' => 'Robe de mage',
                    'image' => 'clothing/mage-robe.png',
                ],
            ],
        ],

        'armor' => [
            'name' => 'Armures',
            'equipment' => [
                [
                    'name' => 'Armure de cuir',
                    'image' => null,
                ],
                [
                    'name' => 'Cotte de mailles',
                    'image' => null,
                ],
                [
                    'name' => 'Armure du gardien',
                    'image' => null,
                ],
            ],
        ],

        'accessory' => [
            'name' => 'Accessoires',
            'equipment' => [
                [
                    'name' => 'Amulette ancienne',
                    'image' => null,
                ],
                [
                    'name' => 'Anneau runique',
                    'image' => null,
                ],
                [
                    'name' => 'Ceinture de voyage',
                    'image' => null,
                ],
            ],
        ],

        'weapon' => [
            'name' => 'Armes',
            'equipment' => [
                [
                    'name' => 'Épée longue',
                    'image' => null,
                ],
                [
                    'name' => 'Arc sylvestre',
                    'image' => null,
                ],
                [
                    'name' => 'Bâton arcanique',
                    'image' => null,
                ],
            ],
        ],
    ];

    public static function getGroups(): array
    {
        return ['equipment'];
    }

    public function load(ObjectManager $manager): void
    {
        $categoryRepository = $manager->getRepository(
            EquipmentCategory::class,
        );

        $equipmentRepository = $manager->getRepository(
            Equipment::class,
        );

        $now = new \DateTimeImmutable();

        foreach (self::CATALOGUE as $categoryCode => $categoryData) {
            /** @var EquipmentCategory|null $category */
            $category = $categoryRepository->findOneBy([
                'code' => $categoryCode,
            ]);

            if (!$category) {
                $category = $categoryRepository->findOneBy([
                    'name' => $categoryData['name'],
                ]);
            }

            if (!$category) {
                $category = new EquipmentCategory();
                $category->setCreatedAt($now);

                $manager->persist($category);
            } else {
                $category->setUpdatedAt($now);
            }

            $category
                ->setName($categoryData['name'])
                ->setCode($categoryCode);

            foreach ($categoryData['equipment'] as $item) {
                /** @var Equipment|null $equipment */
                $equipment = $equipmentRepository->findOneBy([
                    'name' => $item['name'],
                ]);

                if (!$equipment) {
                    $equipment = new Equipment();
                    $equipment
                        ->setName($item['name'])
                        ->setCreatedAt($now);

                    $manager->persist($equipment);
                } else {
                    $equipment->setUpdatedAt($now);
                }

                $equipment
                    ->setCategory($category)
                    ->setActive(true);

                if ($item['image'] !== null) {
                    $imagePath = __DIR__
                        . '/assets/equipment/'
                        . $item['image'];

                    if (!is_file($imagePath)) {
                        throw new \RuntimeException(sprintf(
                            'Image introuvable pour "%s" : %s',
                            $item['name'],
                            $imagePath,
                        ));
                    }

                    $imageContent = file_get_contents($imagePath);

                    if ($imageContent === false) {
                        throw new \RuntimeException(sprintf(
                            'Impossible de lire l’image de "%s".',
                            $item['name'],
                        ));
                    }

                    $equipment->setImage($imageContent);
                }
            }
        }

        $manager->flush();
    }
}
