<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectCategory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    private array $categories = ['technology', 'fashion', 'design', 'food', 'art', 'games'];
    private array $creators = ['john_doe', 'jane_smith', 'alice_wonder', 'bob_builder'];
    private array $images = [
        'project/project-1-1.jpg',
        'project/project-1-2.jpg',
        'project/project-1-3.jpg',
        'project/project-1-4.jpg'
    ];

    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Create projects for each creator
        foreach ($this->creators as $index => $username) {
            $creator = $this->getReference('user_' . $username, User::class);
            $project = new Project();
            $project->setTitle('Project by ' . $creator->getName())
                ->setDescription($faker->paragraphs(3, true))
                ->setGoal($creator->getCurrentFlower()->getDonationAmount() * 4) // Goal is 4x the flower amount
                ->setPledged(0)
                ->setBackers(0)
                ->setLocation($faker->city . ', ' . $faker->country)
                ->setEndDate($faker->dateTimeBetween('+1 month', '+6 months'))
                ->setCategory($this->getReference('project_category_' . $faker->randomElement($this->categories), ProjectCategory::class))
                ->setCreator($creator)
                ->setIsActive(true);

            // Handle image upload - cycle through existing images
            $imageFile = $faker->randomElement($this->images);
            $sourcePath = $this->parameterBag->get('kernel.project_dir') . '/assets/landing/images/' . $imageFile;
            if (file_exists($sourcePath)) {
                $uploadDir = $this->parameterBag->get('kernel.project_dir') . '/public/uploads/projects';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'project');
                copy($sourcePath, $tempFile);

                $uploadedFile = new UploadedFile(
                    $tempFile,
                    basename($imageFile),
                    'image/jpeg',
                    null,
                    true
                );

                $project->setImageFile($uploadedFile);
            }

            $manager->persist($project);
            // Use index to ensure unique references
            $this->addReference('project_' . $username, $project);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            FlowerFixtures::class,
            ProjectCategoryFixtures::class, // Changed from CategoryFixtures
            UserFixtures::class,
        ];
    }
}
