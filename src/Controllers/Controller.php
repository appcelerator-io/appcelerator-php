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

    private static function mustStartWith(string $input, string $prefix) : string
    {
        return str_starts_with($input, $prefix) ? $input : "$prefix$input";
    }

    private static function mustNotEndWith(string $input, string $suffix) : string
    {
        return str_ends_with($input, $suffix) ? substr($input, 0, -strlen($suffix)) : $input;
    }

    private function getEndpointUrl(string $path)
    {
        $url = [];
        $url[] = self::mustNotEndWith($this->service->getServiceUrl(), "/");
        $url[] = $basePath = self::mustStartWith($this->basePath, "/");

        if(strlen($path) > 0)
        {
            if(!str_ends_with($basePath, "/"))
                $path = self::mustStartWith($path, "/");
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
        // An empty body is a valid response (e.g. 204), any other body must be JSON
        if(!$response->isSuccessful() || (strlen($response->getContent()) > 0 && !$response->isJson()))
            throw new AppCeleratorHttpException($response);
    }
}