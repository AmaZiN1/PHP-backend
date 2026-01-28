<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_logs table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE audit_logs (
                id INT AUTO_INCREMENT NOT NULL,
                actor_type VARCHAR(20) NOT NULL,
                actor_id INT DEFAULT NULL,
                event_type VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INT DEFAULT NULL,
                old_value JSON DEFAULT NULL,
                new_value JSON DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                status VARCHAR(20) DEFAULT \'success\' NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX idx_actor (actor_type, actor_id),
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_event_type (event_type),
                INDEX idx_created_at (created_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_logs');
    }
}
