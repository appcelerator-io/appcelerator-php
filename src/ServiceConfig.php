<?php
declare(strict_types=1);

namespace AppCelerator;

use AppCelerator\Exceptions\AppCeleratorException;

/**
 * ServiceConfig
 *  One service the client calls:
 *      name        - e.g. "countries", $client->countries
 *      url         - its base URL, e.g. https://myapp.countries.eu.appcelerator.io
 *      urls        - other base URLs of the service that may receive its token, e.g. a test or internal URL
 *      resource    - its resourceURI at the OAuth2 service, default api://{name}-service
 *      scope       - the scopes requested for its token, default {resource}/.default: every scope the client holds on it
 *      hosts       - other hosts of the service that may receive its token (regional and tenant URLs), "*" is one DNS label,
 *                    default {name}.*.appcelerator.io, *.{name}.*.appcelerator.io and *.{name}.*.*.appcelerator.io (https only)
 */
final class ServiceConfig
{
    public readonly string $name;
    public readonly string $url;
    public readonly array $urls;
    public readonly string $resource;
    public readonly string $scope;
    public readonly array $hosts;

    public function __construct(string $name, string $url, null|string $resource = null, null|string $scope = null, null|array $hosts = null, array $urls = [])
    {
        $this->name = self::normalizeName($name);

        foreach([$url, ...$urls] as $baseUrl)
            if(!is_string($baseUrl) || !self::isHttpUrl($baseUrl))
                throw new AppCeleratorException("Invalid URL '" . (is_string($baseUrl) ? $baseUrl : gettype($baseUrl)) . "' for service '$this->name'");

        $this->url = rtrim($url, "/");
        $this->urls = array_values(array_unique(array_map(fn($baseUrl) => rtrim($baseUrl, "/"), $urls)));
        $this->resource = $resource ?? "api://$this->name-service";
        $this->scope = $scope ?? "$this->resource/.default";
        $this->hosts = $hosts ?? ["$this->name.*.appcelerator.io", "*.$this->name.*.appcelerator.io", "*.$this->name.*.*.appcelerator.io"];
    }

    /**
     * from
     *  $config: the URL, or ["url" => ..., "urls" => [...], "resource" => ..., "scope" => ..., "hosts" => [...]]
     */
    public static function from(string $name, string|array $config) : self
    {
        if(is_string($config))
            return new self($name, $config);

        if(!is_string($config["url"] ?? null))
            throw new AppCeleratorException("The URL of service '$name' is missing");

        return new self($name, $config["url"], $config["resource"] ?? null, $config["scope"] ?? null, $config["hosts"] ?? null, (array) ($config["urls"] ?? []));
    }

    /**
     * normalizeName
     */
    public static function normalizeName(string $name) : string
    {
        return strtolower(trim($name));
    }

    /**
     * url
     *  The absolute URL of $path on this service
     */
    public function url(string $path = "") : string
    {
        return $this->url . ($path === "" ? "" : "/" . ltrim($path, "/"));
    }

    /**
     * matchesUrl
     *  Whether $url is of this service, so it may receive the token of this service: under one of its base URLs, or on one of the
     *  hosts (https). Never a URL with credentials in it.
     */
    public function matchesUrl(string $url) : bool
    {
        $target = parse_url($url);

        if(!is_array($target) || !isset($target["scheme"], $target["host"]) || isset($target["user"]) || isset($target["pass"]))
            return false;

        // Under one of the base URLs
        foreach([$this->url, ...$this->urls] as $baseUrl)
            if(self::isUnder($target, parse_url($baseUrl)))
                return true;

        // Another host of the service
        if(strtolower($target["scheme"]) !== "https" || isset($target["port"]))
            return false;

        $host = strtolower($target["host"]);

        foreach($this->hosts as $pattern)
        {
            $regexp = "/^" . str_replace("\\*", "[a-z0-9-]+", preg_quote(strtolower($pattern), "/")) . "$/D";

            if(preg_match($regexp, $host))
                return true;
        }

        return false;
    }

    /**
     * isUnder
     *  The same scheme, host and port as $base, and a path at or below its path
     */
    private static function isUnder(array $target, array $base) : bool
    {
        if(strtolower($target["scheme"]) !== strtolower($base["scheme"]) || strtolower($target["host"]) !== strtolower($base["host"]) || ($target["port"] ?? null) !== ($base["port"] ?? null))
            return false;

        $basePath = rtrim($base["path"] ?? "", "/");
        $path = $target["path"] ?? "/";

        return $basePath === "" || $path === $basePath || str_starts_with($path, "$basePath/");
    }

    /**
     * isHttpUrl
     */
    private static function isHttpUrl(string $url) : bool
    {
        $parts = parse_url($url);

        return is_array($parts) && in_array(strtolower($parts["scheme"] ?? ""), ["http", "https"], true) && isset($parts["host"]);
    }
}
