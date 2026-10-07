<?php

// Namespace declaration — this entity lives in App\Entity
namespace App\Entity;

// Import the ReservationRepository (links this entity to its custom repository class)
use App\Repository\ReservationRepository;
// Import Doctrine DBAL Types for column type definitions (DATE_MUTABLE, DECIMAL, TEXT)
use Doctrine\DBAL\Types\Types;
// Import Doctrine ORM Mapping — provides attributes like #[ORM\Column], #[ORM\Entity], etc.
use Doctrine\ORM\Mapping as ORM;
// Import Symfony Validator Constraints for automatic form/data validation
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reservation Entity — represents a hotel room reservation (a booking).
 *
 * This class is mapped to the "reservations" table in the database.
 * Each reservation links a User (customer) to a Room for a specific date range.
 *
 * Database table: reservations
 * Repository: ReservationRepository (for custom database queries)
 */
#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservations')]
class Reservation
{
    // ========== STATUS CONSTANTS ==========
    // These define the possible states of a reservation throughout its lifecycle
    public const STATUS_PENDING = 'pending';       // Reservation is waiting for admin confirmation
    public const STATUS_CONFIRMED = 'confirmed';   // Reservation has been confirmed by admin
    public const STATUS_CANCELLED = 'cancelled';   // Reservation has been cancelled (by user or admin)
    public const STATUS_COMPLETED = 'completed';   // The guest's stay is finished

    // ========== PAYMENT STATUS CONSTANTS ==========
    // Payment options — in this hotel, only on-site payment is available
    public const PAYMENT_ON_SITE = 'on_site';  // Customer will pay at the hotel upon arrival
    public const PAYMENT_PAID = 'paid';        // Customer has already paid (marked by admin)

    // ========== DATABASE COLUMNS ==========

    /**
     * @var int|null Primary key — auto-incremented unique ID for each reservation
     */
    #[ORM\Id]                    // Marks this column as the primary key
    #[ORM\GeneratedValue]        // The database auto-generates this value (auto-increment)
    #[ORM\Column]                // Maps this property to a database column
    private ?int $id = null;

    /**
     * @var string|null Unique reference code for the reservation (e.g. "DR-A1B2C3")
     * Used as a human-readable booking identifier instead of the numeric ID
     */
    #[ORM\Column(length: 30, unique: true)]   // VARCHAR(30), must be unique across all reservations
    private ?string $reference = null;

    /**
     * @var User|null The customer who made this reservation
     * This is a Many-to-One relationship: many reservations can belong to one user
     */
    #[ORM\ManyToOne(inversedBy: 'reservations')]   // Links to User entity (inverse side: User->reservations)
    #[ORM\JoinColumn(nullable: false)]              // The user_id foreign key cannot be NULL
    private ?User $user = null;

    /**
     * @var Room|null The room that was booked
     * This is a Many-to-One relationship: many reservations can exist for one room
     */
    #[ORM\ManyToOne(inversedBy: 'reservations')]    // Links to Room entity (inverse side: Room->reservations)
    #[ORM\JoinColumn(nullable: false)]               // The room_id foreign key cannot be NULL
    #[Assert\NotNull(message: 'Veuillez sélectionner une chambre.')]  // Validation: room is required
    private ?Room $room = null;

    /**
     * @var \DateTimeInterface|null Check-in date (arrival date)
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]    // Stored as a DATE in the database (no time component)
    #[Assert\NotNull(message: 'La date d\'arrivée est obligatoire.')]  // Validation: required field
    private ?\DateTimeInterface $checkIn = null;

    /**
     * @var \DateTimeInterface|null Check-out date (departure date)
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de départ est obligatoire.')]
    // Validation: check-out must be after check-in
    #[Assert\GreaterThan(propertyPath: 'checkIn', message: 'La date de départ doit être postérieure à la date d\'arrivée.')]
    private ?\DateTimeInterface $checkOut = null;

    /**
     * @var int|null Number of guests for this reservation
     */
    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de personnes est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de personnes doit être supérieur à zéro.')]  // Must be ≥ 1
    private ?int $guests = 1;

    /**
     * @var string|null Total price for the entire stay (number of nights × price per night)
     * Stored as DECIMAL(10,2) in the database for precise monetary calculations
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    /**
     * @var string|null Current reservation status (pending, confirmed, cancelled, completed)
     * Defaults to "pending" when a new reservation is created
     */
    #[ORM\Column(length: 30)]
    private ?string $status = self::STATUS_PENDING;

    /**
     * @var string|null Payment status (on_site or paid)
     * Defaults to "on_site" — customer pays at the hotel
     */
    #[ORM\Column(length: 30)]
    private ?string $paymentStatus = self::PAYMENT_ON_SITE;

