<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_domains table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_domains (
            user_id INT NOT NULL,
            domain_id INT NOT NULL,
            INDEX IDX_USER_DOMAINS_USER (user_id),
            INDEX IDX_USER_DOMAINS_DOMAIN (domain_id),
            PRIMARY KEY(user_id, domain_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user_domains ADD CONSTRAINT FK_USER_DOMAINS_USER
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE user_domains ADD CONSTRAINT FK_USER_DOMAINS_DOMAIN
            FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_domains DROP FOREIGN KEY FK_USER_DOMAINS_USER');
        $this->addSql('ALTER TABLE user_domains DROP FOREIGN KEY FK_USER_DOMAINS_DOMAIN');
        $this->addSql('DROP TABLE user_domains');
    }
}
