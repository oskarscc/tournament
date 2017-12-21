<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171221211436 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_136AC113E05AFB42');
        $this->addSql('DROP INDEX IDX_136AC113E5C617D0');
        $this->addSql('CREATE TEMPORARY TABLE __temp__result AS SELECT id, home_team, guest_team, win, level, points, division_name FROM result');
        $this->addSql('DROP TABLE result');
        $this->addSql('CREATE TABLE result (id INTEGER NOT NULL, home_team INTEGER DEFAULT NULL, guest_team INTEGER DEFAULT NULL, win BOOLEAN NOT NULL, level VARCHAR(255) NOT NULL COLLATE BINARY, points INTEGER NOT NULL, division_name VARCHAR(255) DEFAULT NULL COLLATE BINARY, branch VARCHAR(1) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_136AC113E5C617D0 FOREIGN KEY (home_team) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_136AC113E05AFB42 FOREIGN KEY (guest_team) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO result (id, home_team, guest_team, win, level, points, division_name) SELECT id, home_team, guest_team, win, level, points, division_name FROM __temp__result');
        $this->addSql('DROP TABLE __temp__result');
        $this->addSql('CREATE INDEX IDX_136AC113E05AFB42 ON result (guest_team)');
        $this->addSql('CREATE INDEX IDX_136AC113E5C617D0 ON result (home_team)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_136AC113E5C617D0');
        $this->addSql('DROP INDEX IDX_136AC113E05AFB42');
        $this->addSql('CREATE TEMPORARY TABLE __temp__result AS SELECT id, home_team, guest_team, win, level, division_name, points FROM result');
        $this->addSql('DROP TABLE result');
        $this->addSql('CREATE TABLE result (id INTEGER NOT NULL, home_team INTEGER DEFAULT NULL, guest_team INTEGER DEFAULT NULL, win BOOLEAN NOT NULL, level VARCHAR(255) NOT NULL, division_name VARCHAR(255) DEFAULT NULL, points INTEGER NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO result (id, home_team, guest_team, win, level, division_name, points) SELECT id, home_team, guest_team, win, level, division_name, points FROM __temp__result');
        $this->addSql('DROP TABLE __temp__result');
        $this->addSql('CREATE INDEX IDX_136AC113E5C617D0 ON result (home_team)');
        $this->addSql('CREATE INDEX IDX_136AC113E05AFB42 ON result (guest_team)');
    }
}
