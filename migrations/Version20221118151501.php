<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221118151501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE director ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE director ADD CONSTRAINT FK_1E90D3F0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1E90D3F0A76ED395 ON director (user_id)');
        $this->addSql('ALTER TABLE professor ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE professor ADD CONSTRAINT FK_790DD7E3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_790DD7E3A76ED395 ON professor (user_id)');
        $this->addSql('ALTER TABLE student ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B723AF33A76ED395 ON student (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE director DROP FOREIGN KEY FK_1E90D3F0A76ED395');
        $this->addSql('DROP INDEX UNIQ_1E90D3F0A76ED395 ON director');
        $this->addSql('ALTER TABLE director DROP user_id');
        $this->addSql('ALTER TABLE professor DROP FOREIGN KEY FK_790DD7E3A76ED395');
        $this->addSql('DROP INDEX UNIQ_790DD7E3A76ED395 ON professor');
        $this->addSql('ALTER TABLE professor DROP user_id');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33A76ED395');
        $this->addSql('DROP INDEX UNIQ_B723AF33A76ED395 ON student');
        $this->addSql('ALTER TABLE student DROP user_id');
    }
}
