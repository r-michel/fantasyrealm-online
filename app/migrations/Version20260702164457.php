<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702164457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `character` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, gender VARCHAR(50) NOT NULL, skin_color VARCHAR(50) NOT NULL, eye_color VARCHAR(50) NOT NULL, eye_shape VARCHAR(50) NOT NULL, nose_shape VARCHAR(50) NOT NULL, mouth_shape VARCHAR(50) NOT NULL, shared TINYINT NOT NULL, authorized TINYINT NOT NULL, image LONGBLOB NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, INDEX IDX_937AB0347E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, rate INT NOT NULL, comment LONGTEXT NOT NULL, created_at DATETIME NOT NULL, published TINYINT NOT NULL, owner_id INT NOT NULL, on_character_id INT NOT NULL, INDEX IDX_9474526C7E3C61F9 (owner_id), INDEX IDX_9474526CC23AC56E (on_character_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE equipment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, active TINYINT NOT NULL, image LONGBLOB NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_D338D58312469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE equipment_character (equipment_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_4147F64D517FE9FE (equipment_id), INDEX IDX_4147F64D1136BE75 (character_id), PRIMARY KEY (equipment_id, character_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE equipment_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE `character` ADD CONSTRAINT FK_937AB0347E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CC23AC56E FOREIGN KEY (on_character_id) REFERENCES `character` (id)');
        $this->addSql('ALTER TABLE equipment ADD CONSTRAINT FK_D338D58312469DE2 FOREIGN KEY (category_id) REFERENCES equipment_category (id)');
        $this->addSql('ALTER TABLE equipment_character ADD CONSTRAINT FK_4147F64D517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipment_character ADD CONSTRAINT FK_4147F64D1136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `character` DROP FOREIGN KEY FK_937AB0347E3C61F9');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C7E3C61F9');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CC23AC56E');
        $this->addSql('ALTER TABLE equipment DROP FOREIGN KEY FK_D338D58312469DE2');
        $this->addSql('ALTER TABLE equipment_character DROP FOREIGN KEY FK_4147F64D517FE9FE');
        $this->addSql('ALTER TABLE equipment_character DROP FOREIGN KEY FK_4147F64D1136BE75');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE equipment_character');
        $this->addSql('DROP TABLE equipment_category');
    }
}
