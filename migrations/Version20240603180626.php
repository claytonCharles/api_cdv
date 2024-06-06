<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Permissoes extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE tb_permissoes (
                co_permissao bigint auto_increment not null primary key,
                ds_nome varchar(20) not null unique,
                dt_registro datetime not null default current_timestamp, 
                st_ativo char(1) not null default "S"
            );'
        );

        $this->addSql("INSERT INTO tb_permissoes (ds_nome) VALUES ('comum')");
        $this->addSql("INSERT INTO tb_permissoes (ds_nome) VALUES ('premium')");
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("tb_permissoes");
    }
}
