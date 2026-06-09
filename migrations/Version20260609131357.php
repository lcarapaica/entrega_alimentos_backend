<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609131357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE delivery (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, station_id INT NOT NULL, operator_id INT NOT NULL, signature_path VARCHAR(255) NOT NULL, delivered_at DATETIME NOT NULL, is_proxy_delivery TINYINT(1) NOT NULL, authorized_cedula VARCHAR(20) DEFAULT NULL, authorized_full_name VARCHAR(255) DEFAULT NULL, authorization_reason LONGTEXT DEFAULT NULL, INDEX IDX_3781EC108C03F15C (employee_id), INDEX IDX_3781EC1021BDB235 (station_id), INDEX IDX_3781EC10584598A3 (operator_id), UNIQUE INDEX uq_delivery_employee_station (employee_id, station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, vice_presidency_id INT NOT NULL, name VARCHAR(100) NOT NULL, INDEX IDX_CD1DE18AF23F8454 (vice_presidency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE distribution (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, name VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, INDEX IDX_A4483781B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE employee (id INT AUTO_INCREMENT NOT NULL, job_title_id INT NOT NULL, department_id INT DEFAULT NULL, site_id INT DEFAULT NULL, national_id VARCHAR(20) NOT NULL, p00_code VARCHAR(20) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, foto_path VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_5D9F75A136491297 (national_id), UNIQUE INDEX UNIQ_5D9F75A189795B98 (p00_code), INDEX IDX_5D9F75A16DD822C6 (job_title_id), INDEX IDX_5D9F75A1AE80F5DF (department_id), INDEX IDX_5D9F75A1F6BD1646 (site_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_title (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, region VARCHAR(100) NOT NULL, state VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE station (id INT AUTO_INCREMENT NOT NULL, distribution_id INT NOT NULL, site_id INT NOT NULL, name VARCHAR(100) NOT NULL, order_number INT NOT NULL, INDEX IDX_9F39F8B16EB6DDB5 (distribution_id), INDEX IDX_9F39F8B1F6BD1646 (site_id), UNIQUE INDEX uq_station_distribution_site_order (distribution_id, site_id, order_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, registered_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D6498C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vice_presidency (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC108C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1021BDB235 FOREIGN KEY (station_id) REFERENCES station (id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10584598A3 FOREIGN KEY (operator_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT FK_CD1DE18AF23F8454 FOREIGN KEY (vice_presidency_id) REFERENCES vice_presidency (id)');
        $this->addSql('ALTER TABLE distribution ADD CONSTRAINT FK_A4483781B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A16DD822C6 FOREIGN KEY (job_title_id) REFERENCES job_title (id)');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B16EB6DDB5 FOREIGN KEY (distribution_id) REFERENCES distribution (id)');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B1F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6498C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC108C03F15C');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1021BDB235');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC10584598A3');
        $this->addSql('ALTER TABLE department DROP FOREIGN KEY FK_CD1DE18AF23F8454');
        $this->addSql('ALTER TABLE distribution DROP FOREIGN KEY FK_A4483781B03A8386');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A16DD822C6');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1AE80F5DF');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1F6BD1646');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B16EB6DDB5');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B1F6BD1646');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6498C03F15C');
        $this->addSql('DROP TABLE delivery');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE distribution');
        $this->addSql('DROP TABLE employee');
        $this->addSql('DROP TABLE job_title');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE station');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE vice_presidency');
    }
}
