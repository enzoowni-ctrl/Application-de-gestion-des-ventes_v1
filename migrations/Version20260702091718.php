<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702091718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__sale AS SELECT id, date, n_commande, bascule, domaine, type, offre, porta, pto, convergence, point_de_vente, valeur, statut, motif_rejet, date_validation, created_at, agent_id, valide_par_id_id, prime FROM sale');
        $this->addSql('DROP TABLE sale');
        $this->addSql('CREATE TABLE sale (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, n_commande VARCHAR(50) NOT NULL, bascule VARCHAR(50) DEFAULT NULL, domaine VARCHAR(20) NOT NULL, type VARCHAR(50) NOT NULL, offre VARCHAR(255) NOT NULL, porta BOOLEAN NOT NULL, pto BOOLEAN NOT NULL, convergence BOOLEAN NOT NULL, point_de_vente VARCHAR(255) DEFAULT NULL, valeur NUMERIC(10, 2) NOT NULL, statut VARCHAR(20) NOT NULL, motif_rejet CLOB DEFAULT NULL, date_validation DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, agent_id INTEGER NOT NULL, valide_par_id_id INTEGER DEFAULT NULL, prime NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_E54BC0053414710B FOREIGN KEY (agent_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E54BC00525A40043 FOREIGN KEY (valide_par_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO sale (id, date, n_commande, bascule, domaine, type, offre, porta, pto, convergence, point_de_vente, valeur, statut, motif_rejet, date_validation, created_at, agent_id, valide_par_id_id, prime) SELECT id, date, n_commande, bascule, domaine, type, offre, porta, pto, convergence, point_de_vente, valeur, statut, motif_rejet, date_validation, created_at, agent_id, valide_par_id_id, prime FROM __temp__sale');
        $this->addSql('DROP TABLE __temp__sale');
        $this->addSql('CREATE INDEX IDX_E54BC00525A40043 ON sale (valide_par_id_id)');
        $this->addSql('CREATE INDEX IDX_E54BC0053414710B ON sale (agent_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, manager_id FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, manager_id INTEGER DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, log_admcc VARCHAR(50) DEFAULT NULL, CONSTRAINT FK_8D93D649783E3463 FOREIGN KEY (manager_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user (id, email, roles, password, manager_id) SELECT id, email, roles, password, manager_id FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE INDEX IDX_8D93D649783E3463 ON user (manager_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__work_hours AS SELECT id, periode, heures, updated_at, agent_id, saisi_par_id FROM work_hours');
        $this->addSql('DROP TABLE work_hours');
        $this->addSql('CREATE TABLE work_hours (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, periode VARCHAR(7) NOT NULL, heures NUMERIC(6, 2) NOT NULL, updated_at DATETIME NOT NULL, agent_id INTEGER NOT NULL, saisi_par_id INTEGER DEFAULT NULL, CONSTRAINT FK_A2E1C6A23414710B FOREIGN KEY (agent_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A2E1C6A2B0F809FE FOREIGN KEY (saisi_par_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO work_hours (id, periode, heures, updated_at, agent_id, saisi_par_id) SELECT id, periode, heures, updated_at, agent_id, saisi_par_id FROM __temp__work_hours');
        $this->addSql('DROP TABLE __temp__work_hours');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AGENT_PERIODE ON work_hours (agent_id, periode)');
        $this->addSql('CREATE INDEX IDX_A2E1C6A2B0F809FE ON work_hours (saisi_par_id)');
        $this->addSql('CREATE INDEX IDX_A2E1C6A23414710B ON work_hours (agent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__sale AS SELECT id, date, n_commande, bascule, domaine, type, offre, porta, pto, convergence, point_de_vente, valeur, statut, motif_rejet, date_validation, created_at, prime, agent_id, valide_par_id_id FROM sale');
        $this->addSql('DROP TABLE sale');
        $this->addSql('CREATE TABLE sale (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, n_commande VARCHAR(50) NOT NULL, bascule VARCHAR(50) DEFAULT NULL, domaine VARCHAR(20) NOT NULL, type VARCHAR(50) NOT NULL, offre VARCHAR(255) NOT NULL, porta BOOLEAN NOT NULL, pto BOOLEAN NOT NULL, convergence BOOLEAN NOT NULL, point_de_vente VARCHAR(255) DEFAULT NULL, valeur NUMERIC(10, 2) NOT NULL, statut VARCHAR(20) NOT NULL, motif_rejet CLOB DEFAULT NULL, date_validation DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, prime NUMERIC(10, 2) DEFAULT NULL, agent_id INTEGER NOT NULL, valide_par_id_id INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO sale (id, date, n_commande, bascule, domaine, type, offre, porta, pto, convergence, point_de_vente, valeur, statut, motif_rejet, date_validation, created_at, prime, agent_id, valide_par_id_id) SELECT id, date, n_commande, bascule, domaine, type, offre, porta, pto, convergence, point_de_vente, valeur, statut, motif_rejet, date_validation, created_at, prime, agent_id, valide_par_id_id FROM __temp__sale');
        $this->addSql('DROP TABLE __temp__sale');
        $this->addSql('CREATE INDEX IDX_E54BC0053414710B ON sale (agent_id)');
        $this->addSql('CREATE INDEX IDX_E54BC00525A40043 ON sale (valide_par_id_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, manager_id FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles VARCHAR NOT NULL, password VARCHAR(255) NOT NULL, manager_id INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, manager_id) SELECT id, email, roles, password, manager_id FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE INDEX IDX_8D93D649783E3463 ON "user" (manager_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__work_hours AS SELECT id, periode, heures, updated_at, agent_id, saisi_par_id FROM work_hours');
        $this->addSql('DROP TABLE work_hours');
        $this->addSql('CREATE TABLE work_hours (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, periode VARCHAR(7) NOT NULL, heures NUMERIC(6, 2) NOT NULL, updated_at DATETIME NOT NULL, agent_id INTEGER NOT NULL, saisi_par_id INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO work_hours (id, periode, heures, updated_at, agent_id, saisi_par_id) SELECT id, periode, heures, updated_at, agent_id, saisi_par_id FROM __temp__work_hours');
        $this->addSql('DROP TABLE __temp__work_hours');
        $this->addSql('CREATE INDEX IDX_A2E1C6A23414710B ON work_hours (agent_id)');
        $this->addSql('CREATE INDEX IDX_A2E1C6A2B0F809FE ON work_hours (saisi_par_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AGENT_PERIODE ON work_hours (agent_id, periode)');
    }
}
