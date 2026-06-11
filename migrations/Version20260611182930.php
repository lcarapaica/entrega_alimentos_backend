<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611182930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make employee.p00_code nullable — column retained for future use, not required by import pipeline.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee CHANGE p00_code p00_code VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE must_change_password must_change_password TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee CHANGE p00_code p00_code VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE
          `user`
        CHANGE
          must_change_password must_change_password TINYINT(1) DEFAULT 1 NOT NULL');
    }
}
