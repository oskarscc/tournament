<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180102054145 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, branch INT DEFAULT NULL, Name VARCHAR(255) NOT NULL, Division VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C4E0A61FFE11D138 (Name), INDEX IDX_C4E0A61FBB861B1F (branch), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE result (id INT AUTO_INCREMENT NOT NULL, home_team INT DEFAULT NULL, guest_team INT DEFAULT NULL, win TINYINT(1) NOT NULL, level VARCHAR(255) NOT NULL, division_name VARCHAR(255) DEFAULT NULL, points INT NOT NULL, INDEX IDX_136AC113E5C617D0 (home_team), INDEX IDX_136AC113E05AFB42 (guest_team), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE branch (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(1) NOT NULL, UNIQUE INDEX UNIQ_BB861B1F5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FBB861B1F FOREIGN KEY (branch) REFERENCES branch (id)');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113E5C617D0 FOREIGN KEY (home_team) REFERENCES team (id)');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113E05AFB42 FOREIGN KEY (guest_team) REFERENCES team (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113E5C617D0');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113E05AFB42');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61FBB861B1F');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE result');
        $this->addSql('DROP TABLE branch');
    }
}
