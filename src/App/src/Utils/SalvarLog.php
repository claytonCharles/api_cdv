<?php 

declare(strict_types = 1);

namespace App\Utils;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Log\AbstractLogger;

class SalvarLog extends AbstractLogger 
{
    public function __construct(
        public AdapterInterface $adapter
    ) {
    }

    /**
     * Classe simples para armazenamento de logs no banco.
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    function log($level, string|\Stringable $message, array $context = []): void
    {   
        $tbData = new TableGateway("tb_logs", $this->adapter);
        $infos = [
            "ds_level" => $level,
            "ds_message" => $message,
            "dt_registro" => date("Y-m-d H:i:s")
        ];
        $tbData->insert($infos);
    }
}