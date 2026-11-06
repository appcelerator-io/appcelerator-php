<?php
declare(strict_types=1);

namespace AppCelerator\Factories;

use AppCelerator\Client;
use AppCelerator\Exceptions\AppCeleratorException;
use AppCelerator\Services\Service;

/**
 * ServiceFactory
 *  $client->{name}: the service with predefined endpoints (Services/{Name}Service.php), or for another configured service
 *  (e.g. backup) a Service with request() only
 */
class ServiceFactory
{
    private array $services;

    public function __construct(protected Client $client, private array $opts = [])
    {
        $this->services = [];
    }

    private function addService($name, $class)
    {
        $this->services[$name] = $class;
    }

    /**
     * initService
     *  False when there is no class for the service
     */
    private function initService(string $name) : bool
    {
        $serviceName = $name . "Service";

        $directory = dirname(__FILE__) . "/../Services";

        $results = array_values(preg_grep("/^" . preg_quote($serviceName, "/") . "\.php$/i", scandir($directory)));

        if(count($results) == 0 || strtolower($serviceName) === "service")
            return false;

        include_once "$directory/$results[0]";

        $this->addService($name, '\\AppCelerator\\Services\\' . substr($results[0], 0, strpos($results[0], ".")));

        return true;
    }

    public function __get($name)
    {
        $name = ucfirst(strtolower($name));

        if(!array_key_exists($name, $this->services) && !$this->initService($name))
        {
            if(!$this->client->hasService($name))
                throw new AppCeleratorException("Service '$name' could not be found, configure its URL first");

            return new Service($this->client, $name, $this->opts);
        }

        return new $this->services[$name]($this->client, $this->opts);
    }
}
