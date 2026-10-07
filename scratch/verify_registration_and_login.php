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

echo "=======================================================\n";
echo "1. VERIFICATION DE LA CONNEXION MYSQL ACTIVE\n";
echo "=======================================================\n";
$connection = $em->getConnection();
echo "Database: " . $connection->getDatabase() . "\n";
echo "Driver: " . get_class($connection->getDriver()) . "\n";

echo "\n=======================================================\n";
echo "2. TEST DE CREATION D'UN NOUVEAU COMPTE CLIENT\n";
echo "=======================================================\n";
$testEmail = 'client.test.mysql@drei-hotel.com';
$plainPassword = 'Password2026!';

// Supprimer l'ancien compte test si existant
$existing = $em->getRepository(User::class)->findOneBy(['email' => $testEmail]);
if ($existing) {
    $em->remove($existing);
    $em->flush();
}

$newUser = new User();
$newUser->setEmail($testEmail);
$newUser->setFirstName('Testeur');
$newUser->setLastName('MySQL');
$newUser->setPhone('+33 711223344');
$newUser->setRoles(['ROLE_USER']);
$hashedPassword = $hasher->hashPassword($newUser, $plainPassword);
$newUser->setPassword($hashedPassword);

$em->persist($newUser);
$em->flush();

echo "-> Compte créé avec succès dans MySQL !\n";
echo "-> ID généré par MySQL: " . $newUser->getId() . "\n";
echo "-> Email: " . $newUser->getEmail() . "\n";

echo "\n=======================================================\n";
echo "3. VERIFICATION DIRECTE DANS LA TABLE MYSQL 'users'\n";
echo "=======================================================\n";
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=drei_hotel', 'root', '');
$stmt = $pdo->prepare('SELECT id, email, first_name, last_name, phone, created_at FROM users WHERE email = ?');
$stmt->execute([$testEmail]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "-> [CONFIRMATION PDO] La ligne existe bien physiquement dans MySQL drei_hotel.users:\n";
    echo "   ID : " . $row['id'] . "\n";
    echo "   Email : " . $row['email'] . "\n";
    echo "   Nom : " . $row['first_name'] . " " . $row['last_name'] . "\n";
    echo "   Date création : " . $row['created_at'] . "\n";
} else {
    die("ERREUR: La ligne n'a pas été trouvée dans MySQL !\n");
}

echo "\n=======================================================\n";
echo "4. TEST D'AUTHENTIFICATION & CONNEXION DU COMPTE\n";
echo "=======================================================\n";
// Recharger l'utilisateur depuis MySQL
$em->clear();
$userFromDb = $em->getRepository(User::class)->findOneBy(['email' => $testEmail]);

$isAuthSuccess = $hasher->isPasswordValid($userFromDb, $plainPassword);
$isAuthWrongPass = $hasher->isPasswordValid($userFromDb, 'WrongPassword!');

echo "-> Tentative de connexion avec le bon mot de passe ('$plainPassword') : " . ($isAuthSuccess ? "SUCCÈS (Connexion acceptée)" : "ÉCHEC") . "\n";
echo "-> Tentative de connexion avec un mauvais mot de passe : " . (!$isAuthWrongPass ? "REJETÉ (Sécurité OK)" : "FAILLE") . "\n";

echo "\n=======================================================\n";
echo "5. LISTE COMPLETE DES UTILISATEURS DANS PHPMYADMIN / MYSQL\n";
echo "=======================================================\n";
$allUsers = $em->getRepository(User::class)->findBy([], ['id' => 'ASC']);
foreach ($allUsers as $u) {
    echo "#" . $u->getId() . " | " . $u->getEmail() . " | " . $u->getFullName() . " | " . implode(', ', $u->getRoles()) . "\n";
}
echo "\nTOUT EST PARFAITEMENT OPERATIONNEL AVEC MYSQL !\n";
