<?php

// Namespace declaration — this controller lives in App\Controller
namespace App\Controller;

// Import the User entity
use App\Entity\User;
// Import the registration form type (defines the registration form fields)
use App\Form\RegistrationType;
// Import EntityManagerInterface to save data to the database
use Doctrine\ORM\EntityManagerInterface;
// Import the base controller class
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request and Response for HTTP handling
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// Import the password hasher to securely hash passwords before storing them
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// Import Route for URL mapping
use Symfony\Component\Routing\Annotation\Route;
// Import AuthenticationUtils to retrieve login errors and the last entered username
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * SecurityController — handles user authentication (register, login, logout).
 * These pages are public (no login required) except logout which requires a session.
 */
class SecurityController extends AbstractController
{
    /**
     * Registration page — allows a new user to create an account.
     * Route: GET + POST /inscription
     * Name: app_register
     *
     * @param Request $request The HTTP request (contains form data on POST)
     * @param UserPasswordHasherInterface $userPasswordHasher Service to hash passwords securely (bcrypt/argon2)
     * @param EntityManagerInterface $entityManager Service to persist (save) the new user to the database
     * @return Response The rendered registration page or a redirect to the login page on success
     */
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // If the user is already logged in, redirect them to the home page
        // (no need to register again)
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Create a new empty User object and build the registration form
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        // Process the request — if form was submitted, this fills $user with the form data
        $form->handleRequest($request);

        // If the form was submitted and all validation rules pass
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Get the plain-text password from the form
                // (the 'plainPassword' field is NOT mapped to the entity — it's a standalone form field)
                $plainPassword = $form->get('plainPassword')->getData();

                // Hash the plain-text password using Symfony's password hasher
                // NEVER store plain-text passwords in the database!
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );

                // Save the new user to the database
                $entityManager->persist($user);  // Tell Doctrine to track this new entity
                $entityManager->flush();          // Execute the INSERT SQL query

                // Show a success message and redirect to the login page
                $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                // If something goes wrong (e.g. duplicate email), show an error message
                $this->addFlash('danger', 'Une erreur est survenue lors de la création de votre compte : ' . $e->getMessage());
            }
        }

        // Render the registration form (either first display, or re-display after validation errors)
        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * Login page — displays the login form.
     * Route: GET + POST /connexion
     * Name: app_login
     *
     * NOTE: The actual authentication (checking email/password) is handled automatically
     * by Symfony's security system (configured in config/packages/security.yaml).
     * This method only renders the login form and shows errors if authentication failed.
     *
     * @param AuthenticationUtils $authenticationUtils Symfony service that provides the last login error and username
     * @return Response The rendered login page
     */
    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If the user is already logged in, redirect them to the home page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Get the last authentication error (if any) — e.g. "Invalid credentials"
        $error = $authenticationUtils->getLastAuthenticationError();
        // Get the last username (email) entered by the user to re-fill the form
        $lastUsername = $authenticationUtils->getLastUsername();

        // Render the login template and pass the error + last username
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Logout action — ends the user's session.
     * Route: GET /deconnexion
     * Name: app_logout
     *
     * This method will NEVER actually execute — Symfony's security firewall intercepts
     * the request to /deconnexion and handles the logout automatically.
     * The method body is just a safety fallback.
     */
    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        // This exception is never thrown in practice — Symfony intercepts the logout route
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
