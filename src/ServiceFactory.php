<?php
declare(strict_types=1);

namespace AppCelerator\Factories;

use AppCelerator\Client;
use AppCelerator\Exceptions\AppCeleratorException;

/**
 * ServiceFactory
 */
class ServiceFactory
{
    public function __construct(private Client $client, private array $services = [])
    { }

    private function addService($name, $class)
    {
        $this->services[$name] = $class;
    }

    private function initService(string $name)
    {
        $serviceName = $name . "service";

        $path = dirname(__FILE__) . "/services/$serviceName.php";

        if(is_file($path))
            include_once $path;

        $results = array_values(preg_grep("/$serviceName\.php$/i", scandir(dirname($path))));
        
        if(count($results) == 0)
            throw new AppCeleratorException("Service '$name' could not be found");

        $fileName = reset($results);

        $className = substr($fileName, 0, strpos($fileName, "."));

        $this->addService($name, '\\AppCelerator\\Services\\'.$className);
    }

    public function __get($name)
    {
        $name = strtolower($name);

        if(!array_key_exists($name, $this->services))
            $this->initService($name);

        return new $this->services[$name]($this->client);
    }
}