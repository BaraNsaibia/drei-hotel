<?php

// Namespace declaration
namespace App\Repository;

// Import the Reservation entity (needed for date-based availability queries)
use App\Entity\Reservation;
// Import the Room entity (the entity this repository manages)
use App\Entity\Room;
// Import the base repository class
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
// Import ManagerRegistry
use Doctrine\Persistence\ManagerRegistry;

/**
 * RoomRepository — custom database queries for the Room entity.
 *
 * Provides methods to:
 *   - Find featured rooms for the home page
 *   - Search/filter rooms by type, capacity, price
 *   - Find rooms available for a specific date range (excluding already-booked rooms)
 *
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    /**
     * Constructor — tells Doctrine that this repository manages the Room entity.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    /**
     * Find featured rooms to display on the home page.
     * Returns the cheapest available rooms (sorted by price ascending).
     *
     * @param int $limit Maximum number of rooms to return (default: 3)
     * @return Room[] Array of featured rooms
     */
    public function findFeaturedRooms(int $limit = 3): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')                      // Only available rooms
            ->setParameter('status', Room::STATUS_AVAILABLE)
            ->orderBy('r.pricePerNight', 'ASC')                // Cheapest first
            ->setMaxResults($limit)                             // Limit results
            ->getQuery()
            ->getResult();
    }

    /**
     * Search rooms with optional filters (type, capacity, max price).
     * Used on the /chambres page when NO date range is specified.
     *
     * Only shows rooms with status = "available".
     * Each filter is optional — if null, that filter is skipped.
     *
     * @param string|null $type     Filter by room type (standard, deluxe, suite, family)
     * @param int|null    $capacity Filter by minimum capacity (e.g. 2 = rooms for 2+ guests)
     * @param float|null  $maxPrice Filter by maximum price per night
     * @return Room[] Array of rooms matching the filters
     */
    public function findWithFilters(?string $type = null, ?int $capacity = null, ?float $maxPrice = null): array
    {
        // Start building the query — only include available rooms
        $qb = $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', Room::STATUS_AVAILABLE);

        // Apply type filter if provided
        if (!empty($type)) {
            $qb->andWhere('r.type = :type')
               ->setParameter('type', $type);
        }

        // Apply capacity filter if provided (rooms that can hold at least this many guests)
        if (!empty($capacity)) {
            $qb->andWhere('r.capacity >= :capacity')
               ->setParameter('capacity', $capacity);
        }

        // Apply max price filter if provided
        if (!empty($maxPrice)) {
            $qb->andWhere('r.pricePerNight <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Sort results by price (cheapest first) and execute the query
        return $qb->orderBy('r.pricePerNight', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all rooms available for a specific date range.
     * This is the most complex query — it excludes rooms that have overlapping reservations.
     *
     * HOW IT WORKS (2-step process):
     *   1. Find all room IDs that have a conflicting (overlapping) active reservation
     *   2. Exclude those room IDs from the results
     *
     * @param \DateTimeInterface $checkIn  Desired check-in date
     * @param \DateTimeInterface $checkOut Desired check-out date
     * @param int|null    $capacity Optional: minimum capacity filter
     * @param string|null $type     Optional: room type filter
     * @param float|null  $maxPrice Optional: maximum price filter
     * @return Room[] Array of available rooms for the requested dates
     */
    public function findAvailableRoomsForDates(
        \DateTimeInterface $checkIn,
        \DateTimeInterface $checkOut,
        ?int $capacity = null,
        ?string $type = null,
        ?float $maxPrice = null
    ): array {
        // === STEP 1: Find room IDs that are UNAVAILABLE (have overlapping reservations) ===
        // This sub-query looks at the reservations table for any non-cancelled reservation
        // whose dates overlap with the requested date range
        $overlappingQb = $this->getEntityManager()->createQueryBuilder();
        $overlappingResults = $overlappingQb->select('DISTINCT IDENTITY(res.room) AS roomId')  // Get unique room IDs
            ->from(Reservation::class, 'res')
            ->where('res.status != :cancelled')            // Exclude cancelled reservations
            ->andWhere('res.checkIn < :checkOut')          // Overlap condition: existing check-in before requested check-out
            ->andWhere('res.checkOut > :checkIn')          // Overlap condition: existing check-out after requested check-in
            ->setParameter('cancelled', Reservation::STATUS_CANCELLED)
            ->setParameter('checkIn', $checkIn)
            ->setParameter('checkOut', $checkOut)
            ->getQuery()
            ->getScalarResult();                           // Returns simple array of values

        // Extract the room IDs into a flat array (e.g. [1, 3, 5])
        $unavailableRoomIds = array_column($overlappingResults, 'roomId');

        // === STEP 2: Query rooms, EXCLUDING the unavailable ones ===
        $qb = $this->createQueryBuilder('r')
            ->where('r.status = :available')               // Only rooms marked as "available"
            ->setParameter('available', Room::STATUS_AVAILABLE);

        // Exclude rooms that have conflicting reservations
        if (!empty($unavailableRoomIds)) {
            $qb->andWhere('r.id NOT IN (:unavailableIds)')
               ->setParameter('unavailableIds', $unavailableRoomIds);
        }

        // Apply optional filters (same as findWithFilters)
        if (!empty($capacity)) {
            $qb->andWhere('r.capacity >= :capacity')
               ->setParameter('capacity', $capacity);
        }

        if (!empty($type)) {
            $qb->andWhere('r.type = :type')
               ->setParameter('type', $type);
        }

        if (!empty($maxPrice)) {
            $qb->andWhere('r.pricePerNight <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Sort by price (cheapest first) and return results
        return $qb->orderBy('r.pricePerNight', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
