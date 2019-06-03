<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190531215646 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE campaign (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, payment_address VARCHAR(255) NOT NULL, payment_address_password VARCHAR(255) DEFAULT NULL, state SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1F1512DD989D9B62 ON campaign (slug)');
        $this->addSql('CREATE TABLE address (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, address VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D4E6F81D4E6F81 ON address (address)');
        $this->addSql('CREATE TABLE payment_transaction (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, hash VARCHAR(255) NOT NULL, amount BIGINT UNSIGNED NOT NULL, fee_amount BIGINT UNSIGNED NOT NULL, confirmations INTEGER UNSIGNED NOT NULL, state SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE payment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, campaign_id INTEGER UNSIGNED NOT NULL, address_id INTEGER UNSIGNED NOT NULL, payment_transaction_id INTEGER UNSIGNED DEFAULT NULL, amount BIGINT UNSIGNED NOT NULL, state SMALLINT UNSIGNED NOT NULL, fail_reason VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX IDX_6D28840DF639F774 ON payment (campaign_id)');
        $this->addSql('CREATE INDEX IDX_6D28840DF5B7AF75 ON payment (address_id)');
        $this->addSql('CREATE INDEX IDX_6D28840DCAE8710B ON payment (payment_transaction_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE payment_transaction');
        $this->addSql('DROP TABLE payment');
    }
}
