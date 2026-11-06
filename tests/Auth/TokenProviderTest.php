<?php
declare(strict_types=1);

namespace AppCelerator\Tests\Auth;

use AppCelerator\Auth\Store\FileTokenStore;
use AppCelerator\Auth\Store\MemoryTokenStore;
use AppCelerator\Auth\TokenEndpoint;
use AppCelerator\Auth\TokenProvider;
use AppCelerator\Exceptions\AppCeleratorException;
use PHPUnit\Framework\TestCase;

/**
 * CountingEndpoint
 *  Hands out numbered tokens and records what was asked
 */
final class CountingEndpoint implements TokenEndpoint
{
    public array $requests = [];
    public int $lifetime = 3600;

    public function clientCredentials(string $scope) : array
    {
        $this->requests[] = ["client_credentials", $scope];

        return ["token" => "t" . count($this->requests), "expires" => time() + $this->lifetime];
    }

    public function tokenExchange(string $subjectToken, string $scope, null|string $tenant = null) : array
    {
        $this->requests[] = ["exchange", $scope, $subjectToken, $tenant];

        return ["token" => "t" . count($this->requests), "expires" => time() + $this->lifetime];
    }
}

/**
 * TokenProviderTest
 *  A token per scope, user and tenant, cached and renewed before it expires
 */
final class TokenProviderTest extends TestCase
{
    private CountingEndpoint $endpoint;
    private MemoryTokenStore $store;
    private string $file;

    protected function setUp() : void
    {
        $this->endpoint = new CountingEndpoint();
        $this->store = new MemoryTokenStore();
        $this->file = sys_get_temp_dir() . "/appcelerator-tokens-test-" . uniqid() . ".json";
    }

    protected function tearDown() : void
    {
        @unlink($this->file);
        @unlink("$this->file.lock");
    }

    private function provider() : TokenProvider
    {
        return new TokenProvider($this->endpoint, $this->store, "client_test");
    }

    public function testATokenIsRequestedOnceAndCached() : void
    {
        $provider = $this->provider();

        $this->assertSame("t1", $provider->get("api://files-service/.default"));
        $this->assertSame("t1", $provider->get("api://files-service/.default"));
        $this->assertSame("t1", $this->provider()->get("api://files-service/.default"), "Another provider on the same store");
        $this->assertCount(1, $this->endpoint->requests);
    }

    public function testEveryScopeUserAndTenantHasItsOwnToken() : void
    {
        $provider = $this->provider();

        $tokens = [
            $provider->get("api://files-service/.default"),
            $provider->get("api://control-service/.default"),
            $provider->get("api://files-service/.default", "user-token-a"),
            $provider->get("api://files-service/.default", "user-token-b"),
            $provider->get("api://files-service/.default", "user-token-a", "myapp.dev"),
        ];

        $this->assertSame(["t1", "t2", "t3", "t4", "t5"], $tokens);
        $this->assertSame(["exchange", "api://files-service/.default", "user-token-a", "myapp.dev"], $this->endpoint->requests[4]);
    }

    public function testATenantNeedsAUser() : void
    {
        $this->expectException(AppCeleratorException::class);

        $this->provider()->get("api://files-service/.default", null, "myapp.dev");
    }

    public function testATokenIsRenewedBeforeItExpires() : void
    {
        $this->endpoint->lifetime = 30; // within the refresh buffer

        $provider = $this->provider();

        $this->assertSame("t1", $provider->get("api://files-service/.default"));
        $this->assertSame("t2", $provider->get("api://files-service/.default"));
    }

    public function testAForgottenTokenIsRequestedAgain() : void
    {
        $provider = $this->provider();
        $provider->get("api://files-service/.default");
        $provider->get("api://control-service/.default");

        $provider->forget("api://files-service/.default");

        $this->assertSame("t3", $provider->get("api://files-service/.default"));
        $this->assertSame("t2", $provider->get("api://control-service/.default"));

        $provider->forgetAll();

        $this->assertSame("t4", $provider->get("api://control-service/.default"));
    }

    public function testTheTokenOfAUserIsNeverAKey() : void
    {
        $this->store = new MemoryTokenStore();
        $file = new FileTokenStore($this->file);

        (new TokenProvider($this->endpoint, $file, "client_test"))->get("api://files-service/.default", "secret-user-token");

        $this->assertStringNotContainsString("secret-user-token", file_get_contents($this->file));
    }

    public function testTheFileStoreSharesTokensBetweenProcesses() : void
    {
        (new TokenProvider($this->endpoint, new FileTokenStore($this->file), "client_test"))->get("api://files-service/.default");

        // Another process: a new provider (empty memory) on the same file
        $this->assertSame("t1", (new TokenProvider($this->endpoint, new FileTokenStore($this->file), "client_test"))->get("api://files-service/.default"));
        $this->assertCount(1, $this->endpoint->requests);
        $this->assertSame("0600", substr(sprintf("%o", fileperms($this->file)), -4));
    }

    public function testTheFileStoreForgetsAndSurvivesABrokenFile() : void
    {
        $store = new FileTokenStore($this->file);
        $store->put("A", "a", time() + 100);
        $store->put("B", "b", time() + 100);
        $store->put("OLD", "old", time() - 1); // expired, dropped on write

        $store->forget("A");
        $this->assertNull($store->get("A"));
        $this->assertSame("b", $store->get("B")["token"]);
        $this->assertNull($store->get("OLD"));

        file_put_contents($this->file, "not json");
        $this->assertNull($store->get("B"));

        $store->put("C", "c", time() + 100);
        $this->assertSame("c", $store->get("C")["token"]);

        $store->forget();
        $this->assertNull($store->get("C"));
    }

    public function testWithoutApcuTheDefaultStoreIsAFile() : void
    {
        if(function_exists("apcu_enabled") && apcu_enabled())
            $this->markTestSkipped("APCu is enabled");

        $this->assertInstanceOf(FileTokenStore::class, TokenProvider::defaultStore("client_test", $this->file));
    }
}
