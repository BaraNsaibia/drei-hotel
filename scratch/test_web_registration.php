<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$passwordHasherFactory = new PasswordHasherFactory([
    User::class => ['algorithm' => 'auto'],
]);
$passwordHasher = new UserPasswordHasher($passwordHasherFactory);

$uniqueEmail = 'sophie.bernard.' . time() . '@test.com';

// Test registration logic directly
$user = new User();
$user->setEmail($uniqueEmail);
$user->setFirstName('Sophie');
$user->setLastName('Bernard');
$user->setPhone('+33 611223344');
$user->setPassword($passwordHasher->hashPassword($user, 'Password123!'));
$user->setRoles(['ROLE_USER']);

$em->persist($user);
$em->flush();

echo "User created directly via EntityManager in MySQL!\n";

// Now test querying from MySQL
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=drei_hotel', 'root', '');
$stmt = $pdo->prepare('SELECT id, email, first_name, last_name, phone, created_at FROM users WHERE email = ?');
$stmt->execute([$uniqueEmail]);
$found = $stmt->fetch(PDO::FETCH_ASSOC);

if ($found) {
    echo "SUCCESS: User found in MySQL!\n";
    echo "ID: " . $found['id'] . "\n";
    echo "Email: " . $found['email'] . "\n";
    echo "Name: " . $found['first_name'] . " " . $found['last_name'] . "\n";
} else {
    echo "FAIL: Not found in MySQL.\n";
}
