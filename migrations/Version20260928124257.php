<?php

// Enable strict types — enforces type declarations for function arguments and return values
declare(strict_types=1);

// Namespace for Doctrine migrations (standard Symfony convention)
namespace DoctrineMigrations;

// Import the Schema class (represents the database schema structure)
use Doctrine\DBAL\Schema\Schema;
// Import the base migration class
use Doctrine\Migrations\AbstractMigration;

/**
 * Database Migration — Version 2026-09-28 12:42:57
 *
 * This migration creates the initial database schema for the Drei Hotel application.
 * It creates three tables:
 *   1. reservations — stores all hotel room reservations
 *   2. rooms        — stores all hotel rooms and their details
 *   3. users        — stores all registered user accounts
 *
 * This migration is specifically designed for SQLite database.
 * Run it with: php bin/console doctrine:migrations:migrate
 */
final class Version20260928124257 extends AbstractMigration
{
    /**
     * Description of what this migration does.
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * UP — apply the migration (create tables).
     * This method runs when you execute: php bin/console doctrine:migrations:migrate
     *
     * @param Schema $schema The database schema object (not used directly here since we use raw SQL)
     */
    public function up(Schema $schema): void
    {
        // Safety check: only run this migration on SQLite (would fail on MySQL/PostgreSQL)
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SQLitePlatform'."
        );

        // CREATE the "reservations" table
        // Columns: id, reference (unique booking code), check_in, check_out, guests,
        //          total_price, status, payment_status, notes, created_at
        // Foreign keys: user_id → users(id), room_id → rooms(id)
        $this->addSql('CREATE TABLE reservations (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(30) NOT NULL COLLATE "BINARY", check_in DATE NOT NULL, check_out DATE NOT NULL, guests INTEGER NOT NULL, total_price NUMERIC(10, 2) NOT NULL, status VARCHAR(30) NOT NULL COLLATE "BINARY", payment_status VARCHAR(30) NOT NULL COLLATE "BINARY", notes CLOB DEFAULT NULL COLLATE "BINARY", created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, room_id INTEGER NOT NULL, CONSTRAINT FK_4DA239A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4DA23954177093 FOREIGN KEY (room_id) REFERENCES rooms (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        // Create an index on room_id for faster queries when filtering reservations by room
        $this->addSql('CREATE INDEX IDX_4DA23954177093 ON reservations (room_id)');
        // Create an index on user_id for faster queries when filtering reservations by user
        $this->addSql('CREATE INDEX IDX_4DA239A76ED395 ON reservations (user_id)');
        // Create a unique index on reference to ensure no two reservations share the same code
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4DA239AEA34913 ON reservations (reference)');

        // Safety check again (standard migration pattern)
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SQLitePlatform'."
        );

        // CREATE the "rooms" table
        // Columns: id, name, type, description, price_per_night, capacity, image,
        //          status, amenities (JSON), surface, bed_type, created_at, updated_at
        $this->addSql('CREATE TABLE rooms (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(150) NOT NULL COLLATE "BINARY", type VARCHAR(50) NOT NULL COLLATE "BINARY", description CLOB NOT NULL COLLATE "BINARY", price_per_night NUMERIC(10, 2) NOT NULL, capacity INTEGER NOT NULL, image VARCHAR(255) DEFAULT NULL COLLATE "BINARY", status VARCHAR(30) NOT NULL COLLATE "BINARY", amenities CLOB DEFAULT NULL COLLATE "BINARY", surface INTEGER DEFAULT NULL, bed_type VARCHAR(100) DEFAULT NULL COLLATE "BINARY", created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL)');

        // Safety check
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SQLitePlatform'."
        );

        // CREATE the "users" table
        // Columns: id, email (unique), roles (JSON), password (hashed),
        //          first_name, last_name, phone, created_at
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL COLLATE "BINARY", roles CLOB NOT NULL COLLATE "BINARY", password VARCHAR(255) NOT NULL COLLATE "BINARY", first_name VARCHAR(100) NOT NULL COLLATE "BINARY", last_name VARCHAR(100) NOT NULL COLLATE "BINARY", phone VARCHAR(30) NOT NULL COLLATE "BINARY", created_at DATETIME NOT NULL)');
        // Create a unique index on email to prevent duplicate accounts
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    /**
     * DOWN — reverse the migration (drop tables).
     * This method runs when you execute: php bin/console doctrine:migrations:migrate prev
     * It undoes the UP migration by dropping all three tables.
     *
     * @param Schema $schema The database schema object
     */
    public function down(Schema $schema): void
    {
        // Drop all tables in reverse order to avoid foreign key constraint errors
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SQLitePlatform'."
        );

        // Drop reservations first (it has foreign keys to users and rooms)
        $this->addSql('DROP TABLE "reservations"');

        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SQLitePlatform'."
        );

        // Drop rooms second
        $this->addSql('DROP TABLE "rooms"');

        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SQLitePlatform'."
        );

        // Drop users last
        $this->addSql('DROP TABLE "users"');
    }
}
