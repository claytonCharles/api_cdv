<?php

namespace App\Model\Mapper;

use App\Utils\SalvarLog;
use Exception;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\TableGateway;

class UsuarioMapper
{   
    /** @var SalvarLog */
    public $logger;

    public function __construct(
        private AdapterInterface $adapter
    ) {
        $this->logger = new SalvarLog($adapter);
    }

        
    /**
     * Cadastra o usuário ao sistema, e retorna o seu ID, caso o cadastro do mesmo ocorra com sucesso.
     * @param array $dadosUsuario
     * @return int
     */
    public function cadastrarUsuario(array $dadosUsuario): int
    {
        $result = 0;
        try {
            $tbData = new TableGateway("tb_usuarios", $this->adapter);
            $tbData->insert($dadosUsuario);
            $result = $tbData->getLastInsertValue();
        } catch (Exception $error) {
            $this->logger->error("Não foi possível cadastrar o usuário devido ao erro: {$error->getMessage()}");
        }

        return $result;
    }


    /**
     * @param int $coUsuario
     * @param array $dadosUsuario
     * @return bool
     */
    public function atualizarDadosUsuario(int $coUsuario, array $dadosUsuario): bool
    {
        $result = false;
        try {
            $tbData = new TableGateway("tb_usuarios", $this->adapter);
            $tbData->update($dadosUsuario, "co_usuario = $coUsuario");
            $result = true;
        } catch (Exception $error) {
            $this->logger->error("Não foi atualizar os dados do usuário $coUsuario devido ao erro: {$error->getMessage()}");
        }

        return $result;
    }


    /**
     * Resgata as informações do usuário de acordo com seu id
     * @param int $coUsuario
     * @return array
     */
    public function resgatarDadosUsuario(int $coUsuario): array
    {
        $result = [];
        try {
            $tbData = new TableGateway("tb_usuarios", $this->adapter);
            $sql = $tbData->getSql()->select()
                                    ->columns(["co_usuario", "ds_nome", "ds_email", "dt_registro", "st_email_validado"])
                                    ->join("tb_grupos", "tb_grupos.co_usuario = tb_usuarios.co_usuario", ["dt_registro_permissao" => "dt_registro", "dt_limite_permissao" => "dt_limite"], Select::JOIN_LEFT)
                                    ->join("tb_permissoes", "tb_permissoes.co_permissao = tb_grupos.co_permissao", ["tp_permissao" => "ds_nome"], Select::JOIN_LEFT)
                                    ->where("tb_usuarios.co_usuario = $coUsuario AND tb_usuarios.st_ativo = 'S'");
            $result = $tbData->selectWith($sql)->toArray();
        } catch (Exception $error) {
            $this->logger->error("Não foi possível resgatar os dados do usuário $coUsuario devido ao erro: {$error->getMessage()}");
        }

        return $result;
    }
}