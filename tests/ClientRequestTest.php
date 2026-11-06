<?php
declare(strict_types=1);

namespace AppCelerator\Tests;

use AppCelerator\Auth\Credentials;
use AppCelerator\Auth\Store\MemoryTokenStore;
use AppCelerator\Client;
use AppCelerator\Config;
use AppCelerator\Exceptions\AppCeleratorException;
use AppCelerator\Exceptions\AppCeleratorHttpException;
use PHPUnit\Framework\TestCase;

/**
 * ClientRequestTest
 *  The client against a stub of the platform (tests/Fixtures/server.php): tokens per service, request(), on behalf of a user
 *  and in a tenant, a rejected token, refused URLs, and SDK 1.x
 */
final class ClientRequestTest extends TestCase
{
    private static $server;
    private static string $base;
    private static string $state;

    public static function setUpBeforeClass() : void
    {
        self::$state = sys_get_temp_dir() . "/appcelerator-stub-" . uniqid();
        mkdir(self::$state);

        $port = 18000 + random_int(0, 999);
        self::$base = "http://127.0.0.1:$port";

        self::$server = proc_open(
            [PHP_BINARY, "-S", "127.0.0.1:$port", __DIR__ . "/Fixtures/server.php"],
            [0 => ["pipe", "r"], 1 => ["file", "/dev/null", "w"], 2 => ["file", "/dev/null", "w"]],
            $pipes,
            null,
            ["STUB_STATE" => self::$state] + getenv(),
        );

        for($i = 0; $i < 50 && @fsockopen("127.0.0.1", $port) === false; $i++)
            usleep(100_000);
    }

    public static function tearDownAfterClass() : void
    {
        proc_terminate(self::$server);
        array_map("unlink", glob(self::$state . "/*"));
        rmdir(self::$state);
    }

    protected function setUp() : void
    {
        @unlink(self::$state . "/count");
        @unlink(self::$state . "/token-requests");
    }

    private function client(string $secret = "secret", array $services = []) : Client
    {
        return new Client(new Config(
            tokenUrl: self::$base . "/oauth2/token",
            credentials: Credentials::of("client_test", $secret),
            services: $services + [
                "oauth2" => self::$base . "/oauth2",
                "countries" => ["url" => self::$base . "/countries", "scope" => "countries.country:read"],
                "backup" => self::$base . "/backup",
            ],
            headers: ["identifier" => "appcelerator.dev"],
            tokenStore: new MemoryTokenStore(),
        ));
    }

    /** The token requests the stub received */
    private static function tokenRequests() : array
    {
        $lines = @file(self::$state . "/token-requests", FILE_IGNORE_NEW_LINES) ?: [];

        return array_map(fn($line) => json_decode($line, true), $lines);
    }

    public function testEveryServiceGetsItsOwnToken() : void
    {
        $client = $this->client();

        $countries = $client->countries->request("GET", "/country/nl/")->json()["data"];
        $backup = $client->backup->request("GET", "/policy/")->json()["data"];
        $again = $client->countries->request("GET", "/country/be/")->json()["data"];

        $this->assertSame(["Bearer t1", "Bearer t2", "Bearer t1"], [$countries["authorization"], $backup["authorization"], $again["authorization"]]);
        $this->assertSame(["countries.country:read", "api://backup-service/.default"], array_column(self::tokenRequests(), "scope"));
        $this->assertSame("client_credentials", self::tokenRequests()[0]["grant_type"]);
        $this->assertSame("appcelerator.dev", $countries["identifier"]);
    }

    public function testARequestCarriesItsOptions() : void
    {
        $client = $this->client();

        $json = $client->backup->request("POST", "/policy/", ["json" => ["name" => "daily"], "query" => ["dry" => "1"], "headers" => ["X-Custom" => "yes"]])->json()["data"];
        $form = $client->backup->request("PUT", "policy/x/", ["form" => ["name" => "weekly"]])->json()["data"];
        $raw = $client->backup->request("PATCH", "/policy/x/", ["body" => "<x/>", "headers" => ["Content-Type" => "application/xml"]])->json()["data"];
        $delete = $client->backup->request("DELETE", "/policy/x/")->json()["data"];

        $this->assertSame(["POST", "/backup/policy/", ["dry" => "1"], "application/json", '{"name":"daily"}', "yes"], [$json["method"], $json["path"], $json["query"], $json["contentType"], $json["body"], $json["custom"]]);
        $this->assertSame(["PUT", "application/x-www-form-urlencoded", "name=weekly"], [$form["method"], $form["contentType"], $form["body"]]);
        $this->assertSame(["application/xml", "<x/>"], [$raw["contentType"], $raw["body"]]);
        $this->assertSame("DELETE", $delete["method"]);
    }

    public function testAnErrorIsAResponseOrAnException() : void
    {
        $response = $this->client()->backup->request("GET", "/not-found/");

        $this->assertSame(404, $response->status());

        $this->expectException(AppCeleratorHttpException::class);
        $this->client()->backup->request("GET", "/not-found/", ["throw" => true]);
    }

    public function testARejectedTokenIsReplacedOnce() : void
    {
        $client = $this->client();

        $response = $client->backup->request("GET", "/reject-first/x/");

        $this->assertSame(200, $response->status());
        $this->assertSame("Bearer t2", $response->json()["data"]["authorization"]);
        $this->assertSame("Bearer t2", $client->backup->request("GET", "/x/")->json()["data"]["authorization"], "The new token is cached");
    }

