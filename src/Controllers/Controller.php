<?php
declare(strict_types=1);

namespace AppCelerator\Controllers;

use AppCelerator\Exceptions\AppCeleratorHttpException;
use AppCelerator\Interfaces\ControllerInterface;
use AppCelerator\Interfaces\ServiceInterface;
use AppCelerator\Utils\Curl;
use AppCelerator\Utils\Response;

use function AppCelerator\format_uri;

include_once __DIR__ . "../../Library.php";

/**
 * Controller
 */
class Controller extends Curl implements ControllerInterface
{
    private null|string|Response $response;

    public function __construct(private ServiceInterface $service, public string $basePath = "")
    {
        parent::__construct($service->getClient()->getKey());

        $this->response = null;
    }

    public function getService()
    {
        return $this->service;
    }

    public function getResponse()
    {
        return $this->response;
    }

    private function getEndpointUrl(string $path)
    {
        return format_uri($this->service->getServiceUrl(), $this->basePath, $path);
    }

    public function curl(string $method, string $path, array $data = [], array $headers = [], bool $verbose = false, bool $debug = false) : Response
    {
        $this->response = self::call($method, $this->getEndpointUrl($path), $data, $headers, $verbose, $debug);

        $this->handleResponseErrors($this->response);

        return $this->response->getParameters();
    }

    public function handleResponseErrors(Response $response)
    {
        if(!$response->isSuccessful())
            throw new AppCeleratorHttpException($response);
    }
}