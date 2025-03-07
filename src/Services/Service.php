<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;
use AppCelerator\Exceptions\AppCeleratorException;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * Service
 */
class Service implements ServiceInterface
{
    private array $controllers;

    public function __construct(private Client $client, private string $name)
    {
        $this->controllers = [];
    }

    public function getClient() : Client
    {
        return $this->client;
    }

    public function getServiceUrl() : string
    {
        return $this->client->getServiceUrl(strtolower($this->name));
    }

    private function addController($name, $class)
    {
        $this->controllers[$name] = $class;
    }

    private function initController(string $name)
    {
        $controllerName = "{$name}controller";

        $filePath = dirname(__FILE__) . "/../controllers/".strtolower($this->name)."/$controllerName";

        if(is_file($filePath))
            include_once $filePath;
        
        $results = array_values(preg_grep("/^$controllerName\.php$/i", scandir(dirname($filePath))));

        if(count($results) == 0)
            throw new AppCeleratorException("OAuth2 controller for '$name' could not be found");
            
        $fileName = reset($results);

        $className = substr($fileName, 0, strpos($fileName, "."));

        $this->addController($name, '\\AppCelerator\\Controllers\\'.$this->name.'\\'.$className);
    }

    public function __get($name)
    {
        $name = strtolower($name);

        if(!array_key_exists($name, $this->controllers))
            $this->initController($name);

        return new $this->controllers[$name]($this);
    }
}