<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create aliases table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aliases (
            id INT AUTO_INCREMENT NOT NULL,
            domain_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            `to` TEXT NOT NULL,
            active TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_E16F61D4115F0EE5 (domain_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE aliases ADD CONSTRAINT FK_E16F61D4115F0EE5
            FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aliases DROP FOREIGN KEY FK_E16F61D4115F0EE5');
        $this->addSql('DROP TABLE aliases');
    }
}
