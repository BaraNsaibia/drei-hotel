<?php

// Namespace declaration — this entity lives in App\Entity
namespace App\Entity;

// Import the RoomRepository (links this entity to its custom repository class)
use App\Repository\RoomRepository;
// Import Doctrine Collection classes for handling one-to-many relationships
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// Import Doctrine DBAL Types for column type definitions
use Doctrine\DBAL\Types\Types;
// Import Doctrine ORM Mapping — provides attributes for database mapping
use Doctrine\ORM\Mapping as ORM;
// Import Symfony Validator Constraints for automatic data validation
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Room Entity — represents a hotel room.
 *
 * This class is mapped to the "rooms" table in the database.
 * Each room has a name, type, description, price, capacity, amenities, and an image.
 * A room can have many reservations (one-to-many relationship with Reservation).
 *
 * Database table: rooms
 * Repository: RoomRepository (for custom database queries)
 */
#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[ORM\Table(name: 'rooms')]
class Room
{
    // ========== STATUS CONSTANTS ==========
    // Define whether a room can be booked or not
    public const STATUS_AVAILABLE = 'available';       // Room is open for booking
    public const STATUS_UNAVAILABLE = 'unavailable';   // Room is closed (e.g. maintenance, renovation)

    // ========== ROOM TYPE CONSTANTS ==========
    // Define the different categories of rooms offered by the hotel
    public const TYPE_STANDARD = 'standard';   // Basic room at the lowest price
    public const TYPE_DELUXE = 'deluxe';       // Upgraded room with more amenities
    public const TYPE_SUITE = 'suite';         // Premium large room (the best the hotel offers)
    public const TYPE_FAMILY = 'family';       // Room designed for families with multiple beds

    // ========== DATABASE COLUMNS ==========

    /**
     * @var int|null Primary key — auto-incremented unique ID
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var string|null Display name of the room (e.g. "Chambre Deluxe")
     */
    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le nom de la chambre est obligatoire.')]
    #[Assert\Length(min: 3, max: 150, minMessage: 'Le nom doit comporter au moins {{ limit }} caractères.')]
    private ?string $name = null;

    /**
     * @var string|null Room type — one of the TYPE_ constants (standard, deluxe, suite, family)
     */
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de chambre est obligatoire.')]
    private ?string $type = null;

    /**
     * @var string|null Full description of the room (displayed on the room detail page)
     */
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description de la chambre est obligatoire.')]
    private ?string $description = null;

    /**
     * @var string|null Price per night in euros — stored as DECIMAL(10,2) for precision
     * Stored as string internally because that's how Doctrine handles DECIMAL columns
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix par nuit est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être un nombre positif.')]
    private ?string $pricePerNight = null;

    /**
     * @var int|null Maximum number of guests the room can accommodate
     */
    #[ORM\Column]
    #[Assert\NotBlank(message: 'La capacité est obligatoire.')]
    #[Assert\Positive(message: 'La capacité doit être au moins de 1 personne.')]
    private ?int $capacity = 2;

    /**
     * @var string|null Filename or URL of the room's photo
     * (e.g. "room-deluxe.jpg" stored in public/images/rooms/)
     */
    #[ORM\Column(length: 255, nullable: true)]   // Optional field (can be NULL)
    private ?string $image = null;

    /**
     * @var string|null Availability status (available or unavailable)
     * Defaults to "available" when a new room is created
     */
    #[ORM\Column(length: 30)]
    private ?string $status = self::STATUS_AVAILABLE;

    /**
     * @var array|null List of room amenities/features
     * Stored as JSON in the database (e.g. ["Wi-Fi gratuit", "Climatisation", "Mini-bar"])
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $amenities = [];

    /**
     * @var int|null Room surface area in square meters (m²)
     */
    #[ORM\Column(nullable: true)]   // Optional field
    private ?int $surface = null;

    /**
     * @var string|null Description of the bed type (e.g. "1 grand lit King Size")
     */
    #[ORM\Column(length: 100, nullable: true)]   // Optional field
    private ?string $bedType = null;

