<?php

// Namespace declaration — this service lives in App\Service
namespace App\Service;

// Import entity classes used by this service
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
// Import the ReservationRepository for querying existing reservations
use App\Repository\ReservationRepository;
// Import EntityManagerInterface for saving data to the database
use Doctrine\ORM\EntityManagerInterface;

/**
 * ReservationManager — the core business logic service for handling hotel reservations.
 *
 * This service encapsulates all reservation-related logic that goes beyond simple CRUD:
 *   - Calculating the number of nights between two dates
 *   - Calculating the total price for a stay
 *   - Checking room availability (preventing double-booking)
 *   - Creating a new reservation with all business rules applied
 *
 * WHY A SERVICE?
 *   Controllers should be "thin" (little logic). Business rules belong in services
 *   so they can be reused across multiple controllers and are easier to test.
 */
class ReservationManager
{
    /**
     * Constructor — Symfony automatically injects these dependencies (autowiring).
     *
     * @param ReservationRepository $reservationRepository Used to check for overlapping reservations
     * @param EntityManagerInterface $entityManager Used to save new reservations to the database
     */
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Calculate the number of nights between check-in and check-out dates.
     *
     * Example: check-in Nov 1, check-out Nov 4 → 3 nights
     *
     * @param \DateTimeInterface $checkIn  Arrival date
     * @param \DateTimeInterface $checkOut Departure date
     * @return int Number of nights (minimum 1)
     */
    public function calculateNights(\DateTimeInterface $checkIn, \DateTimeInterface $checkOut): int
    {
        // diff() returns a DateInterval object — we extract the total number of days
        $days = (int) $checkIn->diff($checkOut)->days;

        // Ensure at least 1 night (in case of same-day dates due to rounding)
        return max(1, $days);
    }

    /**
     * Calculate the total price for a stay.
     * Formula: total = number of nights × price per night
     *
     * Example: 3 nights × 120€/night = 360€
     *
     * @param float $pricePerNight The room's price per night
     * @param \DateTimeInterface $checkIn  Arrival date
     * @param \DateTimeInterface $checkOut Departure date
     * @return float Total price, rounded to 2 decimal places
     */
    public function calculateTotal(
        float $pricePerNight,
        \DateTimeInterface $checkIn,
        \DateTimeInterface $checkOut
    ): float {
        $nights = $this->calculateNights($checkIn, $checkOut);

        // Round to 2 decimal places to avoid floating-point precision issues
        return round($pricePerNight * $nights, 2);
    }

    /**
     * Check if a room is available for booking during a specific date range.
     *
     * A room is UNAVAILABLE if:
     *   1. Its admin status is set to "unavailable" (e.g. under maintenance), OR
     *   2. There is an existing non-cancelled reservation that overlaps with the requested dates
     *
     * @param Room $room The room to check
     * @param \DateTimeInterface $checkIn  Desired check-in date
     * @param \DateTimeInterface $checkOut Desired check-out date
     * @param int|null $excludeReservationId Optional: skip this reservation ID in the overlap check
     *                                        (useful when editing an existing reservation)
     * @return bool True if the room is available, false if it's already booked or unavailable
     */
    public function isRoomAvailable(
        Room $room,
        \DateTimeInterface $checkIn,
        \DateTimeInterface $checkOut,
        ?int $excludeReservationId = null
    ): bool {
        // CHECK 1: Is the room marked as available by the admin?
        if (!$room->isAvailable()) {
            return false;
        }

        // CHECK 2: Are there any overlapping reservations for this room and date range?
        $overlapping = $this->reservationRepository->findOverlappingReservations(
            $room,
            $checkIn,
            $checkOut,
            $excludeReservationId
        );

        // If the list is empty, the room is available (no conflicts)
        return count($overlapping) === 0;
    }

    /**
     * Create and save a new reservation to the database.
     *
     * This method:
     *   1. Double-checks room availability (safety net against race conditions)
     *   2. Calculates the total price based on the room's nightly rate
     *   3. Creates a Reservation object with all required data
     *   4. Saves it to the database
     *
     * @param User $user The customer making the reservation
     * @param Room $room The room being booked
     * @param \DateTimeInterface $checkIn  Check-in date
     * @param \DateTimeInterface $checkOut Check-out date
     * @param int $guests Number of guests
     * @param string|null $notes Optional special requests from the customer
     * @return Reservation The created reservation (with reference code, total price, etc.)
     * @throws \InvalidArgumentException If the room is not available for the requested dates
     */
    public function createReservation(
        User $user,
        Room $room,
        \DateTimeInterface $checkIn,
        \DateTimeInterface $checkOut,
        int $guests,
        ?string $notes = null
    ): Reservation {
        // SAFETY CHECK: Verify availability one more time before creating
        // This prevents double-booking in case two users submit at the same time
        if (!$this->isRoomAvailable($room, $checkIn, $checkOut)) {
            throw new \InvalidArgumentException('Cette chambre n\'est pas disponible pour les dates sélectionnées.');
        }

        // Calculate the total price (nights × price per night)
        $totalPrice = $this->calculateTotal((float) $room->getPricePerNight(), $checkIn, $checkOut);

        // Create a new Reservation object and fill in all the details
        $reservation = new Reservation();
        $reservation->setUser($user);                                    // Who is booking
        $reservation->setRoom($room);                                    // Which room
        $reservation->setCheckIn($checkIn);                              // Arrival date
        $reservation->setCheckOut($checkOut);                            // Departure date
        $reservation->setGuests($guests);                                // Number of guests
        $reservation->setTotalPrice($totalPrice);                        // Calculated total price
        $reservation->setStatus(Reservation::STATUS_PENDING);            // New reservations start as "pending"
        $reservation->setPaymentStatus(Reservation::PAYMENT_ON_SITE);   // Payment is always on-site
        $reservation->setNotes($notes);                                  // Optional special requests

        // Save the reservation to the database
        $this->entityManager->persist($reservation);   // Tell Doctrine to track this new entity
        $this->entityManager->flush();                  // Execute the INSERT SQL query

        // Return the created reservation (the controller uses this to redirect to confirmation)
        return $reservation;
    }
}
