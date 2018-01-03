<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180103001106 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61FBB861B1F');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FBB861B1F FOREIGN KEY (branch) REFERENCES branch (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113E05AFB42');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113E5C617D0');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113E05AFB42 FOREIGN KEY (guest_team) REFERENCES team (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113E5C617D0 FOREIGN KEY (home_team) REFERENCES team (id) ON DELETE SET NULL');
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
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113E5C617D0 FOREIGN KEY (home_team) REFERENCES team (id)');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113E05AFB42 FOREIGN KEY (guest_team) REFERENCES team (id)');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61FBB861B1F');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FBB861B1F FOREIGN KEY (branch) REFERENCES branch (id)');
    }
}
