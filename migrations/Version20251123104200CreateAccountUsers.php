<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251123104200CreateAccountUsers extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create account_users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE account_users (
            id BIGINT AUTO_INCREMENT NOT NULL,
            uuid CHAR(36) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE account_users');
    }
}
