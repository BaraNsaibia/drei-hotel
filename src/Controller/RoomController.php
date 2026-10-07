<?php

// Namespace declaration — this controller lives in App\Controller
namespace App\Controller;

// Import the Room entity
use App\Entity\Room;
// Import the ReservationType form (used on the room detail page for quick booking)
use App\Form\ReservationType;
// Import the RoomRepository to query rooms from the database
use App\Repository\RoomRepository;
// Import the base controller class
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request to read URL query parameters (filters)
use Symfony\Component\HttpFoundation\Request;
// Import Response to represent the HTTP response
use Symfony\Component\HttpFoundation\Response;
// Import Route for URL mapping
use Symfony\Component\Routing\Annotation\Route;

/**
 * RoomController — handles the public room browsing pages.
 * Contains two actions:
 *   1. index() — List all rooms with optional filters (type, capacity, price, dates)
 *   2. show()  — Display a single room's detail page
 */
class RoomController extends AbstractController
{
    /**
     * Room listing page — shows all available rooms with optional search filters.
     * Route: GET /chambres
     * Name: app_rooms
     *
     * Supports the following query parameters (filters):
     *   - type       : room type (standard, deluxe, suite, family)
     *   - capacity   : minimum number of guests
     *   - max_price  : maximum price per night
     *   - check_in   : desired check-in date (format: YYYY-MM-DD)
     *   - check_out  : desired check-out date (format: YYYY-MM-DD)
     *
     * @param Request $request Contains the filter values from the URL query string
     * @param RoomRepository $roomRepository Used to query rooms from the database
     * @return Response The rendered rooms listing page
     */
    #[Route('/chambres', name: 'app_rooms')]
    public function index(Request $request, RoomRepository $roomRepository): Response
    {
        // Read all filter values from the URL query parameters
        // Example URL: /chambres?type=deluxe&capacity=2&max_price=200&check_in=2026-11-01&check_out=2026-11-05
        $type = $request->query->get('type');                                                    // Room type filter (or null)
        $capacity = $request->query->getInt('capacity') ?: null;                                 // Minimum capacity (or null)
        $maxPrice = $request->query->get('max_price') ? (float) $request->query->get('max_price') : null;  // Max price (or null)
        $checkInStr = $request->query->get('check_in');                                          // Check-in date string (or null)
        $checkOutStr = $request->query->get('check_out');                                        // Check-out date string (or null)

        // Initialize date objects as null
        $checkIn = null;
        $checkOut = null;

        // Try to parse the date strings into DateTime objects
        if (!empty($checkInStr) && !empty($checkOutStr)) {
            try {
                $checkIn = new \DateTime($checkInStr);
                $checkOut = new \DateTime($checkOutStr);
            } catch (\Exception) {
                // If dates are in an invalid format, just ignore them (keep null)
            }
        }

        // Choose which query to run based on whether valid dates were provided
        if ($checkIn && $checkOut && $checkOut > $checkIn) {
            // DATE-BASED SEARCH: Find rooms that have NO overlapping reservations for the given dates
            // Also applies other filters (capacity, type, maxPrice) if provided
            $rooms = $roomRepository->findAvailableRoomsForDates($checkIn, $checkOut, $capacity, $type, $maxPrice);
        } else {
            // STANDARD SEARCH: Find all available rooms with optional type/capacity/price filters
            $rooms = $roomRepository->findWithFilters($type, $capacity, $maxPrice);
        }

        // Render the room listing template and pass all the data + selected filter values
        // (the selected values are passed back so the form inputs stay filled after filtering)
        return $this->render('room/index.html.twig', [
            'rooms' => $rooms,
            'selected_type' => $type,
            'selected_capacity' => $capacity,
            'selected_max_price' => $maxPrice,
            'selected_check_in' => $checkInStr,
            'selected_check_out' => $checkOutStr,
        ]);
    }

    /**
     * Room detail page — shows full details about a single room.
     * Route: GET /chambres/{id}   (where {id} must be a number)
     * Name: app_room_show
     *
     * @param Room $room Automatically fetched from the database by Symfony using {id} (ParamConverter)
     * @return Response The rendered room detail page
     */
    #[Route('/chambres/{id}', name: 'app_room_show', requirements: ['id' => '\d+'])]
    public function show(Room $room): Response
    {
        // Create an empty reservation form to display on the room detail page
        // This lets the user start booking directly from this page
        $form = $this->createForm(ReservationType::class);

        // Render the room detail template with the room data and the booking form
        return $this->render('room/show.html.twig', [
            'room' => $room,
            'form' => $form->createView(),
        ]);
    }
}
