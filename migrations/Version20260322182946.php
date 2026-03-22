<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260322182946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE click ADD short_url_id INT NOT NULL');
        $this->addSql('ALTER TABLE click ADD CONSTRAINT FK_BAF6C220F1252BC8 FOREIGN KEY (short_url_id) REFERENCES short_url (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_BAF6C220F1252BC8 ON click (short_url_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE click DROP CONSTRAINT FK_BAF6C220F1252BC8');
        $this->addSql('DROP INDEX IDX_BAF6C220F1252BC8');
        $this->addSql('ALTER TABLE click DROP short_url_id');
    }
}
