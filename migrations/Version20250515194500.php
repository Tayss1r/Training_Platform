<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Custom migration to update session table with proper data conversion
 */
final class Version20250515194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update session table to use start_date, end_date, and time fields';
    }

    public function up(Schema $schema): void
    {
        // First, add the new columns without constraints
        $this->addSql('ALTER TABLE session ADD start_date DATE NULL, ADD end_date DATE NULL, ADD time TIME NULL');
        
        // Update the data - convert existing date to start_date and end_date, and start_time to time
        $this->addSql('UPDATE session SET 
            start_date = DATE(date), 
            end_date = DATE(date), 
            time = start_time 
            WHERE date IS NOT NULL AND start_time IS NOT NULL');
        
        // Now make the columns NOT NULL
        $this->addSql('ALTER TABLE session MODIFY start_date DATE NOT NULL, MODIFY end_date DATE NOT NULL, MODIFY time TIME NOT NULL');
        
        // Finally, drop the old columns
        $this->addSql('ALTER TABLE session DROP date, DROP start_time, DROP end_time');
    }

    public function down(Schema $schema): void
    {
        // First, add back the old columns without constraints
        $this->addSql('ALTER TABLE session ADD date DATETIME NULL, ADD start_time TIME NULL, ADD end_time TIME NULL');
        
        // Update the data - convert start_date and time back to date and start_time
        $this->addSql('UPDATE session SET 
            date = CONCAT(start_date, " ", "00:00:00"), 
            start_time = time, 
            end_time = time 
            WHERE start_date IS NOT NULL AND time IS NOT NULL');
        
        // Now make the columns NOT NULL
        $this->addSql('ALTER TABLE session MODIFY date DATETIME NOT NULL, MODIFY start_time TIME NOT NULL, MODIFY end_time TIME NOT NULL');
        
        // Finally, drop the new columns
        $this->addSql('ALTER TABLE session DROP start_date, DROP end_date, DROP time');
    }
}
