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
        'Vêtements' => [
            'Tunique de soie',
            'Cape du chasseur',
            'Robe de mage',
        ],
        'Armures' => [
            'Armure de cuir',
            'Cotte de mailles',
            'Armure du gardien',
        ],
        'Accessoires' => [
            'Amulette ancienne',
            'Anneau runique',
            'Ceinture de voyage',
        ],
        'Armes' => [
            'Épée longue',
            'Arc sylvestre',
            'Bâton arcanique',
        ],
    ];

    public static function getGroups(): array
    {
        return ['equipment'];
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        foreach (self::CATALOGUE as $categoryName => $equipmentNames) {
            $category = new EquipmentCategory();
            $category
                ->setName($categoryName)
                ->setCreatedAt($now);

            $manager->persist($category);

            foreach ($equipmentNames as $equipmentName) {
                $equipment = new Equipment();
                $equipment
                    ->setName($equipmentName)
                    ->setCategory($category)
                    ->setActive(true)
                    ->setCreatedAt($now);

                $manager->persist($equipment);
            }
        }

        $manager->flush();
    }
}
