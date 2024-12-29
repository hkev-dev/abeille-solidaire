<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\ProjectReview;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class ProjectReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $userRepository = $manager->getRepository(User::class);
        $users = $userRepository->findAll();

        // For each project (0-19 as per ProjectFixtures), create 2-5 reviews
        for ($i = 0; $i < 20; $i++) {
            $project = $this->getReference('project_' . $i, Project::class);
            $numReviews = $faker->numberBetween(2, 5);

            for ($j = 0; $j < $numReviews; $j++) {
                $review = new ProjectReview();
                $review->setProject($project)
                    ->setAuthor($faker->randomElement($users))
                    ->setComment($faker->realText(200))
                    ->setRating($faker->numberBetween(3, 5)); // Slightly biased towards positive reviews

                $manager->persist($review);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            UserFixtures::class
        ];
    }
}
