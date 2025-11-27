<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127124944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX `primary` ON idempotency_keys');
        $this->addSql('ALTER TABLE idempotency_keys CHANGE `key` idempotency_key VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE idempotency_keys ADD PRIMARY KEY (idempotency_key)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX `PRIMARY` ON idempotency_keys');
        $this->addSql('ALTER TABLE idempotency_keys CHANGE idempotency_key `key` VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE idempotency_keys ADD PRIMARY KEY (`key`)');
    }
}
