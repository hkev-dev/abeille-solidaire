<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\ProjectUpdateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectUpdateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProjectUpdate
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'updates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private bool $isMilestone = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function isMilestone(): bool
    {
        return $this->isMilestone;
    }

    public function setIsMilestone(bool $isMilestone): static
    {
        $this->isMilestone = $isMilestone;
        return $this;
    }

    public function getTime(): string
    {
        $now = new \DateTime();
        $interval = $this->createdAt->diff($now);

        if ($interval->y > 0) {
            return $interval->y . ' Years Ago';
        }
        if ($interval->m > 0) {
            return $interval->m . ' Months Ago';
        }
        if ($interval->d > 0) {
            return $interval->d . ' Days Ago';
        }
        if ($interval->h > 0) {
            return $interval->h . ' Hours Ago';
        }
        
        return 'Just Now';
    }
}
