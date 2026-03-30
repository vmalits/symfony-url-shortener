<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330080608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add country and referrer to clicks table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clicks ADD country VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE clicks ADD referrer VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER INDEX idx_baf6c220f1252bc8 RENAME TO IDX_20DA1901723FD28C');
        $this->addSql('ALTER INDEX uniq_8336053177153098 RENAME TO UNIQ_4A53F93477153098');
        $this->addSql('ALTER INDEX idx_83360531a76ed395 RENAME TO IDX_4A53F934A76ED395');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clicks DROP country');
        $this->addSql('ALTER TABLE clicks DROP referrer');
        $this->addSql('ALTER INDEX idx_20da1901723fd28c RENAME TO idx_baf6c220f1252bc8');
        $this->addSql('ALTER INDEX idx_4a53f934a76ed395 RENAME TO idx_83360531a76ed395');
        $this->addSql('ALTER INDEX uniq_4a53f93477153098 RENAME TO uniq_8336053177153098');
    }
}
