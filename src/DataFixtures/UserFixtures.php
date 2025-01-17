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
        'username' => 'root',
        'firstName' => 'Root',
        'lastName' => 'User',
        'password' => 'rootpass123',
        'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
        'projectDescription' => 'Root user for system administration and testing.',
        'phone' => '+33600000000',
        'country' => 'FR',
        'accountType' => 'PRIVATE',
        'isRoot' => true
    ];

    private const array STATIC_USERS = [
        [
            'email' => 'admin@example.com',
            'username' => 'admin',
            'firstName' => 'Admin',
            'lastName' => 'User',
            'password' => 'admin123',
            'roles' => ['ROLE_ADMIN'],
            'projectDescription' => 'Platform administration and support.',
            'phone' => '+33600000001',
            'country' => 'FR',
            'accountType' => 'PRIVATE'
        ],
        [
            'email' => 'john.doe@example.com',
            'username' => 'john_doe',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
            'projectDescription' => 'Building a sustainable farming community in rural areas.',
            'phone' => '+33600000002',
            'country' => 'FR',
            'accountType' => 'ENTERPRISE',
            'organizationName' => 'Sustainable Farms Inc.',
            'organizationNumber' => '12345678900001'
        ],
        [
            'email' => 'jane.smith@example.com',
            'username' => 'jane_smith',
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
            'projectDescription' => 'Creating an educational program for underprivileged children.',
            'phone' => '+33600000003',
            'country' => 'FR',
            'accountType' => 'PRIVATE'
        ],
        [
            'email' => 'alice.wonder@example.com',
            'username' => 'alice_wonder',
            'firstName' => 'Alice',
            'lastName' => 'Wonder',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
            'projectDescription' => 'Developing a renewable energy initiative for local communities.',
            'phone' => '+33600000004',
            'country' => 'FR',
            'accountType' => 'PRIVATE'
        ],
        [
            'email' => 'bob.builder@example.com',
            'username' => 'bob_builder',
            'firstName' => 'Bob',
            'lastName' => 'Builder',
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
            'projectDescription' => 'Building affordable housing for low-income families.',
            'phone' => '+33600000005',
            'country' => 'FR',
            'accountType' => 'PRIVATE'
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
        $this->rootUser = $this->createUser($manager, self::ROOT_USER, 'ABEILLESOLIDAIRE');

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
                'username' => $faker->userName(),
                'firstName' => $firstName,
                'lastName' => $lastName,
                'password' => 'password123',
                'roles' => ['ROLE_USER'],
                'projectDescription' => $faker->paragraphs(2, true),
                'phone' => '+33' . random_int(600000000, 799999999),
                'country' => 'FR',
                'accountType' => 'PRIVATE'
            ];

            $this->createUser($manager, $userData);
        }

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, array $userData, ?string $forcedReferralCode = null): User
    {
        $user = new User();
        $user->setEmail($userData['email'])
            ->setUsername($userData['username'])
            ->setFirstName($userData['firstName'])
            ->setLastName($userData['lastName'])
            ->setRoles($userData['roles'])
            ->setPhone($userData['phone'] ?? '+33' . random_int(600000000, 799999999))
            ->setCountry($userData['country'] ?? 'FR')
            ->setAccountType($userData['accountType'] ?? User::ACCOUNT_TYPE_PRIVATE)
            ->setIsVerified(true)
            ->setWalletBalance(0.0)
            ->setCurrentFlower($this->getReference('flower_1', Flower::class))
            ->setProjectDescription($userData['projectDescription'])
            ->setRegistrationPaymentStatus('completed')
            ->setWaitingSince(null);

        // Handle organization fields for ENTERPRISE and ASSOCIATION account types
        if (in_array($user->getAccountType(), [User::ACCOUNT_TYPE_ENTERPRISE, User::ACCOUNT_TYPE_ASSOCIATION])) {
            $user->setOrganizationName($userData['organizationName'] ?? null);
            $user->setOrganizationNumber($userData['organizationNumber'] ?? null);
        }

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
