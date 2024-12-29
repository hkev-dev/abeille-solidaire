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
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Generate 20 projects
        for ($i = 0; $i < 20; $i++) {
            $project = new Project();
            $project->setTitle($faker->sentence())
                ->setDescription($faker->paragraphs(2, true))
                ->setGoal($faker->numberBetween(10000, 100000))
                ->setPledged($faker->numberBetween(0, 90000))
                ->setBackers($faker->numberBetween(0, 500))
                ->setLocation($faker->city . ', ' . $faker->country)
                ->setEndDate($faker->dateTimeBetween('+1 month', '+6 months'))
                ->setCategory($this->getReference('project_category_' . $faker->randomElement($this->categories), ProjectCategory::class))
                ->setCreator($this->getReference('user_' . $faker->randomElement($this->creators), User::class));

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
            $this->addReference('project_' . $i, $project);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjectCategoryFixtures::class,
            UserFixtures::class,
        ];
    }
}
