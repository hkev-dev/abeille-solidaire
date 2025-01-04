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

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'referrals')]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $referrer = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'referrer')]
    private Collection $referrals;

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

    #[ORM\Column(length: 32, unique: true)]
    private ?string $referralCode = null;

    #[ORM\Column(length: 20)]
    private string $registrationPaymentStatus = 'pending';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $waitingSince = null;

    #[ORM\OneToMany(targetEntity: FlowerCycleCompletion::class, mappedBy: 'user')]
    private Collection $flowerCycleCompletions;

    #[ORM\Column(type: 'boolean')]
    private bool $isKycVerified = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $kycVerifiedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $kycProvider = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $kycReferenceId = null;

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

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->backedProjects = new ArrayCollection();
        $this->referrals = new ArrayCollection();
        $this->donationsMade = new ArrayCollection();
        $this->donationsReceived = new ArrayCollection();
        $this->withdrawals = new ArrayCollection();
        $this->flowerCycleCompletions = new ArrayCollection();
        $this->memberships = new ArrayCollection();
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

    public function getCurrentFlower(): ?Flower
    {
        return $this->currentFlower;
    }

    public function setCurrentFlower(?Flower $flower): self
    {
        $this->currentFlower = $flower;
        return $this;
    }

    public function getReferrer(): ?self
    {
        return $this->referrer;
    }

    public function setReferrer(?self $referrer): self
    {
        $this->referrer = $referrer;
        return $this;
    }

    public function getReferrals(): Collection
    {
        return $this->referrals;
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

    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    public function setReferralCode(string $referralCode): self
    {
        $this->referralCode = $referralCode;
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

    public function getFlowerCompletionCount(Flower $flower): int
    {
        $completion = $this->flowerCycleCompletions
            ->filter(fn(FlowerCycleCompletion $completion) => $completion->getFlower() === $flower)
            ->first();

        return $completion ? $completion->getCompletionCount() : 0;
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

    public function getKycProvider(): ?string
    {
        return $this->kycProvider;
    }

    public function setKycProvider(?string $provider): self
    {
        $this->kycProvider = $provider;
        return $this;
    }

    public function getKycReferenceId(): ?string
    {
        return $this->kycReferenceId;
    }

    public function setKycReferenceId(?string $referenceId): self
    {
        $this->kycReferenceId = $referenceId;
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
}
