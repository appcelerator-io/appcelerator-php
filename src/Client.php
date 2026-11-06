<?php
declare(strict_types=1);

namespace AppCelerator;

use AppCelerator\Auth\OAuthClient;
use AppCelerator\Auth\Store\MemoryTokenStore;
use AppCelerator\Auth\Store\TokenStore;
use AppCelerator\Auth\TokenProvider;
use AppCelerator\Exceptions\AppCeleratorException;
use AppCelerator\Factories\ServiceFactory;
use AppCelerator\Services\Service;
use AppCelerator\Utils\Headers;
use AppCelerator\Utils\Response;

/**
 * Client
 *  With a Config the client requests, caches and renews a token per service itself (client credentials). onBehalfOf() acts
 *  for a user (token exchange), forTenant() in a tenant of that user. With a token (or a function that returns a token per
 *  service) and the service URLs, the client uses that token as is (SDK 1.x).
 *
 * @property \AppCelerator\Services\Oauth2Service $oauth2
 * @property \AppCelerator\Services\CountriesService $countries
 * @property \AppCelerator\Services\FilesService $files
 */
class Client extends ServiceFactory
{
    private null|string|\Closure $key = null;
    private null|Config $config = null;

    /** @var ServiceConfig[] name => config: without a Config the services, with one those added by withService() */
    private array $serviceConfigs = [];

    private null|TokenProvider $tokens = null; // of the client itself
    private null|TokenProvider $userTokens = null; // exchanged on behalf of a user
    private null|string $subjectToken = null;
    private null|string $tenant = null;
    private array $headers = [];

    /**
     * @param string|\Closure|Config $key   a Config, or (SDK 1.x) the access token for every service, or a function that
     *                                      returns the token for a service: fn(string $service): string, called with the
     *                                      service name ("oauth2", "countries", "files")
     * @param array $serviceUrls            without a Config: name => URL
     * @param array $opts                   headers: sent with every request
     */
    public function __construct(string|\Closure|Config $key, array $serviceUrls = [], private array $opts = [])
    {
        if($key instanceof Config)
        {
            $this->config = $key;
            $this->headers = Headers::merge($key->headers, $opts["headers"] ?? []);

            if($key->canRequestTokens())
            {
                $clientId = $key->credentials->clientId;
                $store = $key->tokenStore ?? TokenProvider::defaultStore($clientId, $key->tokenCacheFile);

                $this->tokens = new TokenProvider(new OAuthClient($key->tokenUrl, $key->credentials, min($key->timeout, 30)), $store, $clientId);
            }
        }
        else
        {
            $this->key = $key;
            $this->headers = $opts["headers"] ?? [];

            foreach($serviceUrls as $name => $url)
            {
                if(!is_string($url))
                    throw new AppCeleratorException("Invalid service url using type " . gettype($url));

                $service = new ServiceConfig((string) $name, $url);
                $this->serviceConfigs[$service->name] = $service;
            }
        }

        parent::__construct($this, ["headers" => $this->headers] + $opts);
    }

    /**
     * __clone
     *  onBehalfOf() and forTenant() return a copy, its services belong to the copy
     */
    public function __clone()
    {
        $this->client = $this;
    }

