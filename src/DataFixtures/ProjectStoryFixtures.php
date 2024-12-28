<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Faker\Factory;

class ProjectStoryFixtures extends Fixture implements DependentFixtureInterface
{
    private array $staticStoryData = [
        'keyPoints' => [
            'Nsectetur cing mauris quis risus laoreet elit.',
            'Suspe ndisse dolor sit amet suscipit sagittis leo.',
            'Entum estibulum metus aliquam egestas dignissim posuere.',
            'If you are going to use a auctor nec purus passage.'
        ],
        'galleryImages' => [
            'main' => [
                'project/project-details-tab-box-story-img-one-1.jpg',
                'project/project-details-tab-box-story-img-one-2.jpg'
            ],
            'secondary' => 'project/project-details-tab-box-story-img-two-1.jpg'
        ],
        'paragraphs' => [
            'Integer feugiat est in tincidunt congue. Nam eget accumsan ligula. Nunc auctor ligula a quam fermentum, non iaculis diam suscipit...',
            'Nulla in ex at mi viverra sagittis ut non erat raesent nec congue elit. Nunc arcu odio, convallis a lacinia ut...'
        ]
    ];

    public function __construct(
        private KernelInterface $kernel,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->addProvider(new \Faker\Provider\Lorem($faker));

        $projects = $manager->getRepository(Project::class)->findAll();

        foreach ($projects as $project) {
            $story = new ProjectStory();

            // Set key points
            $story->setKeyPoints($this->staticStoryData['keyPoints']);

            // Set paragraphs
            $story->setParagraphs($this->staticStoryData['paragraphs']);

            // Handle main image (just first one)
            $mainImagePath = $this->kernel->getProjectDir() . '/assets/landing/images/' . $this->staticStoryData['galleryImages']['main'][0];
            if (file_exists($mainImagePath)) {
                $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/projects/stories/main';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'story_main');
                copy($mainImagePath, $tempFile);

                $uploadedFile = new UploadedFile(
                    $tempFile,
                    basename($this->staticStoryData['galleryImages']['main'][0]),
                    'image/jpeg',
                    null,
                    true
                );

                $story->setMainImageFile($uploadedFile);
            }

            // Handle secondary image
            $secondaryImagePath = $this->kernel->getProjectDir() . '/assets/landing/images/' . $this->staticStoryData['galleryImages']['secondary'];
            if (file_exists($secondaryImagePath)) {
                $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/projects/stories/secondary';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'story_secondary');
                copy($secondaryImagePath, $tempFile);

                $uploadedFile = new UploadedFile(
                    $tempFile,
                    basename($this->staticStoryData['galleryImages']['secondary']),
                    'image/jpeg',
                    null,
                    true
                );

                $story->setSecondaryImageFile($uploadedFile);
            }

            $story->setProject($project);
            $manager->persist($story);
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
