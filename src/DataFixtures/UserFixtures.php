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
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
            'projectDescription' => 'Building a sustainable farming community in rural areas.'
        ],
        [
            'email' => 'jane.smith@example.com',
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
            'projectDescription' => 'Creating an educational program for underprivileged children.'
        ],
        [
            'email' => 'alice.wonder@example.com',
            'firstName' => 'Alice',
            'lastName' => 'Wonder',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
            'projectDescription' => 'Developing a renewable energy initiative for local communities.'
        ],
        [
            'email' => 'bob.builder@example.com',
            'firstName' => 'Bob',
            'lastName' => 'Builder',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
            'projectDescription' => 'Building affordable housing for low-income families.'
        ],
        [
            'email' => 'admin@example.com',
            'firstName' => 'Admin',
            'lastName' => 'User',
            'password' => 'admin123',
            'roles' => ['ROLE_ADMIN'],
            'projectDescription' => 'Platform administration and support.'
        ]
    ];

    private ?User $firstUser = null;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

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

            $userData = [
                'email' => $faker->email(),
                'firstName' => $firstName,
                'lastName' => $lastName,
                'password' => 'password123',
                'roles' => ['ROLE_USER'],
                'projectDescription' => $faker->paragraphs(2, true)
            ];

            $this->createUser($manager, $userData);
        }

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, array $userData): void
    {
        $user = new User();
        $user->setEmail($userData['email'])
            ->setFirstName($userData['firstName'])
            ->setLastName($userData['lastName'])
            ->setName($userData['firstName'] . ' ' . $userData['lastName']) // Set the name field
            ->setRoles($userData['roles'])
            ->setIsVerified(true)
            ->setWalletBalance(0.0)
            ->setCurrentFlower($this->getReference('flower_1', Flower::class))
            ->setProjectDescription($userData['projectDescription'])
            ->setReferralCode($this->generateReferralCode())
            ->setRegistrationPaymentStatus('completed') // Set as completed for fixtures
            ->setWaitingSince(null); // No waiting time for fixtures

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
        
        // Create a username from firstName and lastName for reference
        $username = strtolower(str_replace(' ', '_', 
            $userData['firstName'] . '_' . $userData['lastName']
        ));
        $this->addReference('user_' . $username, $user);
        
        // Also store by email for backward compatibility
        $this->addReference('user_by_email_' . $userData['email'], $user);
    }

    private function generateReferralCode(): string
    {
        return bin2hex(random_bytes(16)); // Generates a 32-character hex string
    }

    public function getDependencies(): array
    {
        return [
            FlowerFixtures::class,
        ];
    }
}
