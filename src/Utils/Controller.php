<?php
declare(strict_types=1);

namespace AppCelerator\Controllers;

use AppCelerator\Exceptions\AppCeleratorHttpException;
use AppCelerator\Interfaces\ControllerInterface;
use AppCelerator\Interfaces\ServiceInterface;
use AppCelerator\Utils\Curl;
use AppCelerator\Utils\Response;

use function AppCelerator\format_uri;

/**
 * Controller
 */
class Controller extends Curl implements ControllerInterface
{
    public function __construct(private ServiceInterface $service, public string $basePath = "")
    {
        parent::__construct($service->getClient()->getKey());
    }

    private function getEndpointUrl(string $path)
    {
        return format_uri($this->service->getServiceUrl(), $this->basePath, $path);
    }

    public function curl(string $method, string $path, array $data = [], array $headers = [], bool $verbose = false, bool $debug = false) : Response
    {
        $response = self::call($method, $this->getEndpointUrl($path), $data, $headers, $verbose, $debug);

        $this->handleResponseErrors($response);

        return $response->getParameters();
    }

    public function handleResponseErrors(Response $response)
    {
        if(!$response->isSuccessful())
            throw new AppCeleratorHttpException($response);
    }
}