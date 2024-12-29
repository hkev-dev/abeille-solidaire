<?php

namespace App\DataFixtures;

use App\Entity\EventCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventCategoryFixtures extends Fixture
{
    public const array CATEGORIES = [
        'workshop' => [
            'name' => 'Workshop',
            'icon' => 'fas fa-chalkboard-teacher'
        ],
        'crowdfunding' => [
            'name' => 'Crowdfunding',
            'icon' => 'fas fa-hand-holding-usd'
        ],
        'networking' => [
            'name' => 'Networking',
            'icon' => 'fas fa-users'
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $key => $categoryData) {
            $category = new EventCategory();
            $category->setName($categoryData['name']);
            $category->setIcon($categoryData['icon']);

            $manager->persist($category);
            $this->addReference('event_category_' . $key, $category);
        }

        $manager->flush();
    }
}