    public function testATokenOfTheCallerIsNotReplaced() : void
    {
        $response = $this->client()->backup->request("GET", "/reject-first/x/", ["token" => "t1"]);

        $this->assertSame(401, $response->status());
        $this->assertSame([], self::tokenRequests());
    }

    public function testPredefinedEndpointsUseTheTokenOfTheirService() : void
    {
        $client = $this->client();

        $country = $client->countries->countries->get("nl");

        $this->assertSame("Bearer t1", $country["authorization"]);
        $this->assertSame("countries.country:read", self::tokenRequests()[0]["scope"]);
    }

    public function testAnAbsoluteUrlGoesToItsService() : void
    {
        $client = $this->client();

        $this->assertSame("/backup/policy/", $client->request("GET", self::$base . "/backup/policy/")->json()["data"]["path"]);
        $this->assertSame("/backup/policy/", $client->backup->request("GET", self::$base . "/backup/policy/")->json()["data"]["path"]);
    }

    public function testAUrlOfAnotherHostNeverGetsAToken() : void
    {
        $this->expectException(AppCeleratorException::class);

        $this->client()->backup->request("GET", self::$base . "/countries/country/");
    }

    public function testAUrlOfNoServiceIsRefused() : void
    {
        $this->expectException(AppCeleratorException::class);

        $this->client()->request("GET", "https://api.example.com/");
    }

    public function testOnBehalfOfAUserTheTokenIsExchanged() : void
    {
        $user = $this->client()->onBehalfOf("user-login-token");

        $first = $user->backup->request("GET", "/x/")->json()["data"];
        $second = $user->backup->request("GET", "/x/")->json()["data"];

        $exchange = self::tokenRequests()[0];

        $this->assertSame(["Bearer t1", "Bearer t1"], [$first["authorization"], $second["authorization"]]);
        $this->assertSame("urn:ietf:params:oauth:grant-type:token-exchange", $exchange["grant_type"]);
        $this->assertSame("user-login-token", $exchange["subject_token"]);
        $this->assertSame("api://backup-service/.default", $exchange["scope"]);
        $this->assertArrayNotHasKey("tenant", $exchange);
    }

    public function testInATenantTheTokenAndIdentifierAreOfTheTenant() : void
    {
        $client = $this->client();
        $tenant = $client->onBehalfOf("user-login-token")->forTenant("myapp.dev");

        $data = $tenant->oauth2->request("GET", "/role/")->json()["data"];
        $own = $client->oauth2->request("GET", "/role/")->json()["data"];

        $this->assertSame("myapp.dev", self::tokenRequests()[0]["tenant"]);
        $this->assertSame("myapp.dev", $data["identifier"]);
        $this->assertSame("appcelerator.dev", $own["identifier"], "The client itself is unchanged");
        $this->assertNotSame($data["authorization"], $own["authorization"]);
    }

    public function testAServiceAddedAtRuntimeSharesTheTokens() : void
    {
        $client = $this->client();

        $clusterA = $client->withService("kubernetes-client", ["url" => self::$base . "/cluster-a", "resource" => "api://kubernetes-client"]);
        $clusterB = $client->withService("kubernetes-client", ["url" => self::$base . "/cluster-b", "resource" => "api://kubernetes-client"]);

        $a = $clusterA->service("kubernetes-client")->request("GET", "/pods/")->json()["data"];
        $b = $clusterB->service("kubernetes-client")->request("GET", "/pods/")->json()["data"];

        $this->assertSame(["/cluster-a/pods/", "/cluster-b/pods/"], [$a["path"], $b["path"]]);
        $this->assertSame(["Bearer t1", "Bearer t1"], [$a["authorization"], $b["authorization"]]);
        $this->assertSame(["api://kubernetes-client/.default"], array_column(self::tokenRequests(), "scope"));
        $this->assertFalse($client->hasService("kubernetes-client"), "The client itself is unchanged");

        $this->expectException(AppCeleratorException::class);
        $clusterA->service("kubernetes-client")->request("GET", self::$base . "/cluster-b/pods/");
    }

    public function testOneParameterOfAResponse() : void
    {
        $response = $this->client()->backup->request("GET", "/x/");

        $this->assertSame("GET", $response->getParameter("data")["method"]);
        $this->assertSame("none", $response->getParameter("missing", "none"));
    }

    public function testATenantNeedsAUser() : void
    {
        $this->expectException(AppCeleratorException::class);

        $this->client()->forTenant("myapp.dev");
    }

    public function testWrongCredentialsThrowWithTheOAuthError() : void
    {
        try
        {
            $this->client("wrong")->backup->request("GET", "/x/");
            $this->fail("No exception");
        }
        catch(AppCeleratorHttpException $exception)
        {
            $this->assertSame("invalid_client", $exception->getOAuthError());
            $this->assertSame("credentialsInvalid", $exception->getErrorCode());
        }
    }

    public function testAServiceWithoutUrlIsUnknown() : void
    {
        $this->expectException(AppCeleratorException::class);

        $this->client()->billing->request("GET", "/x/");
    }

    public function testTheTokenOfSdk1StillWorks() : void
    {
        $client = new Client("fixed-token", ["countries" => self::$base . "/countries"]);

        $this->assertSame("Bearer fixed-token", $client->countries->countries->get("nl")["authorization"]);
        $this->assertSame("Bearer fixed-token", $client->countries->request("GET", "/country/nl/")->json()["data"]["authorization"]);
        $this->assertSame([], self::tokenRequests());
    }
}
