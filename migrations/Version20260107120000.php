<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create mailbox_autoresponders table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE mailbox_autoresponders (
                id INT AUTO_INCREMENT NOT NULL,
                mailbox_id INT NOT NULL,
                active TINYINT(1) DEFAULT 0 NOT NULL,
                subject VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_MAILBOX_AUTORESPONDER (mailbox_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE mailbox_autoresponders
            ADD CONSTRAINT FK_MAILBOX_AUTORESPONDER_MAILBOX
            FOREIGN KEY (mailbox_id)
            REFERENCES mailboxes (id)
            ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mailbox_autoresponders DROP FOREIGN KEY FK_MAILBOX_AUTORESPONDER_MAILBOX');
        $this->addSql('DROP TABLE mailbox_autoresponders');
    }
}
