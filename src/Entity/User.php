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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'creator')]
    private Collection $projects;

    #[ORM\Column]
    private int $backedCount = 0;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $verificationTokenExpiresAt = null;

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

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->backedProjects = new ArrayCollection();
        $this->referrals = new ArrayCollection();
        $this->donationsMade = new ArrayCollection();
        $this->donationsReceived = new ArrayCollection();
        $this->withdrawals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        // If name is null, generate it from firstName and lastName
        if ($this->name === null && ($this->firstName !== null || $this->lastName !== null)) {
            return trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
        }
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
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

    public function getBackedCount(): int
    {
        return $this->backedCount;
    }

    public function setBackedCount(int $backedCount): self
    {
        $this->backedCount = $backedCount;
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
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    public function getVerificationTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->verificationTokenExpiresAt;
    }

    public function setVerificationTokenExpiresAt(?\DateTimeInterface $verificationTokenExpiresAt): self
    {
        $this->verificationTokenExpiresAt = $verificationTokenExpiresAt;
        return $this;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
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
}
