<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426183123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE course ADD image VARCHAR(255) DEFAULT NULL, ADD duration INT DEFAULT NULL, CHANGE category_id category_id INT NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE name title VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD start_time TIME NOT NULL, ADD end_time TIME NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE course DROP image, DROP duration, CHANGE category_id category_id INT DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE title name VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session DROP start_time, DROP end_time
        SQL);
    }
}
