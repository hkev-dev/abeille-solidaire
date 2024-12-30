<?php

namespace App\DataFixtures;

use App\Entity\Flower;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private const array STATIC_USERS = [
        [
            'email' => 'john.doe@example.com',
            'username' => 'john_doe',
            'name' => 'John Doe',
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ],
        [
            'email' => 'jane.smith@example.com',
            'username' => 'jane_smith',
            'name' => 'Jane Smith',
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ],
        [
            'email' => 'alice.wonder@example.com',
            'username' => 'alice_wonder',
            'name' => 'Alice Wonder',
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ],
        [
            'email' => 'bob.builder@example.com',
            'username' => 'bob_builder',
            'name' => 'Bob Builder',
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ],
        [
            'email' => 'admin@example.com',
            'username' => 'admin',
            'name' => 'Admin User',
            'password' => 'admin123',
            'roles' => ['ROLE_ADMIN']
        ]
    ];

    private ?User $firstUser = null;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Load static users first
        foreach (self::STATIC_USERS as $userData) {
            $this->createUser($manager, $userData);
        }

        // Generate 20 random users
        for ($i = 1; $i <= 20; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $username = strtolower($firstName . '.' . $lastName);

            $userData = [
                'email' => $faker->email(),
                'username' => str_replace('.', '_', $username),
                'name' => $firstName . ' ' . $lastName,
                'password' => 'password123',
                'roles' => ['ROLE_USER']
            ];

            $this->createUser($manager, $userData);
        }

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, array $userData): void
    {
        $user = new User();
        $user->setEmail($userData['email'])
            ->setUsername($userData['username'])
            ->setName($userData['name'])
            ->setRoles($userData['roles'])
            ->setIsVerified(true)
            ->setWalletBalance(0.0)
            ->setCurrentFlower($this->getReference('flower_1', Flower::class));

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $userData['password']
        );
        $user->setPassword($hashedPassword);

        // Store first user for referral
        if ($this->firstUser === null && !in_array('ROLE_ADMIN', $userData['roles'])) {
            $this->firstUser = $user;
        }
        // Set referrer for non-admin users after first user
        elseif (!in_array('ROLE_ADMIN', $userData['roles']) && $this->firstUser !== null) {
            $user->setReferrer($this->firstUser);
        }

        $manager->persist($user);
        $this->addReference('user_' . $userData['username'], $user);
    }

    public function getDependencies(): array
    {
        return [
            FlowerFixtures::class,
        ];
    }
}
