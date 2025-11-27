<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127111324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates users, accounts, transactions, failed_transactions, idempotency_keys with all proper relations';
    }

    public function up(Schema $schema): void
    {
        // USERS
        $this->addSql('
            CREATE TABLE users (
                id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_USER_EMAIL (email),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        // ACCOUNTS
        $this->addSql('
            CREATE TABLE accounts (
                id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
                account_holder_id BIGINT UNSIGNED NOT NULL,
                uuid VARCHAR(36) NOT NULL,
                balance NUMERIC(20,4) NOT NULL,
                currency VARCHAR(3) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_ACCOUNT_UUID (uuid),
                INDEX IDX_ACCOUNT_HOLDER (account_holder_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        // TRANSACTIONS
        $this->addSql("
            CREATE TABLE transactions (
                id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
                account_id BIGINT UNSIGNED NOT NULL,
                uuid CHAR(36) NOT NULL,
                type VARCHAR(6) NOT NULL,
                amount NUMERIC(20,4) NOT NULL,
                currency VARCHAR(3) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_TRANSACTION_UUID (uuid),
                INDEX IDX_TRANSACTION_ACCOUNT (account_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_TRANSACTION_ACCOUNT FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB;
        ");

        // FAILED TRANSACTIONS
        $this->addSql('
            CREATE TABLE failed_transactions (
                id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
                original_transaction_id BIGINT UNSIGNED NOT NULL,
                error_message JSON NOT NULL,
                failed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id),
                INDEX IDX_FAILED_TX_ORIGINAL (original_transaction_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        // IDEMPOTENCY KEYS
        $this->addSql('
            CREATE TABLE idempotency_keys (
                `key` VARCHAR(255) NOT NULL,
                response LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(`key`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        // FOREIGN KEYS
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_ACCOUNT_HOLDER FOREIGN KEY (account_holder_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE failed_transactions ADD CONSTRAINT FK_FAILED_TX_ORIGINAL FOREIGN KEY (original_transaction_id) REFERENCES transactions (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE failed_transactions');
        $this->addSql('DROP TABLE idempotency_keys');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE accounts');
        $this->addSql('DROP TABLE users');
    }
}
