# 🏨 DREI HOTEL — Application Web de Réservation Hôtelière

[![Symfony](https://img.shields.io/badge/Symfony-6.4%20LTS-black?style=flat-square&logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php)](https://www.php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap)](https://getbootstrap.com)
[![Doctrine](https://img.shields.io/badge/Doctrine-ORM-orange?style=flat-square)](https://www.doctrine-project.org)

> **Drei Hotel** est une application web professionnelle de réservation de séjours hôteliers. Le projet allie une identité visuelle raffinée (Beige Chaud + Bleu Élégant + Blanc) et un code source **clair, lisible, structuré et facile à expliquer lors d'une soutenance de jury**.

---

## 📑 Table des Matières

1. [Présentation du Projet](#-présentation-du-projet)
2. [Identité Visuelle & Design System](#-identité-visuelle--design-system)
3. [Principe Clé : Paiement sur Place](#-principe-clé--paiement-sur-place)
4. [Technologies Utilisées](#-technologies-utilisées)
5. [Structure du Projet](#-structure-du-projet)
6. [Installation & Lancement](#-installation--lancement)
7. [Comptes de Démonstration](#-comptes-de-démonstration)
8. [Fonctionnalités Principales & Logique Métier](#-fonctionnalités-principales--logique-métier)
9. [Guide de Démonstration pour Jury (Scénario Pas à Pas)](#-guide-de-démonstration-pour-jury)

---

## 🌟 Présentation du Projet

Drei Hotel offre aux visiteurs une plateforme fluide pour :
- Découvrir les hébergements (*Chambre Standard, Chambre Deluxe, Suite Drei, Chambre Familiale*).
- Filtrer par catégorie, capacité, tarif ou dates de séjour.
- Calculer automatiquement le nombre de nuitées et le tarif global.
- Effectuer une réservation instantanée **sans carte bancaire en ligne**.
- Suivre l'état des réservations depuis l'espace client.

Pour l'équipe de l'hôtel, un **tableau de bord administrateur** permet de :
- Consulter les indicateurs clés (chambres, réservations, encaissements, clients).
- Gérer l'inventaire des chambres (création, modification, disponibilité, suppression).
- Valider, confirmer ou annuler les réservations.
- Encaisser le règlement des clients à leur arrivée (*Paiement sur place &rarr; Payé*).

---

## 🎨 Identité Visuelle & Design System

Le design est inspiré des boutiques hôtels contemporains :

| Élément | Code Couleur / Rendu | Utilisation |
| :--- | :--- | :--- |
| **Bleu Hôtel Principal** | `#315A72` / `#244356` | Boutons principaux, liens actifs, titres d'accent |
| **Beige Chaud** | `#E8D8C3` / `#F2E9DE` | Cartes d'estimation, badges, surbrillance |
| **Fond Crème** | `#F7F3ED` | Arrière-plan global doux et chaleureux |
| **Texte Ardoise** | `#263238` | Typographie principale lisible et contrastée |
| **Blanc Pur** | `#FFFFFF` | Cartes de contenu, formulaires, modales |

---

## 💵 Principe Clé : Paiement sur Place

> **Aucun paiement par carte en ligne** (Stripe, PayPal, passerelle externe) n'est requis.

1. Le client réserve en ligne sans transmettre de coordonnées bancaires.
2. La réservation est enregistrée avec le statut de paiement `on_site` (*Paiement sur place à l'hôtel*).
3. Lors de son arrivée à la réception, le client règle son séjour en espèces ou carte.
4. L'administrateur valide le règlement dans le panneau de gestion et bascule le statut en `paid` (*Payé*).

---

## 🛠 Technologies Utilisées

- **Backend :** PHP 8.2+, Symfony 6.4 LTS, Twig, Symfony Forms & Validator, Symfony Security.
- **Base de données :** Doctrine ORM, compatible **SQLite** (inclus out-of-the-box sans configuration) et **MySQL** (WAMP, XAMPP, Docker).
- **Frontend :** HTML5 sémantique, CSS3 moderne (variables CSS, flexbox/grid), Vanilla JavaScript (calcul dynamique de nuitées et prix), Bootstrap 5.3 CDN & Font Awesome 6.

---

## 📁 Structure du Projet

```text
drei-hotel/
│
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml       # Configuration de la base de données
│   │   └── security.yaml       # Authentification & contrôle d'accès (ROLE_ADMIN, ROLE_USER)
│   └── routes.yaml
│
├── migrations/                 # Migrations SQL de la base de données
│
├── public/
│   ├── css/
│   │   └── style.css           # Design System Drei Hotel (Beige + Bleu)
│   ├── js/
│   │   └── app.js              # Calcul en direct des nuitées et du prix
│   └── index.php               # Point d'entrée de l'application
│
├── src/
│   ├── Controller/
│   │   ├── HomeController.php          # Accueil, À propos, Services, Contact
│   │   ├── RoomController.php          # Liste des chambres, filtres & fiche détail
│   │   ├── ReservationController.php   # Tunnel de réservation & confirmation
│   │   ├── AccountController.php       # Espace client (profil & mes réservations)
│   │   ├── SecurityController.php      # Inscription, connexion & déconnexion
│   │   └── Admin/
│   │       ├── AdminDashboardController.php    # Statistiques & réservations récentes
│   │       ├── AdminRoomController.php         # CRUD complet des chambres
│   │       ├── AdminReservationController.php  # Validation & encaissement
│   │       └── AdminUserController.php         # Gestion des comptes clients
│   │
│   ├── Entity/
│   │   ├── User.php            # Utilisateurs (Clients & Administrateurs)
│   │   ├── Room.php            # Chambres, types, tarifs, capacités & équipements
│   │   └── Reservation.php     # Réservations, dates, montants, statut & paiement
│   │
│   ├── Form/
│   │   ├── RegistrationType.php    # Formulaire d'inscription sécurisé
│   │   ├── ReservationType.php     # Formulaire de choix des dates
│   │   └── RoomType.php            # Formulaire d'ajout/modification de chambre
│   │
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── RoomRepository.php          # Filtres & recherche de disponibilité
│   │   └── ReservationRepository.php   # Vérification du chevauchement de dates
│   │
│   ├── Service/
│   │   └── ReservationManager.php      # Logique de calcul du prix & anti-double réservation
│   │
│   └── DataFixtures/
│       └── AppFixtures.php     # Données de démonstration réalistes
│
├── templates/
│   ├── base.html.twig          # Layout public général
│   ├── admin_base.html.twig    # Layout de l'espace d'administration
│   ├── home/                   # Vues Accueil, À propos, Services, Contact
│   ├── room/                   # Vues Catalogue et Détail chambre
│   ├── reservation/            # Vues Réservation et Reçu de confirmation
│   ├── account/                # Vue Espace Client
│   ├── security/               # Vues Connexion et Inscription
│   └── admin/                  # Vues Dashboard, Chambres, Réservations, Clients
│
├── tests/
│   └── VerificationTest.php    # Script de test automatisé
│
├── .env                        # Variables d'environnement
├── composer.json
└── README.md
```

---

## 🚀 Installation & Lancement

### 1. Prérequis
- **PHP 8.2 ou supérieur** avec extensions `pdo_sqlite` ou `pdo_mysql`, `intl`.
- **Composer**.

### 2. Cloner ou naviguer dans le dossier
```bash
cd drei-hotel
```

### 3. Installer les dépendances
```bash
composer install
```

### 4. Configuration de la base de données
Par défaut, le projet utilise **SQLite** dans `var/drei_hotel.db` (fonctionne immédiatement sans aucun serveur externe à lancer).

> **Option MySQL (WAMP / XAMPP) :**
> Si vous préférez MySQL, décommentez la ligne correspondante dans `.env` :
> ```env
> DATABASE_URL="mysql://root:@127.0.0.1:3306/drei_hotel?serverVersion=8.0.32&charset=utf8mb4"
> ```
> Puis créez la base :
> ```bash
> php bin/console doctrine:database:create
> ```

### 5. Créer la structure et charger les données de démonstration
```bash
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction
```

### 6. Lancer le serveur web
Avec le serveur intégré PHP :
```bash
php -S 127.0.0.1:8000 -t public
```
Ou avec la CLI Symfony (si installée) :
```bash
symfony server:start
```

L'application est accessible sur : **http://127.0.0.1:8000**

---

## 🔑 Comptes de Démonstration

| Rôle | Adresse Email | Mot de passe | Description |
| :--- | :--- | :--- | :--- |
| **Administrateur** | `admin@drei-hotel.com` | `Admin123!` | Accès complet au tableau de bord `/admin` |
| **Client 1** | `client@drei-hotel.com` | `Client123!` | Karim Ben Salah (historique existant) |
| **Client 2** | `sarah@drei-hotel.com` | `Sarah123!` | Sarah Mansour (réservation en attente) |
| **Client 3** | `mehdi@drei-hotel.com` | `Mehdi123!` | Mehdi Trabelsi (séjour terminé) |

---

## 🧠 Fonctionnalités Principales & Logique Métier

### 1. Prévention du double-booking (Chevauchement de dates)
La méthode `isRoomAvailable()` du service [`ReservationManager`](file:///C:/Users/Bara/.gemini/antigravity/scratch/drei-hotel/src/Service/ReservationManager.php) garantit qu'aucune chambre ne peut être réservée deux fois sur la même période :
```sql
checkIn < reservationExistante.checkOut AND checkOut > reservationExistante.checkIn
```
Les réservations annulées (`status = 'cancelled'`) sont automatiquement exclues.

### 2. Calcul du tarif total
```php
public function calculateTotal(float $pricePerNight, \DateTimeInterface $checkIn, \DateTimeInterface $checkOut): float
{
    $nights = (int) $checkIn->diff($checkOut)->days;
    return round($pricePerNight * max(1, $nights), 2);
}
```

---

## 🎓 Guide de Démonstration pour Jury

Suivez ce scénario de 12 étapes pour présenter l'ensemble des compétences développées :

1. **Page d'accueil (`/`) :** Présentez l'identité visuelle de Drei Hotel (bleu & beige), la barre de recherche rapide, les chambres vedettes et les avis clients.
2. **Catalogue des chambres (`/chambres`) :** Utilisez les filtres (catégorie *Deluxe*, budget max, capacité) et montrez la mise à jour des résultats.
3. **Fiche détaillée (`/chambres/2`) :** Montrez les caractéristiques de la *Chambre Deluxe* (superficie, literie King Size, équipements, bandeau de paiement sur place).
4. **Calcul dynamique :** Modifiez les dates d'arrivée et de départ sur le formulaire latéral pour démontrer le calcul en direct du nombre de nuits et du total en Dinars Tunisiens (DT).
5. **Tunnel de réservation :** Cliquez sur **Réserver**. Connectez-vous avec `client@drei-hotel.com` / `Client123!` (ou inscrivez un nouvel utilisateur).
6. **Confirmation sans carte :** Validez la réservation et montrez le message explicite : *"Paiement sur place à l'hôtel"*.
7. **Reçu de confirmation :** Observez le reçu avec le numéro de référence unique (ex: `#DR-000101`) et le détail financier.
8. **Espace Client (`/mon-compte`) :** Montrez la liste des réservations du client avec les badges de statut et la possibilité d'annulation.
9. **Accès Administrateur :** Déconnectez-vous et connectez-vous avec le compte `admin@drei-hotel.com` / `Admin123!`.
10. **Tableau de bord Admin (`/admin`) :** Présentez les statistiques (nombre de chambres, réservations en attente, revenus encaissés) et la dernière réservation enregistrée.
11. **Gestion des Chambres (`/admin/chambres`) :** Montrez l'activation/désactivation en un clic d'une chambre (`Disponible` / `Indisponible`) et l'édition des tarifs.
12. **Validation & Encaissement (`/admin/reservations/{id}`) :** Ouvrez la réservation, changez son statut en `Confirmée`, puis marquez le paiement comme `Payé (Encaissé)`. Observez la mise à jour instantanée du chiffre d'affaires sur le dashboard.

---

*Projet réalisé avec Symfony 6.4 LTS, PHP 8.2 & Doctrine ORM.*
