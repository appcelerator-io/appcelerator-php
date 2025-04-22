<?php
declare(strict_types=1);

namespace AppCelerator;

use AppCelerator\Exceptions\AppCeleratorException;
use AppCelerator\Factories\ServiceFactory;

/**
 * Client
 * 
 * @property \AppCelerator\Services\Oauth2Service $oauth2
 * @property \AppCelerator\Services\CountriesService $countries
 * @property \AppCelerator\Services\FilesService $files
 */
class Client extends ServiceFactory
{
    private array $serviceUrls;

    public function __construct(private string $key, array $serviceUrls = [], private array $opts = [])
    {
        parent::__construct($this, $opts);

        $this->initServiceUrls($serviceUrls);
    }

    public function getKey()
    {
        return $this->key;
    }

    private function initServiceUrls(array $serviceUrls)
    {
        foreach($serviceUrls as $serviceName => $url)
        {
            if(!is_string($url))
                throw new AppCeleratorException("Invalid service url using" . (is_string($url) ? " $url" : " type " . gettype($url)));
            else
                $this->serviceUrls[ucfirst(strtolower($serviceName))] = strtolower($url);
        }
    }
    
    public function getServiceUrl(string $name)
    {
        $name = ucfirst(strtolower($name));

        if(!array_key_exists($name, $this->serviceUrls))
            throw new AppCeleratorException("Service url not found for service '$name', please initialize the url first");

        return $this->serviceUrls[$name];
    }
}