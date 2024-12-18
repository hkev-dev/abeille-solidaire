<?php

namespace App\DataFixtures;

use App\Entity\MainSlider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class MainSliderFixtures extends Fixture
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $slidesData = [
            [
                'sourceImage' => 'main-slider-1-1.jpg',
                'subtitle' => 'Raising Money is Easy Now!',
                'title' => 'Ultimate Crowdfunding Platforms',
                'position' => 1
            ],
            [
                'sourceImage' => 'main-slider-1-2.jpg',
                'subtitle' => 'Support Amazing Ideas!',
                'title' => 'Innovative Projects Need Your Help',
                'position' => 2
            ],
            [
                'sourceImage' => 'main-slider-1-3.jpg',
                'subtitle' => 'Make Dreams Come True!',
                'title' => 'Revolutionary Crowdfunding Solutions',
                'position' => 3
            ]
        ];

        foreach ($slidesData as $slideData) {
            $slide = new MainSlider();
            $slide->setSubtitle($slideData['subtitle']);
            $slide->setTitle($slideData['title']);
            $slide->setPosition($slideData['position']);
            $slide->setIsActive(true);

            // Handle file upload
            $sourcePath = $this->kernel->getProjectDir() . '/assets/landing/images/backgrounds/' . $slideData['sourceImage'];
            if (file_exists($sourcePath)) {
                // Create uploads directory if it doesn't exist
                $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/slides';
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
