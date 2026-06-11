<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611192742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE department DROP FOREIGN KEY FK_CD1DE18AF23F8454');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE job_title');
        $this->addSql('DROP TABLE vice_presidency');
        $this->addSql('ALTER TABLE employee ADD full_name VARCHAR(255) NOT NULL, DROP first_name, DROP last_name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, vice_presidency_id INT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_CD1DE18AF23F8454 (vice_presidency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE job_title (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE vice_presidency (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT FK_CD1DE18AF23F8454 FOREIGN KEY (vice_presidency_id) REFERENCES vice_presidency (id)');
        $this->addSql('ALTER TABLE employee ADD last_name VARCHAR(255) NOT NULL, CHANGE full_name first_name VARCHAR(255) NOT NULL');
    }
}
