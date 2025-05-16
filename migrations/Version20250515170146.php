<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515170146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE enrollment DROP enrollment_date
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session DROP FOREIGN KEY FK_D044D5D48C4FC193
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D044D5D48C4FC193 ON session
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD location VARCHAR(255) DEFAULT NULL, ADD type VARCHAR(50) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, DROP instructor_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE enrollment ADD enrollment_date DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD instructor_id INT DEFAULT NULL, DROP location, DROP type, DROP description, DROP created_at, DROP updated_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD CONSTRAINT FK_D044D5D48C4FC193 FOREIGN KEY (instructor_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D044D5D48C4FC193 ON session (instructor_id)
        SQL);
    }
}
