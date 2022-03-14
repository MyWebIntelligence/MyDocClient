<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220314100114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document CHANGE creator creator LONGTEXT DEFAULT NULL, CHANGE contributor contributor LONGTEXT DEFAULT NULL, CHANGE coverage coverage LONGTEXT DEFAULT NULL, CHANGE date date LONGTEXT DEFAULT NULL, CHANGE subject subject LONGTEXT DEFAULT NULL, CHANGE type type LONGTEXT DEFAULT NULL, CHANGE format format LONGTEXT DEFAULT NULL, CHANGE identifier identifier LONGTEXT DEFAULT NULL, CHANGE language language LONGTEXT DEFAULT NULL, CHANGE publisher publisher LONGTEXT DEFAULT NULL, CHANGE relation relation LONGTEXT DEFAULT NULL, CHANGE rights rights LONGTEXT DEFAULT NULL, CHANGE source source LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annotation CHANGE content content LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE comment comment LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE document CHANGE title title LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE creator creator VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE contributor contributor VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE coverage coverage VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE date date VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE subject subject VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE type type VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE format format VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE identifier identifier VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE language language VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE publisher publisher VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE relation relation VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE rights rights VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE source source VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE content content LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE document_link CHANGE content content LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE permission CHANGE role role VARCHAR(30) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE project CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE language language VARCHAR(2) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE link_hash link_hash VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE reset_password_request CHANGE selector selector VARCHAR(20) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE hashed_token hashed_token VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE tag CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE first_name first_name VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE last_name last_name VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE organization organization VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE title title VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
