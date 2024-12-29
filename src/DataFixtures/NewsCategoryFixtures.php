<?php

namespace App\DataFixtures;

use App\Entity\NewsCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NewsCategoryFixtures extends Fixture
{
    private const array CATEGORIES = [
        'Crowdfunding News',
        'Success Stories',
        'Platform Updates',
        'Community',
        'Tips & Guides'
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $index => $name) {
            $category = new NewsCategory();
            $category->setName($name);

            $manager->persist($category);
            $this->addReference('news_category_' . $index, $category);
        }

        $manager->flush();
    }
}
