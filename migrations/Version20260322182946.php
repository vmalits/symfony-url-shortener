<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322182946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clicks ADD short_urls_id INT NOT NULL');
        $this->addSql('ALTER TABLE clicks ADD CONSTRAINT FK_BAF6C220F1252BC8 FOREIGN KEY (short_urls_id) REFERENCES short_urls (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_BAF6C220F1252BC8 ON clicks (short_urls_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clicks DROP CONSTRAINT FK_BAF6C220F1252BC8');
        $this->addSql('DROP INDEX IDX_BAF6C220F1252BC8');
        $this->addSql('ALTER TABLE clicks DROP short_urls_id');
    }
}