    /**
     * @var string|null Optional notes/special requests from the customer
     * (e.g. "Baby crib needed", "Late arrival", "Anniversary celebration")
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]   // TEXT column, can be NULL
    private ?string $notes = null;

    /**
     * @var \DateTimeImmutable|null When the reservation was created
     * Uses DateTimeImmutable to prevent accidental modification of the timestamp
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // ========== CONSTRUCTOR ==========

    /**
     * Constructor — sets default values when creating a new Reservation object.
     */
    public function __construct()
    {
        // Set default status to "pending" (waiting for admin confirmation)
        $this->status = self::STATUS_PENDING;
        // Set default payment to "on site" (customer pays at the hotel)
        $this->paymentStatus = self::PAYMENT_ON_SITE;
        // Record the creation timestamp
        $this->createdAt = new \DateTimeImmutable();
        // Generate a unique reference code like "DR-A1B2C3"
        // uniqid() generates a unique string based on the current time in microseconds
        // We take the last 6 characters and prefix them with "DR-"
        $this->reference = 'DR-' . strtoupper(substr(uniqid(), -6));
    }

    // ========== GETTER AND SETTER METHODS ==========
    // Each property has a getter (to read the value) and a setter (to change the value)
    // Setters return $this (fluent interface) to allow method chaining:
    //   $reservation->setUser($user)->setRoom($room)->setGuests(2);

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getCheckIn(): ?\DateTimeInterface
    {
        return $this->checkIn;
    }

    public function setCheckIn(?\DateTimeInterface $checkIn): static
    {
        $this->checkIn = $checkIn;

        return $this;
    }

    public function getCheckOut(): ?\DateTimeInterface
    {
        return $this->checkOut;
    }

    public function setCheckOut(?\DateTimeInterface $checkOut): static
    {
        $this->checkOut = $checkOut;

        return $this;
    }

    public function getGuests(): ?int
    {
        return $this->guests;
    }

    public function setGuests(int $guests): static
    {
        $this->guests = $guests;

        return $this;
    }

    /**
     * Get the total price as a float.
     * The database stores it as a string (DECIMAL type), so we convert it here.
     */
    public function getTotalPrice(): ?float
    {
        return $this->totalPrice !== null ? (float) $this->totalPrice : null;
    }

    /**
     * Set the total price — accepts both float and string values.
     * Stores internally as a string to match the DECIMAL database column.
     */
    public function setTotalPrice(float|string $totalPrice): static
    {
        $this->totalPrice = (string) $totalPrice;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get a human-readable French label for the current reservation status.
     * Uses PHP 8.0+ match expression (similar to switch but more concise).
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',       // Pending
            self::STATUS_CONFIRMED => 'Confirmée',      // Confirmed
            self::STATUS_CANCELLED => 'Annulée',        // Cancelled
            self::STATUS_COMPLETED => 'Terminée',       // Completed
            default => ucfirst((string) $this->status), // Fallback: capitalize the raw status value
        };
    }

    /**
     * Get the CSS badge class for the current status (used in Twig templates for styling).
     * Each status has a different color badge.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-warning',     // Yellow — waiting
            self::STATUS_CONFIRMED => 'badge-success',   // Green — confirmed
            self::STATUS_CANCELLED => 'badge-danger',    // Red — cancelled
            self::STATUS_COMPLETED => 'badge-secondary', // Gray — finished
            default => 'badge-light',                    // Default fallback
        };
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    /**
     * Get a human-readable French label for the payment status.
     */
    public function getPaymentStatusLabel(): string
    {
        return match ($this->paymentStatus) {
            self::PAYMENT_ON_SITE => 'Paiement sur place',  // Payment at the hotel
            self::PAYMENT_PAID => 'Payé',                    // Already paid
            default => ucfirst((string) $this->paymentStatus),
        };
    }

    /**
     * Get the CSS badge class for the payment status.
     */
    public function getPaymentBadgeClass(): string
    {
        return match ($this->paymentStatus) {
            self::PAYMENT_ON_SITE => 'badge-info',       // Blue — payment pending (at hotel)
            self::PAYMENT_PAID => 'badge-success',       // Green — paid
            default => 'badge-secondary',
        };
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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

    /**
     * Calculate the total number of nights between check-in and check-out dates.
     * Returns 0 if either date is not set, otherwise returns at least 1 night.
     */
    public function getNumberOfNights(): int
    {
        // If either date is missing, return 0
        if (!$this->checkIn || !$this->checkOut) {
            return 0;
        }

        // Calculate the difference between the two dates
        $diff = $this->checkIn->diff($this->checkOut);
        // Return the number of days (minimum 1 night)
        return max(1, (int) $diff->days);
    }
}
