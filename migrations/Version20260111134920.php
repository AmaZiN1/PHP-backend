<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260111134920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_logs CHANGE user_agent user_agent LONGTEXT DEFAULT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE domains ADD backup TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE mailbox_autoresponders CHANGE body body LONGTEXT NOT NULL, CHANGE start_date start_date DATETIME DEFAULT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE mailbox_autoresponders RENAME INDEX uniq_mailbox_autoresponder TO UNIQ_104BE9B566EC35CC');
        $this->addSql('ALTER TABLE mailbox_tokens CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE mailbox_tokens RENAME INDEX uniq_token TO UNIQ_31B4EE665F37A13B');
        $this->addSql('ALTER TABLE mailbox_tokens RENAME INDEX idx_mailbox_tokens_mailbox TO IDX_31B4EE6666EC35CC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_logs CHANGE user_agent user_agent TEXT DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'success\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE domains DROP backup');
        $this->addSql('ALTER TABLE mailbox_autoresponders CHANGE body body TEXT NOT NULL, CHANGE start_date start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE end_date end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE mailbox_autoresponders RENAME INDEX uniq_104be9b566ec35cc TO UNIQ_MAILBOX_AUTORESPONDER');
        $this->addSql('ALTER TABLE mailbox_tokens CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE mailbox_tokens RENAME INDEX uniq_31b4ee665f37a13b TO UNIQ_TOKEN');
        $this->addSql('ALTER TABLE mailbox_tokens RENAME INDEX idx_31b4ee6666ec35cc TO IDX_MAILBOX_TOKENS_MAILBOX');
    }
}
