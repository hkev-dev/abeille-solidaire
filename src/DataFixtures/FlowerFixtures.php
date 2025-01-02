<?php

namespace App\DataFixtures;

use App\Entity\Flower;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FlowerFixtures extends Fixture
{
    private const array FLOWERS = [
        ['name' => 'Violette', 'amount' => 25, 'level' => 1],
        ['name' => 'Coquelicot', 'amount' => 50, 'level' => 2],
        ['name' => 'Bouton d\'Or', 'amount' => 100, 'level' => 3],
        ['name' => 'Laurier Rose', 'amount' => 200, 'level' => 4],
        ['name' => 'Tulipe', 'amount' => 400, 'level' => 5],
        ['name' => 'Germini', 'amount' => 800, 'level' => 6],
        ['name' => 'Lys', 'amount' => 1600, 'level' => 7],
        ['name' => 'Clématite', 'amount' => 3200, 'level' => 8],
        ['name' => 'Chrysanthème', 'amount' => 6400, 'level' => 9],
        ['name' => 'Rose Gold', 'amount' => 12800, 'level' => 10],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::FLOWERS as $flowerData) {
            $flower = new Flower();
            $flower->setName($flowerData['name'])
                ->setDonationAmount($flowerData['amount'])
                ->setLevel($flowerData['level']);

            $manager->persist($flower);
            $this->addReference('flower_' . $flowerData['level'], $flower);
        }

        $manager->flush();
    }
}
