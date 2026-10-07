<?php

// Namespace declaration — this controller lives in the Admin sub-namespace
namespace App\Controller\Admin;

// Import the Reservation entity
use App\Entity\Reservation;
// Import the ReservationRepository to query reservations from the database
use App\Repository\ReservationRepository;
// Import EntityManagerInterface to save changes to the database
use Doctrine\ORM\EntityManagerInterface;
// Import the base controller class
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request and Response for HTTP handling
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// Import Route for URL mapping
use Symfony\Component\Routing\Annotation\Route;
// Import IsGranted to restrict access to admins only
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AdminReservationController — allows admins to manage all reservations.
 * All routes are prefixed with /admin/reservations.
 * Access restricted to ROLE_ADMIN users only.
 *
 * Features:
 *   - List all reservations (with optional status filter)
 *   - View a single reservation's details
 *   - Update a reservation's status (pending → confirmed → completed / cancelled)
 *   - Update a reservation's payment status (on_site → paid)
 */
#[Route('/admin/reservations')]
#[IsGranted('ROLE_ADMIN')]
class AdminReservationController extends AbstractController
{
    /**
     * List all reservations — optionally filtered by status.
     * Route: GET /admin/reservations
     * Name: app_admin_reservations
     *
     * @param Request $request Contains the optional "status" query parameter for filtering
     * @param ReservationRepository $reservationRepository Used to query reservations
     * @return Response The rendered reservation list page
     */
    #[Route('', name: 'app_admin_reservations')]
    public function index(Request $request, ReservationRepository $reservationRepository): Response
    {
        // Read the optional "status" filter from the URL (e.g. ?status=pending)
        $status = $request->query->get('status');

        // Build the filter criteria array
        $criteria = [];
        if (!empty($status)) {
            $criteria['status'] = $status;  // Filter by this specific status
        }

        // Fetch reservations matching the criteria, ordered by newest first
        $reservations = $reservationRepository->findBy($criteria, ['createdAt' => 'DESC']);

        // Render the reservation list template
        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'current_status' => $status,  // Pass the current filter to highlight the active tab
        ]);
    }

    /**
     * View a single reservation's full details.
     * Route: GET /admin/reservations/{id}   (where {id} must be a number)
     * Name: app_admin_reservation_show
     *
     * @param Reservation $reservation Automatically fetched by Symfony using {id} (ParamConverter)
     * @return Response The rendered reservation detail page
     */
    #[Route('/{id}', name: 'app_admin_reservation_show', requirements: ['id' => '\d+'])]
    public function show(Reservation $reservation): Response
    {
        // Render the reservation detail template
        return $this->render('admin/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Update a reservation's status (e.g. pending → confirmed, or confirmed → completed).
     * Route: POST /admin/reservations/{id}/statut
     * Name: app_admin_reservation_update_status
     * Method: POST only (for security — state-changing actions must use POST)
     *
     * @param Reservation $reservation The reservation to update (fetched by ID)
     * @param Request $request Contains the new status and CSRF token from the form
     * @param EntityManagerInterface $entityManager Used to save the status change
     * @return Response Redirects back to the reservation detail page
     */
    #[Route('/{id}/statut', name: 'app_admin_reservation_update_status', methods: ['POST'])]
    public function updateStatus(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Verify the CSRF token to prevent forged requests
        if ($this->isCsrfTokenValid('status_' . $reservation->getId(), $request->request->get('_token'))) {
            // Get the new status from the submitted form
            $newStatus = $request->request->get('status');

            // Define the list of allowed status values (prevents setting arbitrary values)
            $allowedStatuses = [
                Reservation::STATUS_PENDING,     // 'pending'   — waiting for confirmation
                Reservation::STATUS_CONFIRMED,   // 'confirmed' — booking confirmed
                Reservation::STATUS_CANCELLED,   // 'cancelled' — booking cancelled
                Reservation::STATUS_COMPLETED,   // 'completed' — stay is finished
            ];

            // Only update if the new status is in the allowed list
            if (in_array($newStatus, $allowedStatuses, true)) {
                $reservation->setStatus($newStatus);
                $entityManager->flush();  // Save to database
                $this->addFlash('success', sprintf('Le statut de la réservation a été changé en "%s".', $reservation->getStatusLabel()));
            }
        }

        // Redirect back to the reservation detail page
        return $this->redirectToRoute('app_admin_reservation_show', ['id' => $reservation->getId()]);
    }

    /**
     * Update a reservation's payment status (on_site → paid).
     * Route: POST /admin/reservations/{id}/paiement
     * Name: app_admin_reservation_update_payment
     * Method: POST only
     *
     * Special behavior: If payment is marked as "paid" and the reservation is still "pending",
     * it automatically confirms the reservation.
     *
     * @param Reservation $reservation The reservation to update
     * @param Request $request Contains the new payment status and CSRF token
     * @param EntityManagerInterface $entityManager Used to save changes
     * @return Response Redirects back to the reservation detail page
     */
    #[Route('/{id}/paiement', name: 'app_admin_reservation_update_payment', methods: ['POST'])]
    public function updatePayment(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Verify the CSRF token
        if ($this->isCsrfTokenValid('payment_' . $reservation->getId(), $request->request->get('_token'))) {
            // Get the new payment status from the form
            $newPayment = $request->request->get('payment_status');

            // Define allowed payment status values
            $allowedPayments = [
                Reservation::PAYMENT_ON_SITE,   // 'on_site' — customer will pay at the hotel
                Reservation::PAYMENT_PAID,      // 'paid'    — customer has already paid
            ];

            // Only update if the value is allowed
            if (in_array($newPayment, $allowedPayments, true)) {
                $reservation->setPaymentStatus($newPayment);

                // AUTO-CONFIRM: If the reservation is marked as paid and was still pending,
                // automatically change the reservation status to "confirmed"
                if ($newPayment === Reservation::PAYMENT_PAID && $reservation->getStatus() === Reservation::STATUS_PENDING) {
                    $reservation->setStatus(Reservation::STATUS_CONFIRMED);
                }

                $entityManager->flush();  // Save all changes to database
                $this->addFlash('success', sprintf('Le statut de paiement a été mis à jour : "%s".', $reservation->getPaymentStatusLabel()));
            }
        }

        // Redirect back to the reservation detail page
        return $this->redirectToRoute('app_admin_reservation_show', ['id' => $reservation->getId()]);
    }
}
