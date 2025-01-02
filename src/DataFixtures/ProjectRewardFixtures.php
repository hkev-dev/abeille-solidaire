<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectReward;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjectRewardFixtures extends Fixture implements DependentFixtureInterface
{
    private array $creators = ['john_doe', 'jane_smith', 'alice_wonder', 'bob_builder'];

    private array $rewardTiers = [
        'early_bird' => [
            'title_format' => 'Early Bird Support - %s',
            'description_format' => 'Be among the first supporters. Get exclusive updates and recognition on our project page. Limited to first %d backers.',
            'multiplier' => 0.25, // 25% of flower amount
            'limit' => [10, 20, 30]
        ],
        'standard' => [
            'title_format' => 'Regular Support - %s',
            'description_format' => 'Support our project and get regular updates on our progress.',
            'multiplier' => 0.5 // 50% of flower amount
        ],
        'premium' => [
            'title_format' => 'Premium Support - %s',
            'description_format' => 'Premium supporter status with exclusive benefits and priority updates.',
            'multiplier' => 1.0 // 100% of flower amount
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        foreach ($this->creators as $username) {
            try {
                $project = $this->getReference('project_' . $username, Project::class);
                $flowerAmount = $project->getCreator()->getCurrentFlower()->getDonationAmount();

                foreach ($this->rewardTiers as $tier => $format) {
                    $reward = new ProjectReward();
                    $limit = isset($format['limit']) ? $faker->randomElement($format['limit']) : null;

                    $reward->setProject($project)
                        ->setTitle(sprintf($format['title_format'], $project->getTitle()))
                        ->setDescription(sprintf(
                            $format['description_format'],
                            $limit ?? 0
                        ))
                        ->setAmount($flowerAmount * $format['multiplier'])
                        ->setEstimatedDelivery($faker->dateTimeBetween('+1 month', '+3 months'))
                        ->setBackerLimit($limit)
                        ->setBackerCount(0);

                    $manager->persist($reward);
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
        return [ProjectFixtures::class];
    }
}
