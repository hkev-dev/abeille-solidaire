<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectReward;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ProjectRewardFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rewards = [
            [
                'project' => 'audiophile_first_smart_wireless_headphones',
                'title' => 'Early Bird Special',
                'description' => 'Get your headphones at 30% off retail price. Limited to first 200 backers.',
                'amount' => 199.99,
                'estimatedDelivery' => new \DateTime('+6 months'),
                'backerCount' => 150
            ],
            [
                'project' => 'audiophile_first_smart_wireless_headphones',
                'title' => 'Deluxe Package',
                'description' => 'Headphones + Premium Carrying Case + Extra Ear Pads + Extended Warranty',
                'amount' => 299.99,
                'estimatedDelivery' => new \DateTime('+6 months'),
                'backerCount' => 75
            ],
            [
                'project' => 'eco_friendly_fashion_collection',
                'title' => 'Basic Collection',
                'description' => 'Choose any 2 items from our sustainable collection',
                'amount' => 150.00,
                'estimatedDelivery' => new \DateTime('+3 months'),
                'backerCount' => 120
            ],
            [
                'project' => 'eco_friendly_fashion_collection',
                'title' => 'Complete Collection',
                'description' => 'Get the entire collection (5 items) at 20% off retail price',
                'amount' => 450.00,
                'estimatedDelivery' => new \DateTime('+3 months'),
                'backerCount' => 45
            ]
        ];

        foreach ($rewards as $rewardData) {
            $reward = new ProjectReward();
            $reward->setProject($this->getReference('project_' . str_replace('-', '_', $rewardData['project']), Project::class))
                ->setTitle($rewardData['title'])
                ->setDescription($rewardData['description'])
                ->setAmount($rewardData['amount'])
                ->setEstimatedDelivery($rewardData['estimatedDelivery'])
                ->setBackerCount($rewardData['backerCount']);

            $manager->persist($reward);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProjectFixtures::class];
    }
}
