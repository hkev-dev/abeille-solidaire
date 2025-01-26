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

    public const ACCOUNT_TYPE_PRIVATE = 'PRIVATE';
    public const ACCOUNT_TYPE_ENTERPRISE = 'ENTERPRISE';
    public const ACCOUNT_TYPE_ASSOCIATION = 'ASSOCIATION';

    public const ACCOUNT_TYPES = [
        self::ACCOUNT_TYPE_PRIVATE,
        self::ACCOUNT_TYPE_ENTERPRISE,
        self::ACCOUNT_TYPE_ASSOCIATION,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Vich\UploadableField(mapping: 'user_avatars', fileNameProperty: 'avatar')]
    private ?File $avatarFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

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

    #[ORM\OneToOne(targetEntity: Project::class, mappedBy: 'creator')]
    private ?Project $project = null;

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

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'donor')]
    private Collection $donationsMade;

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'recipient')]
    private Collection $donationsReceived;

    #[ORM\OneToMany(targetEntity: Withdrawal::class, mappedBy: 'user')]
    private Collection $withdrawals;

    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'user')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $memberships;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $matrixPosition = null;

    #[ORM\Column(type: 'integer')]
    private int $matrixDepth = 0;

    #[ORM\Column(length: 20)]
    private string $registrationPaymentStatus = 'pending';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $waitingSince = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isKycVerified = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $kycVerifiedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $defaultPaymentMethodId = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->donationsMade = new ArrayCollection();
        $this->donationsReceived = new ArrayCollection();
        $this->withdrawals = new ArrayCollection();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        if ($project === null && $this->project !== null) {
            $this->project->setCreator(null);
        }

        if ($project !== null && $project->getCreator() !== $this) {
            $project->setCreator($this);
        }

        $this->project = $project;
        return $this;
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
    }

    public function isVerified(): bool
    {
        return $this->registrationPaymentStatus === 'completed' && $this->isKycVerified;
    }

    public function getFullName(): string
    {
        return $this->getName();
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

    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function getCurrentMembership(): ?Membership
    {
        return $this->memberships->filter(function (Membership $membership) {
            return $membership->getStatus() === Membership::STATUS_ACTIVE;
        })->first() ?: null;
    }

    public function hasPaidAnnualFee(): bool
    {
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->getRoles())) {
            return true;
        }

        $currentMembership = $this->getCurrentMembership();
        if (!$currentMembership) {
            return false;
        }

        return !$currentMembership->isExpired();
    }

    public function getLastMembership(): ?Membership
    {
        return $this->memberships->first() ?: null;
    }

    public function addMembership(Membership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setUser($this);
        }
        return $this;
    }

    public function removeMembership(Membership $membership): self
    {
        if ($this->memberships->removeElement($membership)) {
            // We might want to change the status to expired instead of removing the relationship
            // since we can't set the user to null (it's a required field)
            $membership->setStatus(Membership::STATUS_EXPIRED);
        }
        return $this;
    }

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function getAccountTypeLabel(): string
    {
        return match ($this->accountType) {
            self::ACCOUNT_TYPE_PRIVATE => 'Private',
            self::ACCOUNT_TYPE_ENTERPRISE => 'Enterprise',
            self::ACCOUNT_TYPE_ASSOCIATION => 'Association',
            default => 'Unknown'
        };
    }

    public function getDaysUntilAnnualFeeExpiration(): ?int
    {
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->getRoles())) {
            return null; // Admin users don't expire
        }

        $currentMembership = $this->getCurrentMembership();
        if (!$currentMembership) {
            return 0;
        }

        return $currentMembership->getDaysUntilExpiration();
    }

    public function getMembershipExpiredAt(): ?\DateTimeInterface
    {
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->getRoles())) {
            return null; // Admin users don't expire
        }

        $currentMembership = $this->getCurrentMembership();
        return $currentMembership ? $currentMembership->getEndDate() : null;
    }

    public function getMatrixPosition(): ?int
    {
        return $this->matrixPosition;
    }

    public function setMatrixPosition(?int $position): self
    {
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

    public function getMatrixChildrenCount(): int
    {
        return $this->children->count();
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

    public function getKycVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->kycVerifiedAt;
    }

    public function setKycVerifiedAt(?\DateTimeImmutable $kycVerifiedAt): self
    {
        $this->kycVerifiedAt = $kycVerifiedAt;
        return $this;
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

    public function hasProject(): bool
    {
        return $this->project !== null;
    }

    // Payment-related methods
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

    public function isEligibleForWithdrawal(): bool
    {
        // Admin users bypass matrix depth check
        $hasRequiredMatrixDepth = array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->getRoles()) || 
            $this->matrixDepth >= 3;

        return $this->isKycVerified &&      // KYC verification completed
            $this->hasPaidAnnualFee() &&    // Annual membership is active
            $hasRequiredMatrixDepth &&       // Has required matrix depth
            $this->hasProject();             // Has at least one project
    }

    public function getFlowerProgress(): array
    {
        $childrenCount = $this->getMatrixChildrenCount();
        return [
            'received' => $childrenCount,
            'total' => 4,
            'percentage' => ($childrenCount / 4) * 100,
            'remaining' => 4 - $childrenCount
        ];
    }

    public function getTotalReceivedInFlower(): float
    {
        $total = 0.0;
        foreach ($this->donationsReceived as $donation) {
            if ($donation->getFlower() === $this->currentFlower) {
                $total += $donation->getAmount();
            }
        }
        return $total;
    }

    public function getTotalReceivedInCurrentCycle(): float
    {
        $total = 0.0;
        $cycleStart = new \DateTime();

        // Get the last solidarity donation to determine cycle start
        $lastSolidarity = $this->donationsReceived
            ->filter(fn($d) => $d->getDonationType() === 'solidarity')
            ->last();

        if ($lastSolidarity) {
            $cycleStart = $lastSolidarity->getTransactionDate();
        }

        foreach ($this->donationsReceived as $donation) {
            if (
                $donation->getFlower() === $this->currentFlower &&
                $donation->getTransactionDate() > $cycleStart
            ) {
                $total += $donation->getAmount();
            }
        }
        return $total;
    }
}
