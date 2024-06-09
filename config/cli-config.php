<?php

require 'public/index.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;

$config = require __DIR__ . "/autoload/". APP_AMBIENTE . ".local.php";
$configMigrations = new ConfigurationArray([
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
        'version_column_name' => 'version',
        'version_column_length' => 191,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],

    'migrations_paths' => [
        'Migrations' => getcwd() . '/migrations',
    ],

    'all_or_nothing' => true,
    'transactional' => true,
    'check_database_platform' => true,
    'organize_migrations' => 'none',
    'connection' => null,
    'em' => null,
]);

$conn = DriverManager::getConnection([
    "dbname"   => $config["db"]["database"],
    "user"     => $config["db"]["username"],
    "password" => $config["db"]["password"],
    "host"     => $config["db"]["host"],
    "driver"   => $config["db"]["driver"] ===  "mysqli" ? "pdo_mysql" : $config["db"]["driver"],
    "port"     => $config["db"]["port"],
]);
return DependencyFactory::fromConnection($configMigrations, new ExistingConnection($conn));