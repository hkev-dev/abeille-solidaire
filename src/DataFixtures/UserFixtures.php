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
    private const ROOT_USER = [
        'email' => 'root@example.com',
        'firstName' => 'Root',
        'lastName' => 'User',
        'password' => 'rootpass123',
        'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
        'projectDescription' => 'Root user for system administration and testing.',
        'isRoot' => true
    ];

    private const array STATIC_USERS = [
        [
            'email' => 'admin@example.com',
            'firstName' => 'Admin',
            'lastName' => 'User',
            'password' => 'admin123',
            'roles' => ['ROLE_ADMIN'],
            'projectDescription' => 'Platform administration and support.'
        ],
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
        ]
    ];

    private ?User $rootUser = null;
    private ?User $firstUser = null;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Create root user first
        $this->rootUser = $this->createUser($manager, self::ROOT_USER, 'ROOT_USER_REF');
        
        // Load remaining static users
        foreach (self::STATIC_USERS as $userData) {
            $this->createUser($manager, $userData);
        }

        // Generate 20 random users with root user as referrer
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

    private function createUser(ObjectManager $manager, array $userData, ?string $forcedReferralCode = null): User
    {
        $user = new User();
        $user->setEmail($userData['email'])
            ->setFirstName($userData['firstName'])
            ->setLastName($userData['lastName'])
            ->setRoles($userData['roles'])
            ->setIsVerified(true)
            ->setWalletBalance(0.0)
            ->setCurrentFlower($this->getReference('flower_1', Flower::class))
            ->setProjectDescription($userData['projectDescription'])
            ->setRegistrationPaymentStatus('completed')
            ->setWaitingSince(null);

        // Set referral code
        if ($forcedReferralCode) {
            $user->setReferralCode($forcedReferralCode);
        } else {
            $user->setReferralCode($this->generateReferralCode());
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $userData['password']
        );
        $user->setPassword($hashedPassword);

        // Set referrer for non-root users
        if (!isset($userData['isRoot']) && $this->rootUser !== null) {
            $user->setReferrer($this->rootUser);
        }

        $manager->persist($user);

        // Store references
        $username = strtolower(str_replace(' ', '_', $userData['firstName'] . '_' . $userData['lastName']));
        $this->addReference('user_' . $username, $user);
        $this->addReference('user_by_email_' . $userData['email'], $user);

        return $user;
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
