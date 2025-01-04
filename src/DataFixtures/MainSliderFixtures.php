<?php

namespace App\DataFixtures;

use App\Entity\MainSlider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MainSliderFixtures extends Fixture
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $slidesData = [
            [
                'sourceImage' => 'main-slider-1-1.jpg',
                'subtitle' => 'Projet d\'éducation',
                'title' => 'Assurons l\'avenir de nos enfants',
                'position' => 1
            ],
            [
                'sourceImage' => 'main-slider-1-2.jpg',
                'subtitle' => 'Demeure',
                'title' => 'Construisez votre maison de retraite ici.',
                'position' => 2
            ],
            [
                'sourceImage' => 'main-slider-1-3.jpg',
                'subtitle' => 'Car',
                'title' => 'Votre véhicule de rêve vous attend',
                'position' => 3
            ],
            [
                'sourceImage' => 'main-slider-1-4.jpg',
                'subtitle' => 'Santé',
                'title' => 'Sauvons des vies avec le club Abeille Solidaire',
                'position' => 4
            ]
        ];

        foreach ($slidesData as $slideData) {
            $slide = new MainSlider();
            $slide->setSubtitle($slideData['subtitle']);
            $slide->setTitle($slideData['title']);
            $slide->setPosition($slideData['position']);
            $slide->setIsActive(true);

            // Handle file upload
            $sourcePath = $this->parameterBag->get('kernel.project_dir') . '/assets/landing/images/backgrounds/' . $slideData['sourceImage'];
            if (file_exists($sourcePath)) {
                // Create uploads directory if it doesn't exist
                $uploadDir = $this->parameterBag->get('kernel.project_dir') . '/public/uploads/slides';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Copy file to temporary location
                $tempFile = tempnam(sys_get_temp_dir(), 'slide');
                copy($sourcePath, $tempFile);

                // Create UploadedFile instance
                $uploadedFile = new UploadedFile(
                    $tempFile,
                    $slideData['sourceImage'],
                    'image/jpeg',
                    null,
                    true // Set test mode to true for fixtures
                );

                $slide->setImageFile($uploadedFile);
            } else {
                throw new \RuntimeException(sprintf('Source image not found: %s', $sourcePath));
            }

            $manager->persist($slide);
        }

        $manager->flush();
    }
}
