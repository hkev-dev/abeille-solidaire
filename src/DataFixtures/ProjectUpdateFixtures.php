<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectUpdate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjectUpdateFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $projects = $manager->getRepository(Project::class)->findAll();

        foreach ($projects as $project) {
            // Generate 3-6 updates per project
            $numUpdates = $faker->numberBetween(3, 6);

            for ($i = 0; $i < $numUpdates; $i++) {
                $update = new ProjectUpdate();
                $update->setTitle($faker->sentence())
                    ->setContent($faker->paragraphs(2, true))
                    ->setProject($project);

                // Set creation date between project creation and now
                // Convert DateTime to DateTimeImmutable
                $createdAt = \DateTimeImmutable::createFromMutable(
                    $faker->dateTimeBetween($project->getCreatedAt()->format('Y-m-d H:i:s'), 'now')
                );
                $update->setCreatedAt($createdAt);

                $manager->persist($update);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
        ];
    }
}
