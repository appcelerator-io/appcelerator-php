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
    private string $name;
    private array $controllers;

    public function __construct(private Client $client, string $name)
    {
        $this->name = ucfirst(strtolower($name));
        $this->controllers = [];
    }

    public function getClient() : Client
    {
        return $this->client;
    }

    public function getServiceUrl() : string
    {
        return $this->client->getServiceUrl($this->name);
    }

    private function addController($name, $class)
    {
        $this->controllers[ucfirst($name)] = $class;
    }

    private function initController(string $name)
    {
        $controllerName = ucfirst($name) . "Controller";

        $filePath = dirname(__FILE__) . "/../Controllers/".$this->name."/$controllerName.php";

        if(!is_file($filePath))
            throw new AppCeleratorException("Path '$filePath' could not be found");

        include_once $filePath;

        if(!is_dir(dirname($filePath)))
            throw new AppCeleratorException("Directory '".dirname($filePath)."' could not be found");
        
        $results = array_values(preg_grep("/^$controllerName\.php$/i", scandir(dirname($filePath))));

        if(count($results) == 0)
            throw new AppCeleratorException("OAuth2 controller for '$name' could not be found");
            
        $fileName = reset($results);

        $className = substr($fileName, 0, strpos($fileName, "."));

        $this->addController($name, '\\AppCelerator\\Controllers\\'.$this->name.'\\'.$className);
    }

    public function __get($name)
    {
        $name = ucfirst(strtolower($name)); // Lower to make sure 'lower', then make first char uppercase

        if(!array_key_exists($name, $this->controllers))
            $this->initController($name);

        return new $this->controllers[$name]($this);
    }
}