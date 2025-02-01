<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;
use AppCelerator\Exceptions\AppCeleratorException;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * OAuth2Service
 * 
 * @property \AppCelerator\Controllers\OAuth2\UsersController $users
 * @property \AppCelerator\Controllers\OAuth2\ClientsController $clients
 * @property \AppCelerator\Controllers\OAuth2\ScopesController $scopes
 * @property \AppCelerator\Controllers\OAuth2\RolesController $roles
 */
class OAuth2Service implements ServiceInterface
{
    private array $controllers;

    public function __construct(private Client $client)
    {
        $this->controllers = [];
    }

    public function getClient() : Client
    {
        return $this->client;
    }

    public function getServiceUrl() : string
    {
        return $this->client->getServiceUrl("oauth2");
    }

    private function addController($name, $class)
    {
        $this->controllers[$name] = $class;
    }

    private function initController(string $name)
    {
        $controllerName = "{$name}controller";

        $filePath = dirname(__FILE__) . "/../controllers/oauth2/$controllerName";

        if(is_file($filePath))
            include_once $filePath;
        
        $results = array_values(preg_grep("/$controllerName\.php$/i", scandir(dirname($filePath))));

        if(count($results) == 0)
            throw new AppCeleratorException("OAuth2 controller for '$name' could not be found");
            
        $fileName = reset($results);

        $className = substr($fileName, 0, strpos($fileName, "."));

        $this->addController($name, '\\AppCelerator\\Controllers\\OAuth2\\'.$className);
    }

    public function __get($name)
    {
        $name = strtolower($name);

        if(!array_key_exists($name, $this->controllers))
            $this->initController($name);

        return new $this->controllers[$name]($this);
    }
}