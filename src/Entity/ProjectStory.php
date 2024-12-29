<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\ProjectStoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ProjectStoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class ProjectStory
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null {
        get {
            return $this->id;
        }
    }

    #[ORM\Column(type: Types::JSON)]
    private array $keyPoints = [];

    #[ORM\Column(type: Types::JSON)]
    private array $paragraphs = [];

    #[Vich\UploadableField(mapping: 'project_story_main', fileNameProperty: 'mainImage')]
    private ?File $mainImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainImage = null;

    #[Vich\UploadableField(mapping: 'project_story_secondary', fileNameProperty: 'secondaryImage')]
    private ?File $secondaryImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secondaryImage = null;

    #[ORM\OneToOne(inversedBy: 'story')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    public function getKeyPoints(): array
    {
        return $this->keyPoints;
    }

    public function setKeyPoints(array $keyPoints): static
    {
        $this->keyPoints = $keyPoints;
        return $this;
    }

    public function getParagraphs(): array
    {
        return $this->paragraphs;
    }

    public function setParagraphs(array $paragraphs): static
    {
        $this->paragraphs = $paragraphs;
        return $this;
    }

    public function getMainImageFile(): ?File
    {
        return $this->mainImageFile;
    }

    public function setMainImageFile(?File $mainImageFile = null): void
    {
        $this->mainImageFile = $mainImageFile;
        if ($mainImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getMainImage(): ?string
    {
        return $this->mainImage;
    }

    public function setMainImage(?string $mainImage): self
    {
        $this->mainImage = $mainImage;
        return $this;
    }

    public function getSecondaryImageFile(): ?File
    {
        return $this->secondaryImageFile;
    }

    public function setSecondaryImageFile(?File $secondaryImageFile = null): void
    {
        $this->secondaryImageFile = $secondaryImageFile;
        if ($secondaryImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getSecondaryImage(): ?string
    {
        return $this->secondaryImage;
    }

    public function setSecondaryImage(?string $secondaryImage): self
    {
        $this->secondaryImage = $secondaryImage;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }
}
