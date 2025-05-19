<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515205651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE announcement (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, sender_id INT NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', email_sent TINYINT(1) NOT NULL, INDEX IDX_4DB9D91C613FECDF (session_id), INDEX IDX_4DB9D91CF624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE announcement_read (id INT AUTO_INCREMENT NOT NULL, announcement_id INT NOT NULL, user_id INT NOT NULL, read_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_B466493B913AEA17 (announcement_id), INDEX IDX_B466493BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91C613FECDF FOREIGN KEY (session_id) REFERENCES session (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91CF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement_read ADD CONSTRAINT FK_B466493B913AEA17 FOREIGN KEY (announcement_id) REFERENCES announcement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement_read ADD CONSTRAINT FK_B466493BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement DROP FOREIGN KEY FK_4DB9D91C613FECDF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement DROP FOREIGN KEY FK_4DB9D91CF624B39D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement_read DROP FOREIGN KEY FK_B466493B913AEA17
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement_read DROP FOREIGN KEY FK_B466493BA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE announcement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE announcement_read
        SQL);
    }
}
