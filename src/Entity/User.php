<?php

// Namespace declaration — this entity lives in App\Entity
namespace App\Entity;

// Import the UserRepository (links this entity to its custom repository class)
use App\Repository\UserRepository;
// Import Doctrine Collection classes for one-to-many relationships
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// Import Doctrine ORM Mapping for database mapping attributes
use Doctrine\ORM\Mapping as ORM;
// Import UniqueEntity constraint to ensure no two users share the same email
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
// Import Symfony security interfaces that this class must implement
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
// Import Symfony Validator Constraints for form/data validation
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User Entity — represents a registered user (customer or admin).
 *
 * This class is mapped to the "users" table in the database.
 * It implements Symfony's UserInterface and PasswordAuthenticatedUserInterface
 * so it can be used by Symfony's security system for authentication.
 *
 * Database table: users
 * Repository: UserRepository
 * Unique constraint: email must be unique (only one account per email)
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ========== DATABASE COLUMNS ==========

    /**
     * @var int|null Primary key — auto-incremented unique ID
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var string|null User's email address — used as the login identifier
     * Must be unique across all users (enforced by the UniqueEntity constraint above)
     */
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre adresse email.')]
    #[Assert\Email(message: 'Veuillez saisir une adresse email valide.')]
    private ?string $email = null;

    /**
     * @var list<string> The user's roles (e.g. ['ROLE_USER'] or ['ROLE_ADMIN'])
     * Stored as JSON in the database. Every user automatically gets ROLE_USER.
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password — NEVER stores the plain-text password!
     * The password is hashed using bcrypt or argon2 by Symfony's PasswordHasher
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var string|null User's first name
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre prénom.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.')]
    private ?string $firstName = null;

    /**
     * @var string|null User's last name
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre nom.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.')]
    private ?string $lastName = null;

    /**
     * @var string|null User's phone number
     */
    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre numéro de téléphone.')]
    private ?string $phone = null;

    /**
     * @var \DateTimeImmutable|null When the user account was created
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Reservation> All reservations made by this user
     * One user can have many reservations (one-to-many relationship)
     * orphanRemoval: if a reservation is removed from this collection, it's deleted from the DB
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reservation::class, orphanRemoval: true)]
    private Collection $reservations;

    // ========== CONSTRUCTOR ==========

    /**
     * Constructor — sets default values when creating a new User object.
     */
    public function __construct()
    {
        // Every new user starts with the ROLE_USER role
        $this->roles = ['ROLE_USER'];
        // Record the registration timestamp
        $this->createdAt = new \DateTimeImmutable();
        // Initialize the reservations collection as empty
        $this->reservations = new ArrayCollection();
    }

    // ========== GETTER AND SETTER METHODS ==========

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * Returns the unique identifier used by Symfony to identify this user.
     * In our case, the email serves as the login username.
     *
     * Required by UserInterface.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Get the user's roles.
     * Always includes ROLE_USER to ensure every user has at least the basic role.
     *
     * Required by UserInterface.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Guarantee every user at least has ROLE_USER (even if the DB record is empty)
        $roles[] = 'ROLE_USER';

        // Remove duplicates and re-index the array
        return array_values(array_unique($roles));
    }

    /**
     * Set the user's roles.
     * @param list<string> $roles Array of role strings (e.g. ['ROLE_ADMIN'])
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get the hashed password.
     * Required by PasswordAuthenticatedUserInterface.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set the hashed password.
     * WARNING: Never pass a plain-text password here — always hash it first!
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Clear any temporary sensitive data stored on the user.
     * Called by Symfony after authentication is complete.
     * Currently empty because we don't store any temporary sensitive data.
     *
     * Required by UserInterface.
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // Example: $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get the user's full name by combining first and last name.
     * Used in templates to display "Karim Ben Salah" instead of just "Karim".
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    // ========== RESERVATION RELATIONSHIP METHODS ==========

    /**
     * Get all reservations made by this user.
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    /**
     * Add a reservation to this user.
     * Also sets the user reference on the reservation (bidirectional relationship).
     */
    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setUser($this);
        }

        return $this;
    }

    /**
     * Remove a reservation from this user.
     * Also clears the user reference on the reservation if it still points to this user.
     */
    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // Set the owning side to null (unless already changed to another user)
            if ($reservation->getUser() === $this) {
                $reservation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Check if this user has the ROLE_ADMIN role.
     * Used in templates and controllers to show/hide admin-specific features.
     */
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles(), true);
    }
}
