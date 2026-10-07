<?php

// Namespace declaration — this repository lives in App\Repository
namespace App\Repository;

// Import the Reservation entity (the entity this repository manages)
use App\Entity\Reservation;
// Import the Room entity (used in queries to filter by room)
use App\Entity\Room;
// Import the base repository class provided by Doctrine
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
// Import ManagerRegistry (provides access to the Doctrine entity manager)
use Doctrine\Persistence\ManagerRegistry;

/**
 * ReservationRepository — custom database queries for the Reservation entity.
 *
 * This class extends ServiceEntityRepository, which already provides basic methods:
 *   - find($id)            : Find one reservation by ID
 *   - findOneBy([...])     : Find one reservation matching criteria
 *   - findBy([...])        : Find multiple reservations matching criteria
 *   - findAll()            : Get all reservations
 *   - count([...])         : Count reservations matching criteria
 *
 * The methods below are CUSTOM queries specific to the hotel's business logic.
 *
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    /**
     * Constructor — tells Doctrine that this repository manages the Reservation entity.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Find all active reservations that overlap with a given date range for a specific room.
     * This is used to prevent DOUBLE-BOOKING (two guests booking the same room for overlapping dates).
     *
     * Overlap logic:
     *   Two date ranges [A, B] and [C, D] overlap if: A < D AND B > C
     *   In our case: existingCheckIn < requestedCheckOut AND existingCheckOut > requestedCheckIn
     *
     * Only "active" reservations are considered (cancelled reservations are excluded).
     *
     * @param Room $room The room to check availability for
     * @param \DateTimeInterface $checkIn The requested check-in date
     * @param \DateTimeInterface $checkOut The requested check-out date
     * @param int|null $excludeReservationId Optional: exclude a specific reservation ID
     *                                        (used when editing an existing reservation)
     * @return Reservation[] Array of overlapping reservations (empty = room is available)
     */
    public function findOverlappingReservations(
        Room $room,
        \DateTimeInterface $checkIn,
        \DateTimeInterface $checkOut,
        ?int $excludeReservationId = null
    ): array {
        // Build a Doctrine QueryBuilder query (DQL)
        // 'r' is an alias for the Reservation entity
        $qb = $this->createQueryBuilder('r')
            ->where('r.room = :room')                    // Filter: same room
            ->andWhere('r.status != :cancelled')          // Filter: exclude cancelled reservations
            ->andWhere('r.checkIn < :checkOut')           // Overlap condition part 1
            ->andWhere('r.checkOut > :checkIn')           // Overlap condition part 2
            ->setParameter('room', $room)
            ->setParameter('cancelled', Reservation::STATUS_CANCELLED)
            ->setParameter('checkIn', $checkIn)
            ->setParameter('checkOut', $checkOut);

        // Optionally exclude a specific reservation (useful when updating an existing booking)
        if ($excludeReservationId !== null) {
            $qb->andWhere('r.id != :excludeId')
               ->setParameter('excludeId', $excludeReservationId);
        }

        // Execute the query and return the results as an array of Reservation objects
        return $qb->getQuery()->getResult();
    }

    /**
     * Get the most recent reservations (for the admin dashboard "latest reservations" table).
     *
     * @param int $limit Maximum number of reservations to return (default: 5)
     * @return Reservation[] Array of the most recent reservations
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')    // Newest first
            ->setMaxResults($limit)              // Limit the number of results
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate the total revenue from all PAID reservations.
     * Uses SQL SUM() to add up all totalPrice values where paymentStatus = 'paid'.
     *
     * @return float Total revenue in euros (0.0 if no paid reservations exist)
     */
    public function calculateTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.totalPrice)')          // SQL: SELECT SUM(total_price)
            ->where('r.paymentStatus = :paid')     // WHERE payment_status = 'paid'
            ->setParameter('paid', Reservation::PAYMENT_PAID)
            ->getQuery()
            ->getSingleScalarResult();             // Returns a single value (not an entity)

        // Return 0.0 if no paid reservations exist (SUM returns null in that case)
        return $result !== null ? (float) $result : 0.0;
    }

    /**
     * Count the number of reservations with a specific status.
     * Used on the admin dashboard to show counts per status (e.g. "5 pending, 12 confirmed").
     *
     * @param string $status The status to count (e.g. Reservation::STATUS_PENDING)
     * @return int Number of reservations with that status
     */
    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')              // SQL: SELECT COUNT(id)
            ->where('r.status = :status')        // WHERE status = ?
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
