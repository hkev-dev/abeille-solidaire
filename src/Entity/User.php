<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'creator')]
    private Collection $projects;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[Vich\UploadableField(mapping: 'user_avatars', fileNameProperty: 'avatar')]
    private ?File $avatarFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\OneToMany(targetEntity: ProjectBacking::class, mappedBy: 'backer')]
    private Collection $backedProjects;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $walletBalance = 0.0;

    #[ORM\ManyToOne(targetEntity: Flower::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Flower $currentFlower = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $projectDescription = null;

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'donor')]
    private Collection $donationsMade;

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'recipient')]
    private Collection $donationsReceived;

    #[ORM\OneToMany(targetEntity: Withdrawal::class, mappedBy: 'user')]
    private Collection $withdrawals;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $matrixPosition = null;

    #[ORM\Column(type: 'integer')]
    private int $matrixDepth = 0;

    #[ORM\Column(length: 20)]
    private string $registrationPaymentStatus = 'pending';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $waitingSince = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $flowerCompletions = [];

    #[ORM\Column(type: 'boolean')]
    private bool $isKycVerified = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $kycVerifiedAt = null;

    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'user')]
    private Collection $memberships;

    public const ACCOUNT_TYPE_PRIVATE = 'PRIVATE';
    public const ACCOUNT_TYPE_ENTERPRISE = 'ENTERPRISE';
    public const ACCOUNT_TYPE_ASSOCIATION = 'ASSOCIATION';

    public const ACCOUNT_TYPES = [
        self::ACCOUNT_TYPE_PRIVATE,
        self::ACCOUNT_TYPE_ENTERPRISE,
        self::ACCOUNT_TYPE_ASSOCIATION,
    ];

    #[ORM\Column(length: 50, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column(length: 2)]
    private ?string $country = null;

    #[ORM\Column(length: 20)]
    private string $accountType = self::ACCOUNT_TYPE_PRIVATE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $organizationName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $organizationNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $defaultPaymentMethodId = null;

    #[ORM\Column(type: 'boolean')]
    private bool $hasPaidAnnualFee = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $annualFeePaidAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $annualFeeExpiresAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isAnnualFeePending = false;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->backedProjects = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->donationsMade = new ArrayCollection();
        $this->donationsReceived = new ArrayCollection();
        $this->withdrawals = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        $this->hasPaidAnnualFee = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        // Always generate name from firstName and lastName
        return trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setCreator($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getCreator() === $this) {
                $project->setCreator(null);
            }
        }

        return $this;
    }

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function isVerified(): bool
    {
        // User is verified when registration payment is completed
        return $this->registrationPaymentStatus === 'completed';
    }

    public function setIsVerified(bool $isVerified): self
    {
        // This method is maintained for compatibility but should not be used directly
        // Verification status is controlled by registration payment status
        return $this;
    }

    public function getFullName(): string
    {
        return $this->getName(); // Use the getName method for consistency
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile = null): void
    {
        $this->avatarFile = $avatarFile;
        if ($avatarFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getWalletBalance(): float
    {
        return $this->walletBalance;
    }

    public function setWalletBalance(float $walletBalance): self
    {
        $this->walletBalance = $walletBalance;
        return $this;
    }

    public function addToWalletBalance(float $amount): self
    {
        $this->walletBalance += $amount;
        return $this;
    }

    public function getCurrentFlower(): ?Flower
    {
        return $this->currentFlower;
    }

    public function setCurrentFlower(?Flower $flower): self
    {
        $this->currentFlower = $flower;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getMatrixPosition(): ?int
    {
        return $this->matrixPosition;
    }

    public function setMatrixPosition(?int $position): self
    {
        // Allow null or zero for users in waiting room
        if ($position !== null && $position < 0) {
            throw new \InvalidArgumentException('Matrix position cannot be negative');
        }
        $this->matrixPosition = $position;
        return $this;
    }

    public function getMatrixDepth(): int
    {
        return $this->matrixDepth;
    }

    public function setMatrixDepth(int $depth): self
    {
        if ($depth < 0) {
            throw new \InvalidArgumentException('Matrix depth cannot be negative');
        }
        $this->matrixDepth = $depth;
        return $this;
    }

    public function hasAvailableMatrixSlot(): bool
    {
        return $this->children->count() < 4;
    }

    public function getMatrixLevel(): int
    {
        return $this->matrixDepth + 1;
    }

    public function getProjectDescription(): ?string
    {
        return $this->projectDescription;
    }

    public function setProjectDescription(?string $projectDescription): self
    {
        $this->projectDescription = $projectDescription;
        return $this;
    }

    public function getDonationsMade(): Collection
    {
        return $this->donationsMade;
    }

    public function getDonationsReceived(): Collection
    {
        return $this->donationsReceived;
    }

    public function getWithdrawals(): Collection
    {
        return $this->withdrawals;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getRegistrationPaymentStatus(): string
    {
        return $this->registrationPaymentStatus;
    }

    public function setRegistrationPaymentStatus(string $status): self
    {
        if (!in_array($status, ['pending', 'completed', 'failed'])) {
            throw new \InvalidArgumentException('Invalid registration payment status');
        }
        $this->registrationPaymentStatus = $status;
        return $this;
    }

    public function getWaitingSince(): ?\DateTimeInterface
    {
        return $this->waitingSince;
    }

    public function setWaitingSince(?\DateTimeInterface $waitingSince): self
    {
        $this->waitingSince = $waitingSince;
        return $this;
    }

    public function getFlowerCycleCompletions(): Collection
    {
        return $this->flowerCycleCompletions;
    }

    public function getFlowerCompletions(): ?array
    {
        return $this->flowerCompletions;
    }

    public function setFlowerCompletions(?array $completions): self
    {
        $this->flowerCompletions = $completions;
        return $this;
    }

    public function getFlowerCompletionCount(Flower $flower): int
    {
        if (!$this->flowerCompletions) {
            return 0;
        }
        return $this->flowerCompletions[$flower->getId()] ?? 0;
    }

    public function incrementFlowerCompletion(Flower $flower): void
    {
        $this->flowerCompletions[$flower->getId()] = $this->getFlowerCompletionCount($flower) + 1;
    }

    public function hasReachedFlowerLimit(Flower $flower): bool
    {
        return $this->getFlowerCompletionCount($flower) >= 10;
    }

    public function isKycVerified(): bool
    {
        return $this->isKycVerified;
    }

    public function setIsKycVerified(bool $isKycVerified): self
    {
        $this->isKycVerified = $isKycVerified;
        if ($isKycVerified) {
            $this->kycVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getKycVerifiedAt(): ?\DateTimeInterface
    {
        return $this->kycVerifiedAt;
    }

    public function setKycVerifiedAt(?\DateTimeImmutable $kycVerifiedAt): self
    {
        $this->kycVerifiedAt = $kycVerifiedAt;
        return $this;
    }

    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function getCurrentMembership(): ?Membership
    {
        return $this->memberships
            ->filter(fn(Membership $membership) => $membership->isActive())
            ->first() ?: null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): self
    {
        if (!in_array($accountType, self::ACCOUNT_TYPES)) {
            throw new \InvalidArgumentException('Invalid account type');
        }
        $this->accountType = $accountType;
        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(?string $organizationName): self
    {
        $this->organizationName = $organizationName;
        return $this;
        return $this;
    }

    public function getOrganizationNumber(): ?string
    {
        return $this->organizationNumber;
    }

    public function setOrganizationNumber(?string $organizationNumber): self
    {
        $this->organizationNumber = $organizationNumber;
        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): self
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getDefaultPaymentMethodId(): ?string
    {
        return $this->defaultPaymentMethodId;
    }

    public function setDefaultPaymentMethodId(?string $defaultPaymentMethodId): self
    {
        $this->defaultPaymentMethodId = $defaultPaymentMethodId;
        return $this;
    }

    public function hasPaidAnnualFee(): bool
    {
        if (!$this->hasPaidAnnualFee) {
            return false;
        }

        // Check if the annual fee has expired
        if ($this->annualFeeExpiresAt && $this->annualFeeExpiresAt < new \DateTime()) {
            return false;
        }

        return true;
    }

    public function setHasPaidAnnualFee(bool $hasPaidAnnualFee): self
    {
        $this->hasPaidAnnualFee = $hasPaidAnnualFee;
        if ($hasPaidAnnualFee) {
            $this->annualFeePaidAt = new \DateTime();
            $this->annualFeeExpiresAt = (new \DateTime())->modify('+1 year');
            $this->isAnnualFeePending = false;
        }
        return $this;
    }

    public function getAnnualFeePaidAt(): ?\DateTimeInterface
    {
        return $this->annualFeePaidAt;
    }

    public function getAnnualFeeExpiresAt(): ?\DateTimeInterface
    {
        return $this->annualFeeExpiresAt;
    }

    public function isAnnualFeePending(): bool
    {
        return $this->isAnnualFeePending;
    }

    public function setIsAnnualFeePending(bool $isAnnualFeePending): self
    {
        $this->isAnnualFeePending = $isAnnualFeePending;
        return $this;
    }

    public function getDaysUntilAnnualFeeExpiration(): ?int
    {
        if (!$this->annualFeeExpiresAt) {
            return null;
        }

        $now = new \DateTime();
        $interval = $this->annualFeeExpiresAt->diff($now);

        if ($interval->invert === 0) {
            return 0; // Already expired
        }

        return $interval->days;
    }

    public function isEligibleForWithdrawal(): bool
    {
        return $this->isVerified() &&
            $this->isKycVerified() &&
            $this->hasPaidAnnualFee() &&
            $this->getProjectDescription() !== null;
    }

    public function canProgressInFlowers(): bool
    {
        // Users can progress in flowers only if they've paid their annual fee
        // or are within the grace period
        if ($this->hasPaidAnnualFee()) {
            return true;
        }

        // Check if user is within grace period (e.g., 30 days after expiration)
        if ($this->annualFeeExpiresAt) {
            $gracePeriodEnd = (clone $this->annualFeeExpiresAt)->modify('+30 days');
            return new \DateTime() < $gracePeriodEnd;
        }

        return false;
    }

    public function getFlowerProgress(): array
    {
        $receivedCount = $this->donationsReceived
            ->filter(
                fn($donation) =>
                $donation->getFlower() === $this->currentFlower &&
                $donation->getDonationType() === 'direct'
            )
            ->count();

        return [
            'received' => $receivedCount,
            'total' => 4,
            'percentage' => ($receivedCount / 4) * 100
        ];
    }

    public function getMatrixInfo(): array
    {
        return [
            'position' => $this->matrixPosition,
            'depth' => $this->matrixDepth,
            'level' => $this->matrixDepth + 1,
            'hasParent' => $this->parent !== null,
            'childrenCount' => $this->children->count(),
            'availableSlots' => 4 - $this->children->count()
        ];
    }

    public function canAcceptChildren(): bool
    {
        return $this->children->count() < 4 &&
            $this->isVerified() &&
            $this->registrationPaymentStatus === 'completed';
    }
}
