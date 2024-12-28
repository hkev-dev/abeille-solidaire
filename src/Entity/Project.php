<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Project
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private float $goal = 0.0;

    #[ORM\Column]
    private float $pledged = 0.0;

    #[ORM\Column]
    private ?int $backers = 0;

    #[Vich\UploadableField(mapping: 'projects', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProjectCategory $category = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectUpdate::class)]
    private Collection $updates;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectReview::class)]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectReward::class)]
    private Collection $rewards;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectFAQ::class)]
    private Collection $faqs;

    #[ORM\OneToOne(mappedBy: 'project', targetEntity: ProjectStory::class, cascade: ['persist', 'remove'])]
    private ?ProjectStory $story = null;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectBacking::class)]
    private Collection $backings;

    public function __construct()
    {
        $this->updates = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->rewards = new ArrayCollection();
        $this->faqs = new ArrayCollection();
        $this->backings = new ArrayCollection();
    }

    // Add getters and setters for all properties

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStory(): ?ProjectStory
    {
        return $this->story;
    }

    public function setStory(?ProjectStory $story): static
    {
        if ($story === null && $this->story !== null) {
            $this->story->setProject(null);
        }

        if ($story !== null && $story->getProject() !== $this) {
            $story->setProject($this);
        }

        $this->story = $story;
        return $this;
    }

    public function getGoal(): float
    {
        return $this->goal;
    }

    public function setGoal(float $goal): static
    {
        $this->goal = $goal;
        return $this;
    }

    public function getPledged(): float
    {
        return $this->pledged;
    }

    public function setPledged(float $pledged): static
    {
        $this->pledged = $pledged;
        return $this;
    }

    public function getBackers(): ?int
    {
        return $this->backers;
    }

    public function setBackers(int $backers): static
    {
        $this->backers = $backers;
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getCategory(): ?ProjectCategory
    {
        return $this->category;
    }

    public function setCategory(?ProjectCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    public function getUpdates(): Collection
    {
        return $this->updates;
    }

    public function addUpdate(ProjectUpdate $update): static
    {
        if (!$this->updates->contains($update)) {
            $this->updates->add($update);
            $update->setProject($this);
        }
        return $this;
    }

    public function removeUpdate(ProjectUpdate $update): static
    {
        if ($this->updates->removeElement($update)) {
            if ($update->getProject() === $this) {
                $update->setProject(null);
            }
        }
        return $this;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(ProjectReview $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setProject($this);
        }
        return $this;
    }

    public function removeReview(ProjectReview $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getProject() === $this) {
                $review->setProject(null);
            }
        }
        return $this;
    }

    public function getRewards(): Collection
    {
        return $this->rewards;
    }

    public function addReward(ProjectReward $reward): static
    {
        if (!$this->rewards->contains($reward)) {
            $this->rewards->add($reward);
            $reward->setProject($this);
        }
        return $this;
    }

    public function removeReward(ProjectReward $reward): static
    {
        if ($this->rewards->removeElement($reward)) {
            if ($reward->getProject() === $this) {
                $reward->setProject(null);
            }
        }
        return $this;
    }

    public function getFaqs(): Collection
    {
        return $this->faqs;
    }

    public function addFaq(ProjectFAQ $faq): static
    {
        if (!$this->faqs->contains($faq)) {
            $this->faqs->add($faq);
            $faq->setProject($this);
        }
        return $this;
    }

    public function removeFaq(ProjectFAQ $faq): static
    {
        if ($this->faqs->removeElement($faq)) {
            if ($faq->getProject() === $this) {
                $faq->setProject(null);
            }
        }
        return $this;
    }

    public function getBackings(): Collection
    {
        return $this->backings;
    }

    public function addBacking(ProjectBacking $backing): static
    {
        if (!$this->backings->contains($backing)) {
            $this->backings->add($backing);
            $backing->setProject($this);
        }
        return $this;
    }

    public function removeBacking(ProjectBacking $backing): static
    {
        if ($this->backings->removeElement($backing)) {
            if ($backing->getProject() === $this) {
                $backing->setProject(null);
            }
        }
        return $this;
    }

    public function getProgress(): float
    {
        if ($this->goal <= 0) {
            return 0;
        }
        return min(100, ($this->pledged / $this->goal) * 100);
    }

    public function getDaysLeft(): int
    {
        if (!$this->endDate) {
            return 0;
        }
        $now = new \DateTime();
        return max(0, $this->endDate->diff($now)->days);
    }
}
