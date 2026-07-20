<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720150353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE `character` ADD public_id VARCHAR(200) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_937AB034B5B48B91 ON `character` (public_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_937AB034B5B48B91 ON `character`');
        //$this->addSql('ALTER TABLE `character` DROP public_id');
    }
}
