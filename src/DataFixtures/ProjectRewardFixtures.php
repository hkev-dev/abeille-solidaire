<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectReward;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class ProjectRewardFixtures extends Fixture implements DependentFixtureInterface
{
    private array $rewardTiers = [
        'early_bird' => [
            'title_format' => 'Early Bird Special - %s',
            'description_format' => 'Be among the first to get %s at %d%% off retail price. Limited to first %d backers.',
            'discount' => [20, 30, 40],
            'limit' => [50, 100, 200]
        ],
        'standard' => [
            'title_format' => 'Standard Package - %s',
            'description_format' => 'Get %s at regular crowdfunding price.',
            'discount' => [10, 15, 20]
        ],
        'deluxe' => [
            'title_format' => 'Deluxe Package - %s',
            'description_format' => '%s + Premium Accessories + Extended Warranty',
            'discount' => [0, 5, 10]
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // For each project (0-19 as per ProjectFixtures)
        for ($i = 0; $i < 20; $i++) {
            $project = $this->getReference('project_' . $i, Project::class);
            $basePrice = $faker->numberBetween(50, 500);

            foreach ($this->rewardTiers as $tier => $format) {
                $reward = new ProjectReward();
                $discount = $faker->randomElement($format['discount']);
                $limit = $format['limit'] ?? null ? $faker->randomElement($format['limit']) : null;

                $title = sprintf($format['title_format'], $project->getTitle());
                $description = sprintf(
                    $format['description_format'],
                    $project->getTitle(),
                    $discount,
                    $limit ?? 0
                );

                // Calculate price based on tier and discount
                $price = $basePrice;
                if ($tier === 'deluxe') {
                    $price *= 2; // Deluxe is twice the base price
                }
                $price = $price * (100 - $discount) / 100;

                $reward->setProject($project)
                    ->setTitle($title)
                    ->setDescription($description)
                    ->setAmount($price)
                    ->setEstimatedDelivery($faker->dateTimeBetween('+3 months', '+1 year'))
                    ->setBackerCount($faker->numberBetween(0, $limit ?? 100));

                $manager->persist($reward);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProjectFixtures::class];
    }
}
