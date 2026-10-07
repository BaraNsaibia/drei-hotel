<?php

// Namespace declaration — this controller lives in the App\Controller folder
namespace App\Controller;

// Import the RoomRepository to fetch room data from the database
use App\Repository\RoomRepository;
// Import the base controller class provided by Symfony (gives us render(), addFlash(), redirectToRoute(), etc.)
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request to read HTTP request data (GET/POST parameters, headers, etc.)
use Symfony\Component\HttpFoundation\Request;
// Import Response to represent the HTTP response sent back to the browser
use Symfony\Component\HttpFoundation\Response;
// Import Route attribute to define URL patterns for each controller method
use Symfony\Component\Routing\Annotation\Route;

/**
 * HomeController — handles all the public static pages of the hotel website:
 *   - Home page (/)
 *   - About page (/a-propos)
 *   - Services page (/services)
 *   - Contact page (/contact)
 */
class HomeController extends AbstractController
{
    /**
     * Home page — the landing page of the website.
     * Route: GET /
     * Name: app_home (used in Twig templates for link generation with path('app_home'))
     *
     * @param RoomRepository $roomRepository Injected automatically by Symfony (dependency injection).
     *                                        Used to query the rooms table in the database.
     * @return Response The rendered HTML page
     */
    #[Route('/', name: 'app_home')]
    public function index(RoomRepository $roomRepository): Response
    {
        // Fetch the 3 cheapest available rooms to display as "featured rooms" on the home page
        $featuredRooms = $roomRepository->findFeaturedRooms(3);

        // Render the Twig template and pass the featured rooms as a variable
        return $this->render('home/index.html.twig', [
            'featured_rooms' => $featuredRooms,
        ]);
    }

    /**
     * About page — displays information about the hotel.
     * Route: GET /a-propos
     * Name: app_about
     */
    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        // Simply render the about template — no data needed from the database
        return $this->render('home/about.html.twig');
    }

    /**
     * Services page — shows the hotel's services (spa, restaurant, etc.).
     * Route: GET /services
     * Name: app_services
     */
    #[Route('/services', name: 'app_services')]
    public function services(): Response
    {
        // Simply render the services template — no data needed from the database
        return $this->render('home/services.html.twig');
    }

    /**
     * Contact page — displays a contact form and handles its submission.
     * Route: GET + POST /contact
     * Name: app_contact
     *
     * @param Request $request The HTTP request object, used to check if the form was submitted (POST)
     * @return Response The rendered HTML page
     */
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request): Response
    {
        // Track whether the contact form has been submitted
        $submitted = false;

        // Check if this is a POST request (= the user clicked "Submit" on the contact form)
        if ($request->isMethod('POST')) {
            // Show a green success flash message to the user
            // (Flash messages appear once and disappear after the next page load)
            $this->addFlash('success', 'Votre message a été envoyé avec succès. Notre équipe vous répondra dans les plus brefs délais.');
            $submitted = true;
        }

        // Render the contact page template and pass the submission status
        return $this->render('home/contact.html.twig', [
            'submitted' => $submitted,
        ]);
    }
}
