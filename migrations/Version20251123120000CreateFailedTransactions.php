<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251123120000CreateFailedTransactions extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create failed_transactions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE failed_transactions (
            id BIGINT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            transaction_uuid CHAR(36) NOT NULL,
            error_code VARCHAR(50) DEFAULT NULL,
            error_message JSON NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_FAILED_TX (transaction_uuid),
            CONSTRAINT FK_FAILED_TX FOREIGN KEY (transaction_uuid)
                REFERENCES transactions (uuid)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE failed_transactions DROP FOREIGN KEY FK_FAILED_TX');
        $this->addSql('DROP TABLE failed_transactions');
    }
}
