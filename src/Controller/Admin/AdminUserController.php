<?php

// Namespace declaration — this controller lives in the Admin sub-namespace
namespace App\Controller\Admin;

// Import the User entity
use App\Entity\User;
// Import the UserRepository to query users from the database
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
 * AdminUserController — allows admins to view the list of registered users.
 * All routes are prefixed with /admin/utilisateurs.
 * Access restricted to ROLE_ADMIN users only.
 */
#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    /**
     * List all registered users — admin user management page.
     * Route: GET /admin/utilisateurs
     * Name: app_admin_users
     *
     * @param UserRepository $userRepository Used to fetch all users from the database
     * @return Response The rendered users list page
     */
    #[Route('', name: 'app_admin_users')]
    public function index(UserRepository $userRepository): Response
    {
        // Fetch all users, ordered by registration date (newest first)
        $users = $userRepository->findBy([], ['createdAt' => 'DESC']);

        // Render the admin user list template
        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }
}
