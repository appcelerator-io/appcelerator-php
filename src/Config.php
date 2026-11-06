<?php
declare(strict_types=1);

namespace AppCelerator;

use AppCelerator\Auth\Credentials;
use AppCelerator\Auth\Env;
use AppCelerator\Auth\Store\TokenStore;
use AppCelerator\Exceptions\AppCeleratorException;

/**
 * Config
 *  How the client reaches the services and gets their tokens:
 *      tokenUrl        - the token endpoint of the OAuth2 service, e.g. https://myapp.oauth2.eu.appcelerator.io/token
 *      credentials     - the client the tokens are requested for (Credentials::of / Credentials::fromEnv)
 *      services        - name => URL, or name => ["url", "resource", "scope", "hosts"], see ServiceConfig
 *      headers         - sent with every request, e.g. ["identifier" => "myapp.dev"]
 *      tokenStore      - where tokens are cached, default APCu when enabled, otherwise a file (see TokenProvider::defaultStore)
 *      tokenCacheFile  - the path of that file
 *      timeout         - seconds per request
 */
final class Config
{
    /** @var ServiceConfig[] name => config */
    private array $services = [];

    public function __construct(
        public readonly null|string $tokenUrl = null,
        public readonly null|Credentials $credentials = null,
        array $services = [],
        public readonly array $headers = [],
        public readonly null|TokenStore $tokenStore = null,
        public readonly null|string $tokenCacheFile = null,
        public readonly int $timeout = 30,
    )
    {
        foreach($services as $name => $service)
        {
            if(!is_string($name))
                throw new AppCeleratorException("Services are configured as name => URL or name => [\"url\" => ...]");

            $service = $service instanceof ServiceConfig ? $service : ServiceConfig::from($name, $service);

            $this->services[$service->name] = $service;
        }
    }

    /**
     * fromEnv
     *  The usual environment variables of the platform:
     *      APC_AUTH_TOKEN_URI (or OAUTH2_SERVICE_URI + /token), APC_CLIENT_ID, APC_CLIENT_SECRET,
     *      {NAME}_SERVICE_URI for every service (OAUTH2_SERVICE_URI => oauth2, FILES_SERVICE_URI => files, ...),
     *      RESOURCE_DOMAIN_IDENTIFIER as the identifier header
     *  $services: settings per service on top of its URL, e.g. ["countries" => ["scope" => "countries.country:read"]]
     */
    public static function fromEnv(array $services = [], array $headers = [], null|TokenStore $tokenStore = null, null|string $tokenCacheFile = null, int $timeout = 30) : self
    {
        $configured = [];

        foreach(Env::all() as $variable => $url)
            if(preg_match('/^([A-Z0-9]+(?:_[A-Z0-9]+)*)_SERVICE_URI$/D', (string) $variable, $matches))
                $configured[strtolower(str_replace("_", "-", $matches[1]))] = ["url" => $url];

        foreach($services as $name => $settings)
            $configured[ServiceConfig::normalizeName($name)] = array_merge($configured[ServiceConfig::normalizeName($name)] ?? [], is_string($settings) ? ["url" => $settings] : $settings);

        $tokenUrl = Env::get("APC_AUTH_TOKEN_URI") ?? (isset($configured["oauth2"]["url"]) ? rtrim($configured["oauth2"]["url"], "/") . "/token" : null);

        $credentials = Env::get("APC_CLIENT_ID") !== null && Env::get("APC_CLIENT_SECRET") !== null ? Credentials::fromEnv() : null;

        $identifier = Env::get("RESOURCE_DOMAIN_IDENTIFIER");

        return new self($tokenUrl, $credentials, $configured, $headers + ($identifier !== null ? ["identifier" => $identifier] : []), $tokenStore, $tokenCacheFile, $timeout);
    }

    /**
     * services
     *
     * @return ServiceConfig[]
     */
    public function services() : array
    {
        return $this->services;
    }

    /**
     * service
     */
    public function service(string $name) : null|ServiceConfig
    {
        return $this->services[ServiceConfig::normalizeName($name)] ?? null;
    }

    /**
     * serviceForUrl
     *  The service $url belongs to, null for a URL of no configured service
     */
    public function serviceForUrl(string $url) : null|ServiceConfig
    {
        foreach($this->services as $service)
            if($service->matchesUrl($url))
                return $service;

        return null;
    }

    /**
     * canRequestTokens
     */
    public function canRequestTokens() : bool
    {
        return $this->tokenUrl !== null && $this->credentials !== null;
    }
}
