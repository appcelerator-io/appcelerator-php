<?php
declare(strict_types=1);

namespace AppCelerator\Tests;

use AppCelerator\Config;
use AppCelerator\ServiceConfig;
use PHPUnit\Framework\TestCase;

/**
 * ServiceConfigTest
 *  Which URLs may receive the token of a service, and the configuration from the environment
 */
final class ServiceConfigTest extends TestCase
{
    private array $saved = [];

    protected function tearDown() : void
    {
        foreach($this->saved as $name => $value)
            putenv($value === false ? $name : "$name=$value");
    }

    private function env(array $variables) : void
    {
        foreach($variables as $name => $value)
        {
            $this->saved[$name] ??= getenv($name);
            putenv($value === null ? $name : "$name=$value");
        }
    }

    public function testTheDefaultsFollowTheName() : void
    {
        $service = ServiceConfig::from("Countries", "https://myapp.countries.eu.appcelerator.io/");

        $this->assertSame("countries", $service->name);
        $this->assertSame("https://myapp.countries.eu.appcelerator.io", $service->url);
        $this->assertSame("api://countries-service", $service->resource);
        $this->assertSame("api://countries-service/.default", $service->scope);
        $this->assertSame("https://myapp.countries.eu.appcelerator.io/country/nl/", $service->url("/country/nl/"));
    }

    public function testAScopeCanBeSet() : void
    {
        $service = ServiceConfig::from("countries", ["url" => "https://x.test", "scope" => "countries.country:read"]);

        $this->assertSame("countries.country:read", $service->scope);
    }

    public function testUrlsOfTheService() : void
    {
        $service = ServiceConfig::from("oauth2", "http://localhost/apc-oauth2-service");

        $this->assertTrue($service->matchesUrl("http://localhost/apc-oauth2-service"));
        $this->assertTrue($service->matchesUrl("http://localhost/apc-oauth2-service/service-domain/"));
        $this->assertTrue($service->matchesUrl("https://myapp.oauth2.eu.appcelerator.io/service-domain/"));
        $this->assertTrue($service->matchesUrl("https://myapp.oauth2.dev.eu.appcelerator.io/role/"));
    }

    public function testUrlsOfOtherHostsAreRefused() : void
    {
        $service = ServiceConfig::from("oauth2", "http://localhost/apc-oauth2-service");

        $this->assertFalse($service->matchesUrl("http://localhost/apc-files-service/file/"), "Another service on the same host");
        $this->assertFalse($service->matchesUrl("http://localhost/apc-oauth2-service-evil/"), "A path that only starts the same");
        $this->assertFalse($service->matchesUrl("https://myapp.files.eu.appcelerator.io/"), "Another service");
        $this->assertFalse($service->matchesUrl("http://myapp.oauth2.eu.appcelerator.io/"), "Not https");
        $this->assertFalse($service->matchesUrl("https://myapp.oauth2.eu.appcelerator.io.evil.com/"));
        $this->assertFalse($service->matchesUrl("https://user:pass@myapp.oauth2.eu.appcelerator.io/"), "Credentials in the URL");
        $this->assertFalse($service->matchesUrl("https://myapp.oauth2.eu.appcelerator.io:8443/"), "Another port");
        $this->assertFalse($service->matchesUrl("https://evil.com/?x=https://myapp.oauth2.eu.appcelerator.io/"));
    }

    public function testTheConfigFindsTheServiceOfAUrl() : void
    {
        $config = new Config("http://localhost/apc-oauth2-service/token", null, [
            "oauth2" => "http://localhost/apc-oauth2-service",
            "files" => "http://localhost/apc-files-service",
        ]);

        $this->assertSame("files", $config->serviceForUrl("http://localhost/apc-files-service/file/x")->name);
        $this->assertSame("oauth2", $config->serviceForUrl("https://myapp.oauth2.eu.appcelerator.io/")->name);
        $this->assertNull($config->serviceForUrl("https://api.stripe.com/v1/charges"));
    }

    public function testTheEnvironmentConfiguresThePlatformServices() : void
    {
        $this->env([
            "APC_AUTH_TOKEN_URI" => null,
            "APC_CLIENT_ID" => "client_env",
            "APC_CLIENT_SECRET" => "secret",
            "OAUTH2_SERVICE_URI" => "http://localhost/apc-oauth2-service",
            "KUBERNETES_CLIENT_SERVICE_URI" => "http://localhost/apc-kubernetes-client",
            "RESOURCE_DOMAIN_IDENTIFIER" => "appcelerator.dev",
        ]);

        $config = Config::fromEnv(["countries" => ["url" => "http://localhost/apc-countries-service", "scope" => "countries.country:read"]]);

        $this->assertSame("http://localhost/apc-oauth2-service/token", $config->tokenUrl);
        $this->assertSame("client_env", $config->credentials->clientId);
        $this->assertSame("http://localhost/apc-oauth2-service", $config->service("oauth2")->url);
        $this->assertSame("api://kubernetes-client-service", $config->service("kubernetes-client")->resource);
        $this->assertSame("countries.country:read", $config->service("countries")->scope);
        $this->assertSame(["identifier" => "appcelerator.dev"], $config->headers);
        $this->assertTrue($config->canRequestTokens());
    }

    public function testTheSecretIsNeverPrinted() : void
    {
        $this->assertStringNotContainsString("s3cr3t", print_r(\AppCelerator\Auth\Credentials::of("client_x", "s3cr3t"), true));
    }
}
