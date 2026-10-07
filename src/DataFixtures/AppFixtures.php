<?php

// Namespace declaration — fixtures live in App\DataFixtures
namespace App\DataFixtures;

// Import entity classes to create sample data
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
// Import the base Fixture class from Doctrine
use Doctrine\Bundle\FixturesBundle\Fixture;
// Import the ObjectManager for saving data to the database
use Doctrine\Persistence\ObjectManager;
// Import the password hasher to create secure hashed passwords for demo users
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * AppFixtures — loads sample/demo data into the database.
 *
 * This class is used during development to populate the database with realistic test data.
 * Run it with the console command: php bin/console doctrine:fixtures:load
 *
 * It creates:
 *   1. An admin user (for managing the hotel via the admin panel)
 *   2. Three demo customer accounts (for testing the booking flow)
 *   3. Four hotel rooms (one of each type: standard, deluxe, suite, family)
 *   4. Three sample reservations (in different statuses for testing)
 */
class AppFixtures extends Fixture
{
    /**
     * Constructor — inject the password hasher to securely hash demo user passwords.
     *
     * @param UserPasswordHasherInterface $hasher Service for hashing passwords
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {
    }

    /**
     * Load fixtures — create and save all sample data to the database.
     *
     * @param ObjectManager $manager Doctrine's object manager for persisting entities
     */
    public function load(ObjectManager $manager): void
    {
        // ============================================================
        // 1. CREATE ADMIN USER
        // Login: admin@drei-hotel.com / Admin123!
        // This user has ROLE_ADMIN and can access the /admin dashboard
        // ============================================================
        $admin = new User();
        $admin->setEmail('admin@drei-hotel.com');
        $admin->setFirstName('Directeur');
        $admin->setLastName('Drei Hotel');
        $admin->setPhone('+33 123456789');
        $admin->setRoles(['ROLE_ADMIN']);                                         // Grant admin privileges
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin123!'));   // Hash the password
        $manager->persist($admin);                                                // Mark for saving

        // ============================================================
        // 2. CREATE DEMO CUSTOMER ACCOUNTS
        // These users have ROLE_USER and can browse rooms and make reservations
        // ============================================================

        // Customer 1: Karim Ben Salah — Login: client@drei-hotel.com / Client123!
        $user1 = new User();
        $user1->setEmail('client@drei-hotel.com');
        $user1->setFirstName('Karim');
        $user1->setLastName('Ben Salah');
        $user1->setPhone('+216 98 123 456');
        $user1->setRoles(['ROLE_USER']);
        $user1->setPassword($this->hasher->hashPassword($user1, 'Client123!'));
        $manager->persist($user1);

        // Customer 2: Sarah Mansour — Login: sarah@drei-hotel.com / Sarah123!
        $user2 = new User();
        $user2->setEmail('sarah@drei-hotel.com');
        $user2->setFirstName('Sarah');
        $user2->setLastName('Mansour');
        $user2->setPhone('+216 55 234 567');
        $user2->setRoles(['ROLE_USER']);
        $user2->setPassword($this->hasher->hashPassword($user2, 'Sarah123!'));
        $manager->persist($user2);

        // Customer 3: Mehdi Trabelsi — Login: mehdi@drei-hotel.com / Mehdi123!
        $user3 = new User();
        $user3->setEmail('mehdi@drei-hotel.com');
        $user3->setFirstName('Mehdi');
        $user3->setLastName('Trabelsi');
        $user3->setPhone('+216 22 345 678');
        $user3->setRoles(['ROLE_USER']);
        $user3->setPassword($this->hasher->hashPassword($user3, 'Mehdi123!'));
        $manager->persist($user3);

        // ============================================================
        // 3. CREATE HOTEL ROOMS
        // Four rooms, one of each type, with different prices and capacities
        // ============================================================

        // ROOM 1: Standard Room — 120€/night, 2 guests, 24m²
        $roomStandard = new Room();
        $roomStandard->setName('Chambre Standard');
        $roomStandard->setType(Room::TYPE_STANDARD);
        $roomStandard->setDescription('Idéale pour les séjours professionnels ou les escapades en duo. Notre chambre standard offre un confort chaleureux, une literie de haute qualité et tous les équipements indispensables pour un repos parfait.');
        $roomStandard->setPricePerNight(120.00);
        $roomStandard->setCapacity(2);
        $roomStandard->setSurface(24);
        $roomStandard->setBedType('1 grand lit double Queen Size');
        $roomStandard->setImage('room-standard.jpg');
        $roomStandard->setStatus(Room::STATUS_AVAILABLE);
        $roomStandard->setAmenities([
            'Wi-Fi gratuit',
            'Climatisation individuelle',
            'Télévision écran plat HD',
            'Salle de bain privative avec douche à l\'italienne',
            'Sèche-cheveux',
            'Bureau de travail'
        ]);
        $manager->persist($roomStandard);

        // ROOM 2: Deluxe Room — 180€/night, 2 guests, 36m²
        $roomDeluxe = new Room();
        $roomDeluxe->setName('Chambre Deluxe');
        $roomDeluxe->setType(Room::TYPE_DELUXE);
        $roomDeluxe->setDescription('Un havre de paix élégant et spacieux doté d\'un balcon privé offrant une vue dégagée. Décorée dans les tons chauds et apaisants de l\'hôtel, elle dispose d\'un coin salon cosy et d\'attentions exclusives.');
        $roomDeluxe->setPricePerNight(180.00);
        $roomDeluxe->setCapacity(2);
        $roomDeluxe->setSurface(36);
        $roomDeluxe->setBedType('1 très grand lit King Size');
        $roomDeluxe->setImage('room-deluxe.jpg');
        $roomDeluxe->setStatus(Room::STATUS_AVAILABLE);
        $roomDeluxe->setAmenities([
            'Wi-Fi très haut débit',
            'Balcon privatif avec vue',
            'Climatisation silencieuse',
            'Mini-bar garni',
            'Machine à café Nespresso',
            'Peignoirs & chaussons',
            'Coffre-fort électronique'
        ]);
        $manager->persist($roomDeluxe);

        // ROOM 3: Suite Drei (Premium) — 320€/night, 4 guests, 62m²
        $suiteDrei = new Room();
        $suiteDrei->setName('Suite Drei');
        $suiteDrei->setType(Room::TYPE_SUITE);
        $suiteDrei->setDescription('Le fleuron de notre établissement. La Suite Drei allie raffinement, espace généreux et finitions d\'exception. Comprenant une chambre parentale avec lit King Size, un salon indépendant et une salle de bain avec baignoire balnéo.');
        $suiteDrei->setPricePerNight(320.00);
        $suiteDrei->setCapacity(4);
        $suiteDrei->setSurface(62);
        $suiteDrei->setBedType('1 lit King Size + 1 Canapé-lit grand confort');
        $suiteDrei->setImage('room-suite.jpg');
        $suiteDrei->setStatus(Room::STATUS_AVAILABLE);
        $suiteDrei->setAmenities([
            'Vue panoramique exceptionnelle',
            'Baignoire balnéothérapie & douche pluie',
            'Salon indépendant avec canapé design',
            'Service d\'étage VIP 24h/24',
            'Machine Nespresso & sélection de thés bio',
            'Deux Smart TV 4K 55"',
            'Accès prioritaire aux services de l\'hôtel'
        ]);
        $manager->persist($suiteDrei);

        // ROOM 4: Family Room — 240€/night, 4 guests, 48m²
        $roomFamily = new Room();
        $roomFamily->setName('Chambre Familiale');
        $roomFamily->setType(Room::TYPE_FAMILY);
        $roomFamily->setDescription('Conçue spécialement pour les familles et groupes d\'amis désirant voyager ensemble sans compromis sur l\'espace et l\'intimité. Agencement optimal avec deux espaces de couchage distincts.');
        $roomFamily->setPricePerNight(240.00);
        $roomFamily->setCapacity(4);
        $roomFamily->setSurface(48);
        $roomFamily->setBedType('1 lit double + 2 lits simples confortables');
        $roomFamily->setImage('room-family.jpg');
        $roomFamily->setStatus(Room::STATUS_AVAILABLE);
        $roomFamily->setAmenities([
            'Wi-Fi gratuit multi-appareils',
            'Deux espaces couchage séparés',
            'Grande salle de bain familiale',
            'Mini-réfrigérateur',
            'Bouilloire électrique & boissons chaudes',
            'Climatisation réversible',
            'Chaînes TV enfants & streaming'
        ]);
        $manager->persist($roomFamily);

        // ============================================================
        // 4. CREATE SAMPLE RESERVATIONS
        // Three demo reservations in different statuses for testing the admin panel
        // ============================================================

        // RESERVATION 1: Confirmed & Paid — Karim booked the Deluxe room for 3 nights
        $res1 = new Reservation();
        $res1->setReference('DR-000101');                               // Fixed reference for testing
        $res1->setUser($user1);                                         // Booked by Karim
        $res1->setRoom($roomDeluxe);                                    // Deluxe room
        $res1->setCheckIn(new \DateTime('+3 days'));                     // Arriving in 3 days
        $res1->setCheckOut(new \DateTime('+6 days'));                    // Leaving in 6 days (3 nights)
        $res1->setGuests(2);                                            // 2 guests
        $res1->setTotalPrice(540.00);                                   // 3 nights × 180€ = 540€
        $res1->setStatus(Reservation::STATUS_CONFIRMED);                // Already confirmed
        $res1->setPaymentStatus(Reservation::PAYMENT_PAID);             // Already paid
        $res1->setNotes('Arrivée prévue vers 18h.');                    // Special note
        $manager->persist($res1);

        // RESERVATION 2: Pending & Payment on site — Sarah booked the Suite for 2 nights
        $res2 = new Reservation();
        $res2->setReference('DR-000102');
        $res2->setUser($user2);                                         // Booked by Sarah
        $res2->setRoom($suiteDrei);                                     // Suite Drei
        $res2->setCheckIn(new \DateTime('+8 days'));                     // Arriving in 8 days
        $res2->setCheckOut(new \DateTime('+10 days'));                   // Leaving in 10 days (2 nights)
        $res2->setGuests(3);                                            // 3 guests
        $res2->setTotalPrice(640.00);                                   // 2 nights × 320€ = 640€
        $res2->setStatus(Reservation::STATUS_PENDING);                  // Waiting for confirmation
        $res2->setPaymentStatus(Reservation::PAYMENT_ON_SITE);          // Will pay at the hotel
        $res2->setNotes('Célébration d\'anniversaire de mariage.');     // Special request
        $manager->persist($res2);

        // RESERVATION 3: Completed & Paid — Mehdi's past stay in the Standard room
        $res3 = new Reservation();
        $res3->setReference('DR-000098');
        $res3->setUser($user3);                                         // Booked by Mehdi
        $res3->setRoom($roomStandard);                                  // Standard room
        $res3->setCheckIn(new \DateTime('-5 days'));                     // Arrived 5 days ago (past)
        $res3->setCheckOut(new \DateTime('-2 days'));                    // Left 2 days ago (3 nights)
        $res3->setGuests(1);                                            // 1 guest
        $res3->setTotalPrice(360.00);                                   // 3 nights × 120€ = 360€
        $res3->setStatus(Reservation::STATUS_COMPLETED);                // Stay is finished
        $res3->setPaymentStatus(Reservation::PAYMENT_PAID);             // Already paid
        $manager->persist($res3);

        // Execute all the INSERT SQL queries at once (batch save)
        $manager->flush();
    }
}
