<?php
declare(strict_types=1);

namespace AppCelerator\Controllers;

use AppCelerator\Exceptions\AppCeleratorHttpException;
use AppCelerator\Interfaces\ControllerInterface;
use AppCelerator\Interfaces\ServiceInterface;
use AppCelerator\Utils\Curl;
use AppCelerator\Utils\Response;

/**
 * Controller
 */
class Controller extends Curl implements ControllerInterface
{
    private null|string|Response $response;

    public function __construct(private ServiceInterface $service, public string $basePath = "", private array $opts = [])
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
        $url = [];
        $url[] = str_must_not_end_with($this->service->getServiceUrl(), "/");
        $url[] = $basePath = str_must_start_with($this->basePath, "/");

        if(strlen($path) > 0)
        {
            if(!str_ends_with($basePath, "/"))
                $path = str_must_start_with($path, "/");
        }

        $url[] = $path;

        return implode($url);
    }

    public function curl(string $method, string $path, array $data = [], array $headers = [], bool $verbose = false, bool $debug = false) : array
    {
        $headers = array_merge(@$this->opts["headers"] ?? [], $headers);

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