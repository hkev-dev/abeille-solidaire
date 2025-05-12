<?php

namespace App\Entity;

use App\Repository\CauseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Entity\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Doctrine\DBAL\Type\Enum\CauseStateEnumType;
use App\Constant\Enum\Cause\State;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: CauseRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Cause
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private ?float $goal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: 'cause_images', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private float $pledged = 0.0;

    #[ORM\Column(type: CauseStateEnumType::NAME, nullable: true)]
    private ?State $status = State::IN_PROGRESS;

    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    private ?string $slug = null;

    /**
     * @var Collection<int, PonctualDonation>
     */
    #[ORM\OneToMany(targetEntity: PonctualDonation::class, mappedBy: 'cause')]
    private Collection $amount;

    public function __construct(){
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->amount = new ArrayCollection();
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getGoal(): ?float
    {
        return $this->goal;
    }

    public function setGoal(float $goal): static
    {
        $this->goal = $goal;

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

    public function getReceivedAmount(): float
    {
        return $this->getPledged();
    }

    public function setPledged(?float $pledged): static
    {
        $this->pledged = $pledged;

        return $this;
    }

    public function getPledged(): ?float
    {
        return $this->pledged;
    }

    public function getStatus(): ?State
    {
        return $this->status;
    }

    public function setStatus(State $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getProgress(): float
    {
        if ($this->getGoal() <= 0) {
            return 0;
        }
        return min(100, ($this->getReceivedAmount() / $this->getGoal()) * 100);
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

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            State::IN_PROGRESS => 'En cours',
            State::COMPLETED => 'Terminé',
            State::CANCELED => 'Annulé',
            default => 'Inconnu',
        };
    }

    public function getDaysLeft(): int
    {
        if (!$this->endDate) {
            return 0;
        }
        $now = new \DateTimeImmutable();
        return max(0, $this->endDate->diff($now)->days);
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

    /**
     * @return Collection<int, PonctualDonation>
     */
    public function getAmount(): Collection
    {
        return $this->amount;
    }

    public function addAmount(PonctualDonation $amount): static
    {
        if (!$this->amount->contains($amount)) {
            $this->amount->add($amount);
            $amount->setCause($this);
        }

        return $this;
    }

    public function removeAmount(PonctualDonation $amount): static
    {
        if ($this->amount->removeElement($amount)) {
            // set the owning side to null (unless already changed)
            if ($amount->getCause() === $this) {
                $amount->setCause(null);
            }
        }

        return $this;
    }
}
