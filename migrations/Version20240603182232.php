<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240603182232 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
       $this->addSql(
        'CREATE TABLE tb_grupo (
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
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable("tb_grupo");
    }
}
