<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250224032055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE person_id_seq CASCADE');
        $this->addSql('CREATE TABLE people (id SERIAL NOT NULL, name VARCHAR(64) NOT NULL, surname VARCHAR(64) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('DROP TABLE person');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE person_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE person (id SERIAL NOT NULL, name VARCHAR(64) NOT NULL, surname VARCHAR(64) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('DROP TABLE people');
    }
}
