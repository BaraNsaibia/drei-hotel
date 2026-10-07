<?php

// Namespace declaration — this controller lives in App\Controller
namespace App\Controller;

// Import the Reservation entity to work with reservation records
use App\Entity\Reservation;
// Import the User entity for type-hinting the currently logged-in user
use App\Entity\User;
// Import the ReservationRepository to query reservations from the database
use App\Repository\ReservationRepository;
// Import EntityManagerInterface to save/update data in the database
use Doctrine\ORM\EntityManagerInterface;
// Import the base controller class (provides render(), addFlash(), getUser(), etc.)
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request to read form data sent via POST
use Symfony\Component\HttpFoundation\Request;
// Import Response to represent the HTTP response
use Symfony\Component\HttpFoundation\Response;
// Import Route attribute to define URL patterns
use Symfony\Component\Routing\Annotation\Route;
// Import IsGranted to restrict access — only logged-in users can access this controller
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AccountController — handles the user's personal account area.
 * ALL methods in this controller require the user to be logged in (ROLE_USER).
 * This is enforced by the #[IsGranted('ROLE_USER')] attribute on the class.
 */
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    /**
     * "My Account" page — shows the user's profile info and all their reservations.
     * Route: GET /mon-compte
     * Name: app_account
     *
     * @param ReservationRepository $reservationRepository Injected by Symfony to query reservation data
     * @return Response The rendered account page
     */
    #[Route('/mon-compte', name: 'app_account')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        /** @var User $user — Tell the IDE that getUser() returns a User entity */
        $user = $this->getUser();

        // Fetch all reservations belonging to this user, most recent first
        $reservations = $reservationRepository->findBy(
            ['user' => $user],          // WHERE user = current user
            ['createdAt' => 'DESC']     // ORDER BY createdAt DESC (newest first)
        );

        // Render the account template and pass the user + reservations data
        return $this->render('account/index.html.twig', [
            'user' => $user,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Cancel a reservation — the user can cancel their own pending or confirmed reservation.
     * Route: POST /mon-compte/reservation/{id}/annuler
     * Name: app_account_cancel_reservation
     * Method: POST only (the cancel button sends a POST form for security)
     *
     * @param Reservation $reservation Automatically fetched by Symfony using the {id} in the URL (ParamConverter)
     * @param Request $request Used to read the CSRF token from the form
     * @param EntityManagerInterface $entityManager Used to save the status change to the database
     * @return Response Redirects back to the account page
     */
    #[Route('/mon-compte/reservation/{id}/annuler', name: 'app_account_cancel_reservation', methods: ['POST'])]
    public function cancelReservation(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // SECURITY CHECK: Make sure the reservation belongs to the current user
        // (prevents a user from cancelling someone else's reservation by changing the URL)
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        // CSRF protection: verify the token sent in the form matches the expected token
        // This prevents Cross-Site Request Forgery attacks
        if (!$this->isCsrfTokenValid('cancel_res_' . $reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité CSRF invalide.');
            return $this->redirectToRoute('app_account');
        }

        // Only allow cancellation if the reservation is still "pending" or "confirmed"
        // (already cancelled or completed reservations cannot be cancelled again)
        if (in_array($reservation->getStatus(), [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
            // Change the reservation status to "cancelled"
            $reservation->setStatus(Reservation::STATUS_CANCELLED);
            // Save the change to the database (flush sends the SQL UPDATE query)
            $entityManager->flush();
            // Show success message
            $this->addFlash('success', 'Votre réservation a été annulée avec succès.');
        } else {
            // Show warning if the reservation can no longer be cancelled
            $this->addFlash('warning', 'Cette réservation ne peut plus être annulée.');
        }

        // Redirect back to the account page
        return $this->redirectToRoute('app_account');
    }
}
