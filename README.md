Projeto api_cdv (Coisas da Vida)

Este pequeno projeto será uma API a qual irá implementar com outras APIs, em linguagens diferentes, para poder demonstrar minhas práticas em back-end, tendo em vista que back-end não é algo visual.

Este projeto será implementado em um framework de front-end futuramente, a qual terá um visual simples e bem genérico, devido ao mesmo não ser o meu foco, e sim em back-end.

## Init projeto
Antes da inicialização do projeto, e necessário seguir alguns passos.

# 1 - Clonar o repositório.
    Comando:
        - git clone https://github.com/claytonCharles/api_cdv.git

# 2 - Instalação do docker.
    Recomendado em caso de Windows, usar o docker dentro do Linux, via WSL, para melhor performance.
        - Documentação para o WSL: https://learn.microsoft.com/pt-br/windows/wsl/install
        - Documentação docker para Linux: https://docs.docker.com/desktop/install/linux-install/

# 3 - Criação de Redes Docker.
    O Projeto utiliza redes personalizadas, em vez das padrões criadas pelo docker.
    Comandos em Linux:
        - sudo docker network create frontend
        - sudo docker network create backend

# 4 - Configurar o Docker-compose.yml
    E necessário alterar os caminhos dos arquivos no docker-compose.yml, substituindo "set_you_path_application" pelo caminho da aplicação.
    Juntamente com as configurações da conexão ao banco de dados.
    Exemplos:
        web:
          container_name: api_cdv
          build:
            context: /home/cdv/projetos/api_cdv
          restart: unless-stopped
          volumes:
            - /home/cdv/projetos/api_cdv/public:/var/www/html
            - /home/cdv/projetos/api_cdv:/var/www
            - /home/cdv/projetos/api_cdv/vendor:/var/www/vendor
            - /home/cdv/projetos/api_cdv/data/tmp:/tmp
            - /home/cdv/projetos/api_cdv/data:/var/www/data
            - /home/cdv/projetos/api_cdv/data/log/docker:/var/log
            - /home/cdv/projetos/ftp:/var/ftp
        mysql:
          container_name: database
          image: 'mysql:8.0'
          working_dir: /application
          volumes:
            - '.:/application'
          environment:
            - MYSQL_ROOT_PASSWORD=012345
            - MYSQL_DATABASE=dev
            - MYSQL_USER=manager
            - MYSQL_PASSWORD=012345
          ports:
            - '25002:3306'

# 5 - Criação de arquivo para configurações.

Para que o projeto consiga realizar as funções de conexão ao banco de dados e autenticação dos token JWT, e necessário a adição de suas configurações bases no projeto.

- Criação do arquivo de configuração, altere as informações necessárias.
    - Comando: echo '<?php

declare(strict_types=1);

return [
    "db" => [
        "database" => "dev",
        "username" => "manager",
        "password" => "012345",
        "host"     => "172.17.0.1",
        "driver"   => "mysqli",
        "port"     => "25002",
    ],

    jwt" => [
        "validAudience" => "exemplo.exemplo",
        "validIssue" => "exemplo.exemplo.exemplo",
        "key" => "sua-chave-super-secreta"
    ],
];' > ./config/autoload/teste.local.php