<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611185930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A16DD822C6');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1AE80F5DF');
        $this->addSql('DROP INDEX IDX_5D9F75A16DD822C6 ON employee');
        $this->addSql('DROP INDEX IDX_5D9F75A1AE80F5DF ON employee');
        $this->addSql('ALTER TABLE employee ADD job_title VARCHAR(255) DEFAULT NULL, ADD vice_presidency VARCHAR(255) DEFAULT NULL, ADD department VARCHAR(255) DEFAULT NULL, DROP job_title_id, DROP department_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee ADD job_title_id INT NOT NULL, ADD department_id INT DEFAULT NULL, DROP job_title, DROP vice_presidency, DROP department');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A16DD822C6 FOREIGN KEY (job_title_id) REFERENCES job_title (id)');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_5D9F75A16DD822C6 ON employee (job_title_id)');
        $this->addSql('CREATE INDEX IDX_5D9F75A1AE80F5DF ON employee (department_id)');
    }
}
