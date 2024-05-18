<?php

declare(strict_types=1);

namespace App;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => [],
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [],
            'factories'  => $this->getFactories(),
        ];
    }

    /**
     * Returns the factories configuration
     * @return array
     */
    public function getFactories(): array
    {
        $lstFactories = [];
        $moduleName = basename(realpath(__DIR__ . "/../"));
        $handlers = glob(__DIR__ . "/Handler/*");

        foreach ($handlers as $handler) {
            $handlerName = basename(realpath($handler));
            $actions = glob($handler . "/*Handler.php");

            foreach ($actions as $action) {
                $actionName = basename(realpath($action), ".php");
                $factoryName = str_replace("Handler", "Factory", $actionName);
                $lstFactories["$moduleName\Handler\\$handlerName\\$actionName"] = "$moduleName\Handler\\$handlerName\\$factoryName";
            }
        }
        return $lstFactories;
    }
}
