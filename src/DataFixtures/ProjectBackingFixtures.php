<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectBacking;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjectBackingFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $projects = $manager->getRepository(Project::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        foreach ($projects as $project) {
            // Generate 5-15 backings per project
            $numBackings = $faker->numberBetween(5, 15);

            for ($i = 0; $i < $numBackings; $i++) {
                $backing = new ProjectBacking();
                $backing->setProject($project)
                    ->setBacker($faker->randomElement($users))
                    ->setAmount($faker->randomFloat(2, 10, 1000))
                    ->setComment($faker->boolean(70) ? $faker->sentence() : null)
                    ->setIsAnonymous($faker->boolean(20));

                $manager->persist($backing);

                // Update project pledged amount and backers count
                $project->setPledged($project->getPledged() + $backing->getAmount());
                $project->setBackers($project->getBackers() + 1);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
