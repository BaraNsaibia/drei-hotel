<?php

// Namespace declaration — this controller lives in App\Controller
namespace App\Controller;

// Import necessary entity classes
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
// Import the reservation form type (defines the form fields)
use App\Form\ReservationType;
// Import the repository to search reservations in the database
use App\Repository\ReservationRepository;
// Import the service that handles reservation business logic (availability, price calculation, creation)
use App\Service\ReservationManager;
// Import the base controller class
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request and Response for handling HTTP
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// Import Route for URL mapping
use Symfony\Component\Routing\Annotation\Route;
// Import IsGranted to restrict access to logged-in users
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ReservationController — handles the room booking process for customers.
 * Contains two actions:
 *   1. new()          — Display and process the reservation form
 *   2. confirmation() — Show the booking confirmation page after a successful reservation
 */
class ReservationController extends AbstractController
{
    /**
     * Create a new reservation — shows the booking form and processes it when submitted.
     * Route: GET + POST /reserver/{id}   (where {id} is the room ID, must be a number)
     * Name: app_reservation_new
     * Access: Only logged-in users (ROLE_USER)
     *
     * @param Room $room Automatically fetched from the database using {id} in the URL (ParamConverter)
     * @param Request $request Contains form data and query parameters
     * @param ReservationManager $reservationManager Service that handles availability checks and reservation creation
     * @return Response The rendered form page or a redirect to the confirmation page
     */
    #[Route('/reserver/{id}', name: 'app_reservation_new', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Room $room,
        Request $request,
        ReservationManager $reservationManager
    ): Response {
        /** @var User $user — Get the currently logged-in user */
        $user = $this->getUser();

        // Check if the room is marked as available by the admin
        // If the room is unavailable (e.g. under maintenance), redirect to the rooms list
        if (!$room->isAvailable()) {
            $this->addFlash('danger', 'Cette chambre n\'est pas disponible à la réservation.');
            return $this->redirectToRoute('app_rooms');
        }

        // Create a new empty Reservation object and pre-fill it with the selected room and user
        $reservation = new Reservation();
        $reservation->setRoom($room);
        $reservation->setUser($user);

        // Pre-fill the form dates from URL query parameters if they were passed
        // (e.g. when the user clicks "Book" from the search results with dates already selected)
        if ($request->query->get('check_in')) {
            try {
                $reservation->setCheckIn(new \DateTime($request->query->get('check_in')));
            } catch (\Exception) {
                // Silently ignore invalid date format — the user will just see an empty field
            }
        }
        if ($request->query->get('check_out')) {
            try {
                $reservation->setCheckOut(new \DateTime($request->query->get('check_out')));
            } catch (\Exception) {
                // Silently ignore invalid date format
            }
        }
        // Pre-fill the number of guests, capped at the room's maximum capacity
        if ($request->query->getInt('guests')) {
            $reservation->setGuests(min($room->getCapacity(), $request->query->getInt('guests')));
        }

        // Create the Symfony form and bind it to the reservation object
        $form = $this->createForm(ReservationType::class, $reservation);
        // Process the HTTP request — if the form was submitted, this fills the $reservation with form data
        $form->handleRequest($request);

        // Only process validation if the form was submitted AND passes basic Symfony validation
        if ($form->isSubmitted() && $form->isValid()) {
            $checkIn = $reservation->getCheckIn();
            $checkOut = $reservation->getCheckOut();

            // === VALIDATION STEP 1: Check-in date must not be in the past ===
            $today = new \DateTime('today');
            if ($checkIn < $today) {
                $this->addFlash('danger', 'La date d\'arrivée ne peut pas être située dans le passé.');
                return $this->render('reservation/new.html.twig', [
                    'room' => $room,
                    'form' => $form->createView(),
                ]);
            }

            // === VALIDATION STEP 2: Check-out must be strictly after check-in ===
            if ($checkOut <= $checkIn) {
                $this->addFlash('danger', 'La date de départ doit être postérieure à la date d\'arrivée.');
                return $this->render('reservation/new.html.twig', [
                    'room' => $room,
                    'form' => $form->createView(),
                ]);
            }

            // === VALIDATION STEP 3: Number of guests must not exceed room capacity ===
            if ($reservation->getGuests() > $room->getCapacity()) {
                $this->addFlash('danger', sprintf('Cette chambre peut accueillir au maximum %d personne(s).', $room->getCapacity()));
                return $this->render('reservation/new.html.twig', [
                    'room' => $room,
                    'form' => $form->createView(),
                ]);
            }

            // === VALIDATION STEP 4: Check that the room is not already booked for these dates ===
            // This prevents double-booking by looking for overlapping reservations in the database
            if (!$reservationManager->isRoomAvailable($room, $checkIn, $checkOut)) {
                $this->addFlash('danger', 'Cette chambre n\'est pas disponible pour les dates sélectionnées. Veuillez choisir d\'autres dates.');
                return $this->render('reservation/new.html.twig', [
                    'room' => $room,
                    'form' => $form->createView(),
                ]);
            }

            // === STEP 5: All validations passed — create the reservation ===
            try {
                // Delegate the reservation creation to the ReservationManager service
                // It calculates the total price and saves the reservation to the database
                $createdReservation = $reservationManager->createReservation(
                    $user,
                    $room,
                    $checkIn,
                    $checkOut,
                    $reservation->getGuests(),
                    $reservation->getNotes()
                );

                // Show a success message
                $this->addFlash('success', 'Votre réservation a été enregistrée avec succès.');

                // Redirect to the confirmation page using the reservation reference code
                return $this->redirectToRoute('app_reservation_confirmation', [
                    'reference' => $createdReservation->getReference(),
                ]);
            } catch (\Exception $e) {
                // If something unexpected goes wrong, show an error message
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement : ' . $e->getMessage());
            }
        }

        // Render the reservation form (either for the first time, or again after a validation error)
        return $this->render('reservation/new.html.twig', [
            'room' => $room,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Confirmation page — displayed after a successful reservation.
     * Route: GET /reservation/confirmation/{reference}
     * Name: app_reservation_confirmation
     * Access: Only logged-in users (ROLE_USER)
     *
     * @param string $reference The unique reservation reference code (e.g. "DR-A1B2C3")
     * @param ReservationRepository $reservationRepository Used to find the reservation by its reference
     * @return Response The rendered confirmation page
     */
    #[Route('/reservation/confirmation/{reference}', name: 'app_reservation_confirmation')]
    #[IsGranted('ROLE_USER')]
    public function confirmation(
        string $reference,
        ReservationRepository $reservationRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Find the reservation in the database using its unique reference code
        $reservation = $reservationRepository->findOneBy(['reference' => $reference]);

        // If no reservation is found with this reference, show a 404 error page
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        // SECURITY CHECK: Only the customer who made the reservation OR an admin can view the confirmation
        if ($reservation->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès non autorisé.');
        }

        // Render the confirmation template with the reservation details
        return $this->render('reservation/confirmation.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}
