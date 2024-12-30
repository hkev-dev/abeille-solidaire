<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectReview;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjectReviewFixtures extends Fixture implements DependentFixtureInterface
{
    private array $creators = ['john_doe', 'jane_smith', 'alice_wonder', 'bob_builder'];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $userRepository = $manager->getRepository(User::class);
        $users = $userRepository->findAll();

        // Create reviews for each creator's project
        foreach ($this->creators as $username) {
            try {
                $project = $this->getReference('project_' . $username, Project::class);
                $numReviews = $faker->numberBetween(2, 5);

                for ($j = 0; $j < $numReviews; $j++) {
                    $reviewer = $faker->randomElement($users);

                    // Skip if reviewer is the project creator
                    if ($reviewer === $project->getCreator()) {
                        continue;
                    }

                    $review = new ProjectReview();
                    $review->setProject($project)
                        ->setAuthor($reviewer)
                        ->setComment($faker->realText())
                        ->setRating($faker->numberBetween(3, 5)); // Slightly biased towards positive reviews

                    $manager->persist($review);
                }
            } catch (\Exception $e) {
                // Log or handle the case where project reference is not found
                continue;
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