    /**
     * @var \DateTimeImmutable|null When the room was created in the system
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var \DateTimeImmutable|null When the room was last updated (null if never updated)
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Reservation> All reservations linked to this room
     * One room can have many reservations (one-to-many relationship)
     * orphanRemoval: if a reservation is removed from this collection, it's also deleted from the DB
     */
    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Reservation::class, orphanRemoval: true)]
    private Collection $reservations;

    // ========== CONSTRUCTOR ==========

    /**
     * Constructor — sets default values when creating a new Room object.
     */
    public function __construct()
    {
        // Set default status to "available"
        $this->status = self::STATUS_AVAILABLE;
        // Record the creation timestamp
        $this->createdAt = new \DateTimeImmutable();
        // Initialize the reservations collection as an empty array-like object
        $this->reservations = new ArrayCollection();
        // Set default amenities that every room comes with
        $this->amenities = ['Wi-Fi gratuit', 'Climatisation', 'Télévision HD', 'Salle de bain privée'];
    }

    // ========== GETTER AND SETTER METHODS ==========
    // Standard getters/setters for each property
    // Setters return $this for fluent method chaining

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the human-readable French name for the room type.
     * Translates internal type codes (e.g. "deluxe") to display labels (e.g. "Chambre Deluxe").
     */
    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_STANDARD => 'Chambre Standard',
            self::TYPE_DELUXE => 'Chambre Deluxe',
            self::TYPE_SUITE => 'Suite Drei',
            self::TYPE_FAMILY => 'Chambre Familiale',
            default => ucfirst((string) $this->type),   // Fallback: capitalize the raw type
        };
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

    /**
     * Get the price per night as a float.
     * Converts from the string stored in the database.
     */
    public function getPricePerNight(): ?float
    {
        return $this->pricePerNight !== null ? (float) $this->pricePerNight : null;
    }

    /**
     * Set the price per night — accepts both float and string.
     * Stored as string to match the DECIMAL database column.
     */
    public function setPricePerNight(float|string $pricePerNight): static
    {
        $this->pricePerNight = (string) $pricePerNight;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

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
     * Check if the room is currently available for booking.
     * Returns true if the status is "available", false otherwise.
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Get the human-readable French label for the room's availability status.
     */
    public function getStatusLabel(): string
    {
        return $this->isAvailable() ? 'Disponible' : 'Indisponible';
    }

    /**
     * Get the list of amenities. Returns an empty array if null.
     */
    public function getAmenities(): ?array
    {
        return $this->amenities ?? [];
    }

    public function setAmenities(?array $amenities): static
    {
        $this->amenities = $amenities;

        return $this;
    }

    public function getSurface(): ?int
    {
        return $this->surface;
    }

    public function setSurface(?int $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    public function getBedType(): ?string
    {
        return $this->bedType;
    }

    public function setBedType(?string $bedType): static
    {
        $this->bedType = $bedType;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    // ========== RESERVATION RELATIONSHIP METHODS ==========

    /**
     * Get all reservations for this room.
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    /**
     * Add a reservation to this room.
     * Also sets the room reference on the reservation (bidirectional relationship).
     */
    public function addReservation(Reservation $reservation): static
    {
        // Only add if not already in the collection (prevents duplicates)
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            // Set this room on the reservation (owning side of the relationship)
            $reservation->setRoom($this);
        }

        return $this;
    }

    /**
     * Remove a reservation from this room.
     * Also clears the room reference on the reservation if it still points to this room.
     */
    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // Set the owning side to null (unless the reservation was already moved to another room)
            if ($reservation->getRoom() === $this) {
                $reservation->setRoom(null);
            }
        }

        return $this;
    }

    /**
     * String representation of the room — used when displaying a Room object as text.
     * For example, in dropdown menus or admin lists.
     */
    public function __toString(): string
    {
        return $this->name ?? 'Chambre';
    }
}
