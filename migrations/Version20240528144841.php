<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240528144841 extends AbstractMigration
{
    public function up(Schema $schema): void
    {   
        //Tables
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

        $this->addSql(
            'CREATE TABLE htb_usuarios (
                co_htb_usuario bigint auto_increment not null primary key, 
                co_usuario bigint not null,
                ds_nome varchar(100) not null, 
                ds_email varchar(100) not null,
                dt_registro datetime not null,
                st_ativo char(1) not null,
                st_email_validado char(1),
                Foreign key (co_usuario) references tb_usuarios(co_usuario)
            );'
        );

        $this->addSql(
            'CREATE TABLE tb_permissoes (
                co_permissao bigint auto_increment not null primary key,
                ds_nome varchar(20) not null unique,
                dt_registro datetime not null default current_timestamp, 
                st_ativo char(1) not null default "S"
            );'
        );

        $this->addSql(
            'CREATE TABLE tb_grupos (
                co_grupo bigint auto_increment not null primary key,
                co_usuario bigint not null unique,
                co_permissao bigint not null,
                dt_registro datetime not null default current_timestamp,
                dt_limite datetime,
                st_ativo char(1) not null default "S",
                Foreign key (co_usuario) references tb_usuarios(co_usuario),
                Foreign key (co_permissao) references tb_permissoes(co_permissao)
            );'
       );

        $this->addSql(
            'CREATE TABLE htb_grupos (
                co_htb_grupo bigint auto_increment not null primary key,
                co_grupo bigint not null,
                co_usuario bigint not null,
                co_permissao bigint not null,
                dt_registro datetime not null,
                dt_limite datetime,
                st_ativo char(1) not null,
                Foreign key (co_grupo) references tb_grupos(co_grupo),
                Foreign key (co_usuario) references tb_usuarios(co_usuario),
                Foreign key (co_permissao) references tb_permissoes(co_permissao)
            );'
        );

        $this->addSql(
            'CREATE TABLE tb_logs (
                co_log bigint auto_increment not null primary key,
                ds_level varchar(10) not null,
                ds_message varchar(255) not null,
                dt_registro datetime not null default current_timestamp
            );'
        );

        //Triggers
        $this->addSql('CREATE TRIGGER htb_usuarios_insert AFTER INSERT ON tb_usuarios FOR EACH ROW
            BEGIN
                INSERT INTO htb_usuarios (co_usuario, ds_nome, ds_email, dt_registro, st_ativo, st_email_validado)
                SELECT a.co_usuario, a.ds_nome, a.ds_email, a.dt_registro, a.st_ativo, a.st_email_validado 
                FROM tb_usuarios as a WHERE a.co_usuario = NEW.co_usuario;
            END
        ');

        $this->addSql('CREATE TRIGGER htb_usuarios_update AFTER UPDATE ON tb_usuarios FOR EACH ROW
            BEGIN
                INSERT INTO htb_usuarios (co_usuario, ds_nome, ds_email, dt_registro, st_ativo, st_email_validado)
                SELECT a.co_usuario, a.ds_nome, a.ds_email, a.dt_registro, a.st_ativo, a.st_email_validado 
                FROM tb_usuarios as a WHERE a.co_usuario = NEW.co_usuario;
            END
        ');

        $this->addSql('CREATE TRIGGER htb_grupos_insert AFTER INSERT ON tb_grupos FOR EACH ROW
            BEGIN
                INSERT INTO htb_grupos (co_grupo, co_usuario, co_permissao, dt_registro, dt_limite, st_ativo)
                SELECT a.co_grupo, a.co_usuario, a.co_permissao, a.dt_registro, a.dt_limite, a.st_ativo
                FROM tb_grupos as a WHERE a.co_grupo = NEW.co_grupo;
            END
        ');

        $this->addSql('CREATE TRIGGER htb_grupos_update AFTER UPDATE ON tb_grupos FOR EACH ROW
            BEGIN
                INSERT INTO htb_grupos (co_grupo, co_usuario, co_permissao, dt_registro, dt_limite, st_ativo)
                SELECT a.co_grupo, a.co_usuario, a.co_permissao, a.dt_registro, a.dt_limite, a.st_ativo
                FROM tb_grupos as a WHERE a.co_grupo = NEW.co_grupo;
            END
        ');


        //Inserts default
        $this->addSql("INSERT INTO tb_permissoes (ds_nome) VALUES ('comum')");
        $this->addSql("INSERT INTO tb_permissoes (ds_nome) VALUES ('premium')");
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("htb_grupos");
        $schema->dropTable("tb_grupos");
        $schema->dropTable("htb_usuarios");
        $schema->dropTable("tb_usuarios");
        $schema->dropTable("tb_permissoes");
        $schema->dropTable("tb_logs");
    }
}
