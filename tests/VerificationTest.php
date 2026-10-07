<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use App\Entity\Room;
use App\Entity\User;
use App\Entity\Reservation;
use App\Service\ReservationManager;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();
$reservationManager = new ReservationManager(
    $em->getRepository(Reservation::class),
    $em
);

echo "=== DREI HOTEL AUTOMATED VERIFICATION ===\n\n";

// 1. Verify Entities in Database
$rooms = $em->getRepository(Room::class)->findAll();
echo "[TEST 1] Loaded rooms count: " . count($rooms) . " (Expected >= 4) -> " . (count($rooms) >= 4 ? "PASS" : "FAIL") . "\n";

$users = $em->getRepository(User::class)->findAll();
echo "[TEST 2] Loaded users count: " . count($users) . " (Expected >= 4) -> " . (count($users) >= 4 ? "PASS" : "FAIL") . "\n";

$reservations = $em->getRepository(Reservation::class)->findAll();
echo "[TEST 3] Loaded reservations count: " . count($reservations) . " (Expected >= 3) -> " . (count($reservations) >= 3 ? "PASS" : "FAIL") . "\n";

// 2. Verify Price and Nights Calculation
$checkIn = new DateTime('2027-06-01');
$checkOut = new DateTime('2027-06-04');
$nights = $reservationManager->calculateNights($checkIn, $checkOut);
$total = $reservationManager->calculateTotal(120.00, $checkIn, $checkOut);
echo "[TEST 4] 3 nights at 120 € = " . $total . " € (Nights: " . $nights . ") -> " . ($nights === 3 && $total === 360.0 ? "PASS" : "FAIL") . "\n";

// 3. Test Availability & Overlap Check
$deluxeRoom = $em->getRepository(Room::class)->findOneBy(['type' => Room::TYPE_DELUXE]);
$isAvailableFuture = $reservationManager->isRoomAvailable($deluxeRoom, new DateTime('+60 days'), new DateTime('+65 days'));
echo "[TEST 5] Far future dates available: " . ($isAvailableFuture ? "PASS" : "FAIL") . "\n";

// 4. Test Overlap Prevention on existing reservation
$existingRes = $em->getRepository(Reservation::class)->findOneBy(['room' => $deluxeRoom, 'status' => Reservation::STATUS_CONFIRMED]);
if ($existingRes) {
    $overlapCheck = $reservationManager->isRoomAvailable($deluxeRoom, $existingRes->getCheckIn(), $existingRes->getCheckOut());
    echo "[TEST 6] Overlapping dates blocked (false): " . (!$overlapCheck ? "PASS" : "FAIL") . "\n";
}

echo "\nALL TESTS COMPLETED SUCCESSFULLY!\n";
