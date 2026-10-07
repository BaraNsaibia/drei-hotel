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
$hasher = new UserPasswordHasher($passwordHasherFactory);

echo "=== TESTING REGISTRATION & LOGIN DIRECTLY IN MYSQL ===\n\n";

// 1. Check if SQLite had bara.nsaibia.dev@gmail.com and copy it over to MySQL if not present
$sqliteFile = __DIR__ . '/../var/drei_hotel.db';
if (file_exists($sqliteFile)) {
    $sqlitePdo = new PDO('sqlite:' . $sqliteFile);
    $existingInSqlite = $sqlitePdo->query("SELECT * FROM users WHERE email = 'bara.nsaibia.dev@gmail.com'")->fetch(PDO::FETCH_ASSOC);

    $existingInMysql = $em->getRepository(User::class)->findOneBy(['email' => 'bara.nsaibia.dev@gmail.com']);

    if (!$existingInMysql && $existingInSqlite) {
        $userBara = new User();
        $userBara->setEmail($existingInSqlite['email']);
        $userBara->setFirstName($existingInSqlite['first_name']);
        $userBara->setLastName($existingInSqlite['last_name']);
        $userBara->setPhone($existingInSqlite['phone']);
        $userBara->setRoles(json_decode($existingInSqlite['roles'], true) ?: ['ROLE_USER']);
        $userBara->setPassword($existingInSqlite['password']);
        $em->persist($userBara);
        $em->flush();
        echo "[MIGRATED] Bara Nsaibia successfully inserted into MySQL! ID: " . $userBara->getId() . "\n";
    } elseif ($existingInMysql) {
        echo "[EXISTS] Bara Nsaibia is already in MySQL with ID: " . $existingInMysql->getId() . "\n";
    }
}

// 2. Test creating a NEW test user using Symfony entity and password hasher
$testEmail = 'nouveau.compte@gmail.com';
$testPassword = 'MonMotDePasse123!';

$existingTest = $em->getRepository(User::class)->findOneBy(['email' => $testEmail]);
if ($existingTest) {
    $em->remove($existingTest);
    $em->flush();
}

$newUser = new User();
$newUser->setEmail($testEmail);
$newUser->setFirstName('Nouveau');
$newUser->setLastName('Client');
$newUser->setPhone('+33 789012345');
$newUser->setPassword($hasher->hashPassword($newUser, $testPassword));
$newUser->setRoles(['ROLE_USER']);

$em->persist($newUser);
$em->flush();

echo "[TEST REGISTRATION] New user created in MySQL with ID: " . $newUser->getId() . " and email: " . $newUser->getEmail() . "\n";

// 3. Test password verification / login credentials
$retrievedUser = $em->getRepository(User::class)->findOneBy(['email' => $testEmail]);
$isPasswordValid = $hasher->isPasswordValid($retrievedUser, $testPassword);
echo "[TEST AUTHENTICATION] Password valid: " . ($isPasswordValid ? 'YES (LOGIN SUCCESSFUL)' : 'NO') . "\n";

// 4. List all users currently in MySQL
echo "\n--- ALL USERS CURRENTLY IN MYSQL (drei_hotel.users) ---\n";
$allUsers = $em->getRepository(User::class)->findAll();
foreach ($allUsers as $u) {
    echo "ID: " . $u->getId() . " | " . $u->getEmail() . " | " . $u->getFullName() . " | Roles: " . implode(', ', $u->getRoles()) . "\n";
}
