<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user account with interactive prompts',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Interactive User Creation');
        
        // Show a warning about production usage
        $io->caution('Be careful when creating users in production!');

        // Collect user information with nice formatting
        $io->section('User Information');

        $email = $io->ask('Email', null, function ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Invalid email address');
            }
            return $email;
        });

        $username = $io->ask('Username (min 3 characters)', null, function ($username) {
            if (strlen($username) < 3) {
                throw new \RuntimeException('Username must be at least 3 characters long');
            }
            return $username;
        });

        $password = $io->askHidden('Password (min 6 characters)', function ($password) {
            if (strlen($password) < 6) {
                throw new \RuntimeException('Password must be at least 6 characters long');
            }
            return $password;
        });

        // Confirm password
        $confirm = $io->askHidden('Confirm password', function ($confirm) use ($password) {
            if ($confirm !== $password) {
                throw new \RuntimeException('Passwords do not match!');
            }
            return $confirm;
        });

        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $role = $io->choice('Select user role', $roles, 'ROLE_USER');

        // Show summary before creation
        $io->section('Summary');
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Username', $username],
                ['Role', $role],
            ]
        );

        if (!$io->confirm('Do you want to create this user?', true)) {
            $io->warning('User creation cancelled!');
            return Command::SUCCESS;
        }

        // Show progress
        $io->section('Creating User');
        $progress = $io->createProgressBar(3);
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');

        $progress->setMessage('Creating user entity...');
        $progress->start();

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);

        $progress->advance();
        $progress->setMessage('Encoding password...');
        
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );
        $user->setRoles([$role]);

        $progress->advance();
        $progress->setMessage('Persisting to database...');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $progress->finish();
        $io->newLine(2);

        $io->success([
            'User successfully created!',
            sprintf('Email: %s', $email),
            sprintf('Username: %s', $username),
            sprintf('Role: %s', $role)
        ]);

        return Command::SUCCESS;
    }
}
