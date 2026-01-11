<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create mailbox_tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mailbox_tokens (
            id INT AUTO_INCREMENT NOT NULL,
            mailbox_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_TOKEN (token),
            INDEX IDX_MAILBOX_TOKENS_MAILBOX (mailbox_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE mailbox_tokens ADD CONSTRAINT FK_MAILBOX_TOKENS_MAILBOX
            FOREIGN KEY (mailbox_id) REFERENCES mailboxes (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mailbox_tokens DROP FOREIGN KEY FK_MAILBOX_TOKENS_MAILBOX');
        $this->addSql('DROP TABLE mailbox_tokens');
    }
}
