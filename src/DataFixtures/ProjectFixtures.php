<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectCategory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private KernelInterface $kernel,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $projects = [
            [
                'title' => 'AudioPhile – First Smart Wireless Headphones',
                'description' => 'Revolutionary wireless headphones with AI-powered noise cancellation.',
                'goal' => 50000,
                'pledged' => 35000,
                'backers' => 350,
                'location' => 'San Francisco, USA',
                'endDate' => new \DateTime('+30 days'),
                'category' => 'technology',
                'creator' => 'john_doe',
                'image' => 'project/project-1-1.jpg'
            ],
            [
                'title' => 'Eco-Friendly Fashion Collection',
                'description' => 'Sustainable fashion line made from recycled materials.',
                'goal' => 25000,
                'pledged' => 15000,
                'backers' => 180,
                'location' => 'Paris, France',
                'endDate' => new \DateTime('+45 days'),
                'category' => 'fashion',
                'creator' => 'jane_smith',
                'image' => 'project/project-1-2.jpg'
            ],
        ];

        foreach ($projects as $projectData) {
            $project = new Project();
            $project->setTitle($projectData['title'])
                ->setDescription($projectData['description'])
                ->setGoal($projectData['goal'])
                ->setPledged($projectData['pledged'])
                ->setBackers($projectData['backers'])
                ->setLocation($projectData['location'])
                ->setEndDate($projectData['endDate'])
                ->setCategory($this->getReference('project_category_' . $projectData['category'], ProjectCategory::class))
                ->setCreator($this->getReference('user_' . $projectData['creator'], User::class));

            // Handle image upload
            $sourcePath = $this->kernel->getProjectDir() . '/assets/landing/images/' . $projectData['image'];
            if (file_exists($sourcePath)) {
                $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/projects';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'project');
                copy($sourcePath, $tempFile);

                $uploadedFile = new UploadedFile(
                    $tempFile,
                    basename($projectData['image']),
                    'image/jpeg',
                    null,
                    true
                );

                $project->setImageFile($uploadedFile);
            }

            $manager->persist($project);
            $this->addReference('project_' . $this->slugify($projectData['title']), $project);
        }

        $manager->flush();
    }

    private function slugify(string $text): string
    {
        // Replace all special characters and spaces with underscores
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '_', $text);
        $text = trim($text, '_');
        return strtolower($text);
    }

    public function getDependencies(): array
    {
        return [
            ProjectCategoryFixtures::class,
            UserFixtures::class,
        ];
    }
}
