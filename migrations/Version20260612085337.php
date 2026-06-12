<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260612085337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE company (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(255) DEFAULT NULL, yahoo_ticker VARCHAR(50) DEFAULT NULL, is_active BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_COMPANY_CODE ON company (code)');
        $this->addSql('CREATE TABLE data_source_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, source VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, total_success INTEGER DEFAULT 0 NOT NULL, total_partial INTEGER DEFAULT 0 NOT NULL, total_failed INTEGER DEFAULT 0 NOT NULL, message CLOB DEFAULT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE TABLE stock_data_staging (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(50) DEFAULT NULL, ticker VARCHAR(50) DEFAULT NULL, source VARCHAR(50) DEFAULT \'yahoo_finance\' NOT NULL, price DOUBLE PRECISION DEFAULT NULL, open_price DOUBLE PRECISION DEFAULT NULL, high_price DOUBLE PRECISION DEFAULT NULL, low_price DOUBLE PRECISION DEFAULT NULL, volume BIGINT DEFAULT NULL, pe_ratio DOUBLE PRECISION DEFAULT NULL, eps DOUBLE PRECISION DEFAULT NULL, market_cap DOUBLE PRECISION DEFAULT NULL, currency VARCHAR(20) DEFAULT NULL, market_time DATETIME DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, raw_payload CLOB DEFAULT NULL, missing_fields CLOB DEFAULT NULL, error_message CLOB DEFAULT NULL, fetched_at DATETIME NOT NULL, company_id INTEGER DEFAULT NULL, CONSTRAINT FK_F832E5C7979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F832E5C7979B1AD6 ON stock_data_staging (company_id)');
        $this->addSql('CREATE INDEX IDX_STOCK_DATA_STAGING_CODE ON stock_data_staging (code)');
        $this->addSql('CREATE INDEX IDX_STOCK_DATA_STAGING_SOURCE ON stock_data_staging (source)');
        $this->addSql('CREATE INDEX IDX_STOCK_DATA_STAGING_STATUS ON stock_data_staging (status)');
        $this->addSql('CREATE INDEX IDX_STOCK_DATA_STAGING_FETCHED_AT ON stock_data_staging (fetched_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE data_source_log');
        $this->addSql('DROP TABLE stock_data_staging');
    }
}
