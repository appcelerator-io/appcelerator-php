<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;
use AppCelerator\Exceptions\AppCeleratorException;
use AppCelerator\Exceptions\AppCeleratorHttpException;
use AppCelerator\Interfaces\ServiceInterface;
use AppCelerator\Utils\Headers;
use AppCelerator\Utils\HttpClient;
use AppCelerator\Utils\Response;

/**
 * Service
 */
class Service implements ServiceInterface
{
    private array $controllers;

    public function __construct(private Client $client, private string $name, private array $opts = [])
    {
        $this->name = $name;
        $this->controllers = [];
    }

    public function getClient() : Client
    {
        return $this->client;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getServiceUrl() : string
    {
        return $this->client->getServiceUrl($this->name);
    }

    /**
     * request
     *  Calls any endpoint of this service with the token of this service, which the client requests, caches and renews (see
     *  Client). $path is relative to the URL of the service ("/country/nl/"), or an absolute URL of this service (e.g. a
     *  regional or tenant host, see ServiceConfig::hosts); a URL of another host is refused, the token never goes elsewhere.
     *
     *  $options:
     *      query       - query parameters
     *      json        - a body sent as JSON
     *      form        - a body sent as application/x-www-form-urlencoded
     *      multipart   - multipart fields, a value may be a CURLFile
     *      body        - a raw body (string), e.g. a body passed on as is; set its Content-Type in headers
     *      headers     - name => value, on top of the headers of the client
     *      token       - a token of your own instead of the one of the client, it is never renewed
     *      auth        - false: no token at all
     *      timeout     - seconds
     *      retry       - false: no new token and second attempt when the service rejects the token (401)
     *      throw       - true: an unsuccessful response throws AppCeleratorHttpException
     *
     *  Returns the response, whatever its status (unless throw).
     */
    public function request(string $method, string $path = "", array $options = []) : Response
    {
        $config = $this->client->getServiceConfig($this->name);

        if(preg_match('#^[a-z][a-z0-9+.-]*://#i', $path))
        {
            if(!$config->matchesUrl($path))
                throw new AppCeleratorException("'$path' is not a URL of service '$config->name'");

            $url = $path;
        }
        else
        {
            $url = $config->url($path);
        }

        if(!empty($options["query"]))
            $url .= (str_contains($url, "?") ? "&" : "?") . http_build_query($options["query"], "", "&", PHP_QUERY_RFC3986);

        [$body, $contentType] = self::body($options);

        $headers = Headers::merge(
            $this->opts["headers"] ?? [],
            $this->client->getHeaders(),
            $contentType !== null ? ["Content-Type" => $contentType] : [],
            $options["headers"] ?? [],
        );

        $renewable = false;

        if(!Headers::has($headers, "Authorization") && ($options["auth"] ?? true) !== false)
        {
            if(isset($options["token"]))
            {
                $headers["Authorization"] = "Bearer " . $options["token"];
            }
            else
            {
                $headers["Authorization"] = "Bearer " . $this->client->getKey($this->name);
                $renewable = $this->client->canRenewKey();
            }
        }

        $timeout = (int) ($options["timeout"] ?? $this->client->getTimeout());

        $response = HttpClient::send($method, $url, $body, $headers, $timeout);

        // A rejected token of the client (e.g. signed with a key that no longer exists): a new token, once
        if($renewable && $response->getStatusCode() === 401 && ($options["retry"] ?? true) !== false)
        {
            $this->client->forgetKey($this->name);
            $headers["Authorization"] = "Bearer " . $this->client->getKey($this->name);

            $response = HttpClient::send($method, $url, $body, $headers, $timeout);
        }

        if(($options["throw"] ?? false) && !$response->isSuccessful())
            throw new AppCeleratorHttpException($response);

        return $response;
    }

    /**
     * body
     *  [body, Content-Type] of the request options
     */
    private static function body(array $options) : array
    {
        return match(true)
        {
            array_key_exists("json", $options)      => [json_encode($options["json"], JSON_THROW_ON_ERROR), "application/json"],
            array_key_exists("form", $options)      => [http_build_query((array) $options["form"], "", "&", PHP_QUERY_RFC3986), "application/x-www-form-urlencoded"],
            array_key_exists("multipart", $options) => [(array) $options["multipart"], null], // curl sets the boundary
            array_key_exists("body", $options)      => [(string) $options["body"], null],
            default                                 => [null, null],
        };
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
        $name = ucfirst($name);

        if(!array_key_exists($name, $this->controllers))
            $this->initController($name);

        return new $this->controllers[$name]($this, $this->opts);
    }
}