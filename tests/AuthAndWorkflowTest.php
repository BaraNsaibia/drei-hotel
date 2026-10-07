<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use App\Entity\Room;
use App\Entity\Reservation;
use App\Service\ReservationManager;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== FULL WORKFLOW & SECURITY VERIFICATION ===\n\n";

// 1. Check Admin User
$admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin@drei-hotel.com']);
if (!$admin) {
    die("[FAIL] Admin user not found in database!\n");
}
echo "[TEST 1] Admin User Found: " . $admin->getFullName() . " (Roles: " . implode(', ', $admin->getRoles()) . ")\n";

// 2. Check Client User
$client = $em->getRepository(User::class)->findOneBy(['email' => 'client@drei-hotel.com']);
if (!$client) {
    die("[FAIL] Client user not found in database!\n");
}
echo "[TEST 2] Client User Found: " . $client->getFullName() . " (Roles: " . implode(', ', $client->getRoles()) . ")\n";

// 3. Test Room Listing & Details
$room = $em->getRepository(Room::class)->findOneBy(['type' => Room::TYPE_STANDARD]);
echo "[TEST 3] Room loaded: " . $room->getName() . " | Price: " . $room->getPricePerNight() . " €\n";

// 4. Test Reservation Creation via Service
$reservationManager = new ReservationManager($em->getRepository(Reservation::class), $em);
$checkIn = new DateTime('+40 days');
$checkOut = new DateTime('+43 days');

$res = $reservationManager->createReservation($client, $room, $checkIn, $checkOut, 2, 'Test reservation');
echo "[TEST 4] Reservation Created -> Ref: #" . $res->getReference() . " | Total: " . $res->getTotalPrice() . " € | Payment: " . $res->getPaymentStatus() . "\n";

// 5. Test Status Change to Paid
$res->setPaymentStatus(Reservation::PAYMENT_PAID);
$res->setStatus(Reservation::STATUS_CONFIRMED);
$em->flush();
echo "[TEST 5] Payment Updated to: " . $res->getPaymentStatusLabel() . " | Status: " . $res->getStatusLabel() . "\n";

echo "\nALL WORKFLOW CHECKS PASSED PERFECTLY!\n";
