<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $password = 'Pa$$w0rd!';

        // Create 50 users
        for ($i = 0; $i < 50; $i++) {
            $user = new User();
            $username = $faker->userName();
            
            $user->setUsername($username);
            $user->setEmail($username . '@mailinator.com');
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setIsVerified(true);
            
            // Randomly assign ROLE_ADMIN to some users (10% chance)
            if ($faker->boolean(10)) {
                $user->setRoles(['ROLE_ADMIN']);
            }

            $manager->persist($user);
        }

        // Create one admin user for testing
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@mailinator.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        
        $manager->persist($admin);
        $manager->flush();
    }
}
