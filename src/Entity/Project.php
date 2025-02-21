<?php

namespace App\Entity;

use App\Constant\Enum\Project\State;
use App\Doctrine\DBAL\Type\Enum\ProjectStateEnumType;
use BackedEnum;
use LogicException;
use Serializable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProjectRepository;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Entity\Trait\TimestampableTrait;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Project implements Serializable
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?float $goal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private float $pledged = 0.0;

    #[Vich\UploadableField(mapping: 'projects', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProjectCategory $category = null;

    #[ORM\OneToOne(inversedBy: 'currentProject')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $creator = null;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectUpdate::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $updates;

    #[ORM\OneToMany(targetEntity: ProjectReview::class, mappedBy: 'project')]
    private Collection $reviews;

    #[ORM\OneToMany(targetEntity: ProjectFAQ::class, mappedBy: 'project')]
    private Collection $faqs;

    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?User $owner = null;

    #[ORM\Column(type: ProjectStateEnumType::NAME, nullable: true)]
    private ?State $status = State::IN_PROGRESS;

    public function __construct()
    {
        $this->updates = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->faqs = new ArrayCollection();
    }

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

    public function getGoal(): ?float
    {
        return $this->goal;
    }

    public function setGoal(?float $goal): static
    {
        $this->goal = $goal;
        return $this;
    }

    public function getPledged(): ?float
    {
        return $this->pledged;
    }

    public function setPledged(?float $pledged): static
    {
        $this->pledged = $pledged;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): static
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getProgress(): float
    {
        if ($this->getGoal() <= 0) {
            return 0;
        }
        return min(100, ($this->getReceivedAmount() / $this->getGoal()) * 100);
    }

    public function getDaysLeft(): int
    {
        if (!$this->endDate) {
            return 0;
        }
        $now = new \DateTimeImmutable();
        return max(0, $this->endDate->diff($now)->days);
    }


    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->image,

        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list(
            $this->id,
        ) = unserialize($serialized);
    }

    public function getReceivedAmount(): float
    {
        return $this->getPledged();
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @param float|null $amount
     * @return $this
     * @throws LogicException
     */
    public function addPledged(?float $amount): static
    {
        if ($this->getPledged() >= $this->getGoal()) {
            throw new LogicException('Can only add pledged amount if the project is not completed');
        }

        $this->pledged += $amount;

        if ($this->getPledged() > $this->getGoal()) {
            $this->pledged = $this->getGoal();
        }

        return $this;
    }

    public function getStatus(): ?BackedEnum
    {
        return $this->status;
    }

    public function setStatus(State $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            State::IN_PROGRESS => 'En cours',
            State::COMPLETED => 'Terminé',
            State::CANCELED => 'Annulé',
            default => 'Inconnu'
        };
    }

    public function getStatusBadge(): string
    {
        return match ($this->status) {
            State::IN_PROGRESS => 'badge-primary',
            State::COMPLETED => 'badge-success',
            State::CANCELED => 'badge-danger',
            default => 'badge-secondary'
        };
    }
}