<?php
declare(strict_types=1);

namespace AppCelerator;

use AppCelerator\Exceptions\AppCeleratorException;

/**
 * ServiceConfig
 *  One service the client calls:
 *      name        - e.g. "countries", $client->countries
 *      url         - its base URL, e.g. https://myapp.countries.eu.appcelerator.io
 *      resource    - its resourceURI at the OAuth2 service, default api://{name}-service
 *      scope       - the scopes requested for its token, default {resource}/.default: every scope the client holds on it
 *      hosts       - other hosts of the service that may receive its token (regional and tenant URLs), "*" is one DNS label,
 *                    default *.{name}.*.appcelerator.io and *.{name}.*.*.appcelerator.io (https only)
 */
final class ServiceConfig
{
    public readonly string $name;
    public readonly string $url;
    public readonly string $resource;
    public readonly string $scope;
    public readonly array $hosts;

    public function __construct(string $name, string $url, null|string $resource = null, null|string $scope = null, null|array $hosts = null)
    {
        $this->name = self::normalizeName($name);

        if(!self::isHttpUrl($url))
            throw new AppCeleratorException("Invalid URL '$url' for service '$this->name'");

        $this->url = rtrim($url, "/");
        $this->resource = $resource ?? "api://$this->name-service";
        $this->scope = $scope ?? "$this->resource/.default";
        $this->hosts = $hosts ?? ["*.$this->name.*.appcelerator.io", "*.$this->name.*.*.appcelerator.io"];
    }

    /**
     * from
     *  $config: the URL, or ["url" => ..., "resource" => ..., "scope" => ..., "hosts" => [...]]
     */
    public static function from(string $name, string|array $config) : self
    {
        if(is_string($config))
            return new self($name, $config);

        if(!is_string($config["url"] ?? null))
            throw new AppCeleratorException("The URL of service '$name' is missing");

        return new self($name, $config["url"], $config["resource"] ?? null, $config["scope"] ?? null, $config["hosts"] ?? null);
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
     *  Whether $url is of this service, so it may receive the token of this service: under the base URL, or on one of the
     *  hosts (https). Never a URL with credentials in it.
     */
    public function matchesUrl(string $url) : bool
    {
        $target = parse_url($url);
        $base = parse_url($this->url);

        if(!is_array($target) || !isset($target["scheme"], $target["host"]) || isset($target["user"]) || isset($target["pass"]))
            return false;

        $scheme = strtolower($target["scheme"]);
        $host = strtolower($target["host"]);

        // Under the base URL
        if($scheme === strtolower($base["scheme"]) && $host === strtolower($base["host"]) && ($target["port"] ?? null) === ($base["port"] ?? null))
        {
            $basePath = rtrim($base["path"] ?? "", "/");
            $path = $target["path"] ?? "/";

            if($basePath === "" || $path === $basePath || str_starts_with($path, "$basePath/"))
                return true;
        }

        // Another host of the service
        if($scheme !== "https" || isset($target["port"]))
            return false;

        foreach($this->hosts as $pattern)
        {
            $regexp = "/^" . str_replace("\\*", "[a-z0-9-]+", preg_quote(strtolower($pattern), "/")) . "$/D";

            if(preg_match($regexp, $host))
                return true;
        }

        return false;
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
