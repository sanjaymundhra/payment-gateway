<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251123104700CreateTransactions extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Transactions table';
    }

    public function up(Schema $schema): void
    {
        // Transaction
        $this->addSql('CREATE TABLE transactions (
            id BIGINT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            from_account_uuid CHAR(36) NOT NULL,
            to_account_uuid CHAR(36) NOT NULL,
            amount NUMERIC(20,4) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL,
            idempotency_key VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_UUID (uuid),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_FROM_ACCOUNT FOREIGN KEY (from_account_uuid) REFERENCES accounts (uuid)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_TO_ACCOUNT FOREIGN KEY (to_account_uuid) REFERENCES accounts (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_FROM_ACCOUNT');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_TO_ACCOUNT');
        $this->addSql('DROP TABLE transactions');
    }
}