    /**
     * onBehalfOf
     *  A client that acts for the user of $subjectToken: every service gets a token of that user (token exchange), with the
     *  scopes the user holds and this client may use on behalf of users (delegated). The exchanged tokens are kept in $store
     *  (e.g. the session of the user), by default in memory for this process. Never in the shared store of the client.
     */
    public function onBehalfOf(#[\SensitiveParameter] string $subjectToken, null|TokenStore $store = null) : static
    {
        if($this->tokens === null)
            throw new AppCeleratorException("onBehalfOf needs a Config with a tokenUrl and credentials");

        $client = clone $this;
        $client->subjectToken = $subjectToken;
        $client->tenant = null;
        $client->userTokens = $store !== null ? $this->tokens->withStore($store) : ($this->userTokens ?? $this->tokens->withStore(new MemoryTokenStore()));

        return $client;
    }

    /**
     * forTenant
     *  A client that acts in tenant $tenant (its identifier, e.g. myapp.dev) for the user of onBehalfOf(): the tokens are of the
     *  tenant, with the scopes of the roles the user holds for it. The identifier header of the requests is the tenant.
     */
    public function forTenant(string $tenant) : static
    {
        if($this->subjectToken === null)
            throw new AppCeleratorException("A tenant token is exchanged from the token of a user, use onBehalfOf() first");

        $client = clone $this;
        $client->tenant = $tenant;
        $client->headers = Headers::merge($this->headers, ["identifier" => $tenant]);

        return $client;
    }

    /**
     * withService
     *  A client with one more service (or another config for one), e.g. a service whose URL is only known at runtime such as
     *  the API of a cluster: withService("kubernetes-client", ["url" => $clientApiUrl, "resource" => "api://kubernetes-client"]).
     *  It shares the token cache of this client: every cluster gets the same token for api://kubernetes-client.
     *
     * @param string|array|ServiceConfig $config the URL, or the settings of ServiceConfig::from
     */
    public function withService(string $name, string|array|ServiceConfig $config) : static
    {
        $service = $config instanceof ServiceConfig ? $config : ServiceConfig::from($name, $config);

        $client = clone $this;
        $client->serviceConfigs[$service->name] = $service;

        return $client;
    }

    /**
     * service
     *  The service $name, also one without predefined endpoints (e.g. $client->service("backup")->request(...))
     */
    public function service(string $name) : Service
    {
        return $this->__get($name);
    }

    /**
     * request
     *  A request to the absolute $url of a configured service, with the token of that service (see Service::request). A URL of
     *  no configured service is refused: the token never goes elsewhere.
     */
    public function request(string $method, string $url, array $options = []) : Response
    {
        $service = null;

        foreach($this->serviceConfigs() as $config)
        {
            if($config->matchesUrl($url))
            {
                $service = $config;
                break;
            }
        }

        if($service === null)
            throw new AppCeleratorException("'$url' is not a URL of a configured service");

        return $this->service($service->name)->request($method, $url, $options);
    }

    /**
     * getKey
     *  The access token for $service (lowercase service name): the token of the Config for that service, or the key of SDK 1.x
     */
    public function getKey(null|string $service = null) : string
    {
        if($this->config !== null)
        {
            $config = $this->getServiceConfig((string) $service);

            if($this->subjectToken !== null)
                return $this->userTokens->get($config->scope, $this->subjectToken, $this->tenant);

            if($this->tokens === null)
                throw new AppCeleratorException("No token for service '$service', the Config has no tokenUrl and credentials");

            return $this->tokens->get($config->scope);
        }

        if(is_string($this->key))
            return $this->key;

        $key = ($this->key)(strtolower((string) $service));

        if(!is_string($key))
            throw new AppCeleratorException("Invalid key for service '$service', the key function must return a string");

        return $key;
    }

    /**
     * canRenewKey
     *  Whether the client requested the token itself, so a rejected one can be replaced (see forgetKey)
     */
    public function canRenewKey() : bool
    {
        return $this->config !== null && $this->tokens !== null;
    }

    /**
     * forgetKey
     *  Drops the token of $service, e.g. when the service rejected it (401): the next request gets a new one
     */
    public function forgetKey(string $service) : void
    {
        if(!$this->canRenewKey())
            return;

        $config = $this->getServiceConfig($service);

        if($this->subjectToken !== null)
            $this->userTokens->forget($config->scope, $this->subjectToken, $this->tenant);
        else
            $this->tokens->forget($config->scope);
    }

    /**
     * tokens
     *  The tokens of the client itself, e.g. tokens()->forgetAll() after the OAuth2 service was installed again
     */
    public function tokens() : null|TokenProvider
    {
        return $this->tokens;
    }

    /**
     * getHeaders
     *  Sent with every request
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * getTimeout
     */
    public function getTimeout() : int
    {
        return $this->config?->timeout ?? 30;
    }

    /**
     * hasService
     */
    public function hasService(string $name) : bool
    {
        return isset($this->serviceConfigs()[ServiceConfig::normalizeName($name)]);
    }

    /**
     * getServiceConfig
     */
    public function getServiceConfig(string $name) : ServiceConfig
    {
        $service = $this->serviceConfigs()[ServiceConfig::normalizeName($name)] ?? null;

        if($service === null)
            throw new AppCeleratorException("Service url not found for service '" . ucfirst(strtolower($name)) . "', please initialize the url first");

        return $service;
    }

    public function getServiceUrl(string $name)
    {
        return $this->getServiceConfig($name)->url;
    }

    /**
     * serviceConfigs
     *
     * @return ServiceConfig[]
     */
    private function serviceConfigs() : array
    {
        return $this->config !== null ? array_merge($this->config->services(), $this->serviceConfigs) : $this->serviceConfigs;
    }
}
