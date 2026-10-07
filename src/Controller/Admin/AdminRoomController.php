<?php

// Namespace declaration — this controller lives in the Admin sub-namespace
namespace App\Controller\Admin;

// Import the Room entity
use App\Entity\Room;
// Import the RoomType form (defines the form fields for creating/editing a room)
use App\Form\RoomType;
// Import the RoomRepository to query rooms from the database
use App\Repository\RoomRepository;
// Import EntityManagerInterface to save/delete data in the database
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
 * AdminRoomController — allows admins to manage hotel rooms (CRUD operations).
 * All routes are prefixed with /admin/chambres.
 * Access restricted to ROLE_ADMIN users only.
 *
 * Features:
 *   - List all rooms
 *   - Add a new room
 *   - Edit an existing room
 *   - Toggle room availability status (available ↔ unavailable)
 *   - Delete a room
 */
#[Route('/admin/chambres')]
#[IsGranted('ROLE_ADMIN')]
class AdminRoomController extends AbstractController
{
    /**
     * List all rooms — admin room management page.
     * Route: GET /admin/chambres
     * Name: app_admin_rooms
     *
     * @param RoomRepository $roomRepository Used to fetch all rooms from the database
     * @return Response The rendered rooms list page
     */
    #[Route('', name: 'app_admin_rooms')]
    public function index(RoomRepository $roomRepository): Response
    {
        // Fetch all rooms, ordered by ID descending (newest room first)
        $rooms = $roomRepository->findBy([], ['id' => 'DESC']);

        // Render the admin room list template
        return $this->render('admin/room/index.html.twig', [
            'rooms' => $rooms,
        ]);
    }

    /**
     * Add a new room — display the creation form and save the new room.
     * Route: GET + POST /admin/chambres/ajouter
     * Name: app_admin_room_new
     *
     * @param Request $request Contains form data on POST
     * @param EntityManagerInterface $entityManager Used to save the new room to the database
     * @return Response The rendered form page or redirect to the rooms list
     */
    #[Route('/ajouter', name: 'app_admin_room_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create a new empty Room object
        $room = new Room();
        // Build the form and bind it to the room object
        $form = $this->createForm(RoomType::class, $room);
        // Process the request — fills $room with form data if submitted
        $form->handleRequest($request);

        // If the form was submitted and passes validation
        if ($form->isSubmitted() && $form->isValid()) {
            // Set the creation timestamp
            $room->setCreatedAt(new \DateTimeImmutable());
            // Tell Doctrine to track this new Room entity
            $entityManager->persist($room);
            // Execute the INSERT SQL query to save the room
            $entityManager->flush();

            // Show a success message
            $this->addFlash('success', 'La chambre a été ajoutée avec succès.');

            // Redirect to the rooms list page
            return $this->redirectToRoute('app_admin_rooms');
        }

        // Render the "add room" form template
        return $this->render('admin/room/new.html.twig', [
            'room' => $room,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edit an existing room — display the edit form and save changes.
     * Route: GET + POST /admin/chambres/{id}/modifier
     * Name: app_admin_room_edit
     *
     * @param Room $room The room to edit (fetched automatically by {id})
     * @param Request $request Contains form data on POST
     * @param EntityManagerInterface $entityManager Used to save changes to the database
     * @return Response The rendered edit form or redirect to the rooms list
     */
    #[Route('/{id}/modifier', name: 'app_admin_room_edit')]
    public function edit(Room $room, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Build the form pre-filled with the existing room data
        $form = $this->createForm(RoomType::class, $room);
        // Process the request
        $form->handleRequest($request);

        // If the form was submitted and passes validation
        if ($form->isSubmitted() && $form->isValid()) {
            // Set the update timestamp
            $room->setUpdatedAt(new \DateTimeImmutable());
            // No need for persist() — Doctrine already tracks this room since it was loaded from the DB
            $entityManager->flush();

            // Show a success message
            $this->addFlash('success', 'La chambre a été modifiée avec succès.');

            // Redirect to the rooms list page
            return $this->redirectToRoute('app_admin_rooms');
        }

        // Render the "edit room" form template
        return $this->render('admin/room/edit.html.twig', [
            'room' => $room,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Toggle room status — switch between "available" and "unavailable".
     * Route: POST /admin/chambres/{id}/statut
     * Name: app_admin_room_toggle_status
     * Method: POST only (state-changing action)
     *
     * @param Room $room The room to toggle
     * @param Request $request Contains the CSRF token
     * @param EntityManagerInterface $entityManager Used to save the status change
     * @return Response Redirects to the rooms list page
     */
    #[Route('/{id}/statut', name: 'app_admin_room_toggle_status', methods: ['POST'])]
    public function toggleStatus(Room $room, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verify the CSRF token to prevent forged requests
        if ($this->isCsrfTokenValid('toggle_status_' . $room->getId(), $request->request->get('_token'))) {
            // Toggle the status: if currently available → set unavailable, and vice versa
            $newStatus = $room->isAvailable() ? Room::STATUS_UNAVAILABLE : Room::STATUS_AVAILABLE;
            $room->setStatus($newStatus);
            // Record the update timestamp
            $room->setUpdatedAt(new \DateTimeImmutable());
            // Save the change to the database
            $entityManager->flush();

            $this->addFlash('success', 'Le statut de la chambre a été mis à jour.');
        }

        // Redirect back to the rooms list
        return $this->redirectToRoute('app_admin_rooms');
    }

    /**
     * Delete a room — permanently removes a room from the database.
     * Route: POST /admin/chambres/{id}/supprimer
     * Name: app_admin_room_delete
     * Method: POST only (destructive action — never use GET for deletions!)
     *
     * WARNING: This also deletes all reservations associated with this room
     *          (due to orphanRemoval=true on the Room->reservations relationship).
     *
     * @param Room $room The room to delete
     * @param Request $request Contains the CSRF token
     * @param EntityManagerInterface $entityManager Used to remove the room from the database
     * @return Response Redirects to the rooms list page
     */
    #[Route('/{id}/supprimer', name: 'app_admin_room_delete', methods: ['POST'])]
    public function delete(Room $room, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verify the CSRF token to prevent forged deletion requests
        if ($this->isCsrfTokenValid('delete_room_' . $room->getId(), $request->request->get('_token'))) {
            // Mark the room for deletion
            $entityManager->remove($room);
            // Execute the DELETE SQL query
            $entityManager->flush();

            $this->addFlash('success', 'La chambre a été supprimée avec succès.');
        }

        // Redirect back to the rooms list
        return $this->redirectToRoute('app_admin_rooms');
    }
}
