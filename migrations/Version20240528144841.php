<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Usuarios extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE tb_usuarios (
                co_usuario bigint auto_increment not null primary key, 
                ds_nome varchar(100) not null, 
                ds_email varchar(100) not null unique,
                ds_senha varchar(60) not null,
                ds_recuperacao_senha varchar(60),
                dt_registro datetime not null default current_timestamp,
                st_ativo char(1) not null default "S",
                st_email_validado char(1) not null default "N"
            );'
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("tb_usuarios");
    }
}
