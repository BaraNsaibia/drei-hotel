<?php

// Namespace declaration — this controller lives in App\Controller\Admin (admin area)
namespace App\Controller\Admin;

// Import entity classes
use App\Entity\Reservation;
use App\Entity\Room;
// Import repositories to query the database
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
// Import the base controller class
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Response for HTTP response
use Symfony\Component\HttpFoundation\Response;
// Import Route for URL mapping
use Symfony\Component\Routing\Annotation\Route;
// Import IsGranted to restrict access to admins only
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AdminDashboardController — displays the admin dashboard with key statistics.
 * All routes in this controller are prefixed with /admin.
 * Access restricted to ROLE_ADMIN users only.
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    /**
     * Admin dashboard — the main admin overview page showing key statistics.
     * Route: GET /admin
     * Name: app_admin_dashboard
     *
     * Displays:
     *   - Total number of rooms, reservations, and customers
     *   - Number of pending and confirmed reservations
     *   - Total revenue from paid reservations
     *   - The 5 most recent reservations
     *
     * @param RoomRepository $roomRepository Query rooms from the database
     * @param ReservationRepository $reservationRepository Query reservations from the database
     * @param UserRepository $userRepository Query users from the database
     * @return Response The rendered admin dashboard page
     */
    #[Route('', name: 'app_admin_dashboard')]
    public function index(
        RoomRepository $roomRepository,
        ReservationRepository $reservationRepository,
        UserRepository $userRepository
    ): Response {
        // Count total rooms in the hotel
        $totalRooms = $roomRepository->count([]);
        // Count total reservations (all statuses)
        $totalReservations = $reservationRepository->count([]);
        // Count reservations with "pending" status (waiting for admin action)
        $pendingReservations = $reservationRepository->countByStatus(Reservation::STATUS_PENDING);
        // Count reservations with "confirmed" status
        $confirmedReservations = $reservationRepository->countByStatus(Reservation::STATUS_CONFIRMED);
        // Calculate total revenue from reservations marked as "paid"
        $totalRevenue = $reservationRepository->calculateTotalRevenue();
        // Count total registered customers
        $totalCustomers = $userRepository->countCustomers();
        // Get the 5 most recent reservations for the "latest reservations" table
        $latestReservations = $reservationRepository->findLatest(5);

        // Render the dashboard template and pass all statistics
        return $this->render('admin/dashboard/index.html.twig', [
            'total_rooms' => $totalRooms,
            'total_reservations' => $totalReservations,
            'pending_reservations' => $pendingReservations,
            'confirmed_reservations' => $confirmedReservations,
            'total_revenue' => $totalRevenue,
            'total_customers' => $totalCustomers,
            'latest_reservations' => $latestReservations,
        ]);
    }
}
