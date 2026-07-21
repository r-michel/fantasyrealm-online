<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminUserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $adminEmail,
        private readonly string $adminUsername,
        private readonly string $adminPassword,
    ) {
    }

    public static function getGroups(): array
    {
        return ['admin'];
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();

        $admin
            ->setEmail($this->adminEmail)
            ->setUsername($this->adminUsername)
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true);

        $admin->setPassword(
            $this->passwordHasher->hashPassword(
                $admin,
                $this->adminPassword,
            ),
        );

        $manager->persist($admin);
        $manager->flush();
    }
}
