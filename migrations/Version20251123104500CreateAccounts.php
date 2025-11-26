<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251123104500CreateAccounts extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accounts table';
    }

    public function up(Schema $schema): void
    {
        // accounts
        $this->addSql('CREATE TABLE accounts (
            id BIGINT AUTO_INCREMENT NOT NULL,
            uuid CHAR(36) NOT NULL,
            account_holder_id BIGINT NOT NULL,
            balance NUMERIC(20,4) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE (uuid),
            INDEX IDX_ACCOUNT_HOLDER (account_holder_id),
            CONSTRAINT FK_ACCOUNT_HOLDER FOREIGN KEY (account_holder_id)
                REFERENCES account_users (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY FK_ACCOUNT_HOLDER');
        $this->addSql('DROP TABLE accounts');
    }
}
