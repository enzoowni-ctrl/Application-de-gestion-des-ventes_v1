<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630150637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bareme (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, produit VARCHAR(50) NOT NULL, offre VARCHAR(255) NOT NULL, prime NUMERIC(10, 2) NOT NULL, date_effet DATE NOT NULL, date_fin DATE DEFAULT NULL, actif BOOLEAN NOT NULL)');
        $this->addSql('CREATE TABLE work_hours (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, periode VARCHAR(7) NOT NULL, heures NUMERIC(6, 2) NOT NULL, updated_at TIMESTAMP NOT NULL, agent_id INT NOT NULL, saisi_par_id INT DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_A2E1C6A23414710B ON work_hours (agent_id)');
        $this->addSql('CREATE INDEX IDX_A2E1C6A2B0F809FE ON work_hours (saisi_par_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AGENT_PERIODE ON work_hours (agent_id, periode)');
        $this->addSql('ALTER TABLE sale ADD COLUMN prime NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN manager_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_8D93D649783E3463 ON user (manager_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE bareme');
        $this->addSql('DROP TABLE work_hours');
        $this->addSql('CREATE TABLE sale_backup (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, n_commande VARCHAR(50) NOT NULL, bascule VARCHAR(50) DEFAULT NULL, domaine VARCHAR(20) NOT NULL, type VARCHAR(50) NOT NULL, offre VARCHAR(255) NOT NULL, porta BOOLEAN NOT NULL, pto BOOLEAN NOT NULL, convergence BOOLEAN NOT NULL, point_de_vente VARCHAR(255) DEFAULT NULL, valeur NUMERIC(10, 2) NOT NULL, statut VARCHAR(20) NOT NULL, motif_rejet TEXT DEFAULT NULL, date_validation TIMESTAMP DEFAULT NULL, created_at TIMESTAMP NOT NULL, agent_id INT NOT NULL, valide_par_id_id INT DEFAULT NULL)');
        $this->addSql('INSERT INTO sale_backup SELECT id, date, n_commande, bascule, domaine, type, offre, porta, pto, convergence, point_de_vente, valeur, statut, motif_rejet, date_validation, created_at, agent_id, valide_par_id_id FROM sale');
        $this->addSql('DROP TABLE sale');
        $this->addSql('ALTER TABLE sale_backup RENAME TO sale');
        $this->addSql('CREATE INDEX IDX_E54BC0053414710B ON sale (agent_id)');
        $this->addSql('CREATE INDEX IDX_E54BC00525A40043 ON sale (valide_par_id_id)');
        $this->addSql('CREATE TABLE user_backup (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO user_backup SELECT id, email, roles, password FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE user_backup RENAME TO user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
