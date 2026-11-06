<?php
declare(strict_types=1);

namespace AppCelerator\Auth;

use AppCelerator\Auth\Store\ApcuTokenStore;
use AppCelerator\Auth\Store\FileTokenStore;
use AppCelerator\Auth\Store\TokenStore;
use AppCelerator\Exceptions\AppCeleratorException;

/**
 * TokenProvider
 *  Requests, caches and renews tokens. A token is cached per scope (in practice per service: {resourceURI}/.default), per
 *  user it acts for (token exchange) and per tenant, and renewed REFRESH_BUFFER seconds before it expires:
 *      - in this process (memory)
 *      - in the TokenStore: by default APCu when it is enabled (php-fpm, shared by the workers), otherwise a file in the temp
 *        directory (CLI, development), see defaultStore
 *  One process requests a token, the others wait for it (TokenStore::synchronized). forget() drops a token the service
 *  rejected, the next call requests a new one.
 */
final class TokenProvider
{
    private const REFRESH_BUFFER = 60; // seconds before expiry to renew

    private array $memory = []; // key => ["token", "expires"]

    public function __construct(private TokenEndpoint $endpoint, private TokenStore $store, private string $clientId = "")
    {}

    /**
     * defaultStore
     *  APCu when it is enabled, otherwise a file ($cacheFile, or one in the temp directory per application and client)
     */
    public static function defaultStore(string $clientId, null|string $cacheFile = null) : TokenStore
    {
        if(ApcuTokenStore::isAvailable())
            return new ApcuTokenStore();

        return new FileTokenStore($cacheFile ?? FileTokenStore::defaultPath($clientId));
    }

    /**
     * withStore
     *  The same endpoint with another store, e.g. the session of a user for the tokens exchanged on behalf of that user
     */
    public function withStore(TokenStore $store) : self
    {
        return new self($this->endpoint, $store, $this->clientId);
    }

    /**
     * get
     *  A valid token for $scope: of the client itself, or with $subjectToken on behalf of that user (token exchange),
     *  with $tenant for that tenant (only on behalf of a user)
     */
    public function get(string $scope, #[\SensitiveParameter] null|string $subjectToken = null, null|string $tenant = null) : string
    {
        if($tenant !== null && $subjectToken === null)
            throw new AppCeleratorException("A token for tenant '$tenant' is exchanged from the token of a user, use onBehalfOf() first");

        $key = $this->key($scope, $subjectToken, $tenant);

        if(self::isFresh($stored = $this->memory[$key] ?? null))
            return $stored["token"];

        if(!self::isFresh($stored = $this->store->get($key)))
        {
            $stored = $this->store->synchronized($key, function() use ($key, $scope, $subjectToken, $tenant)
            {
                // Another process may have renewed it meanwhile
                if(self::isFresh($stored = $this->store->get($key)))
                    return $stored;

                $stored = $subjectToken !== null
                    ? $this->endpoint->tokenExchange($subjectToken, $scope, $tenant)
                    : $this->endpoint->clientCredentials($scope);

                $this->store->put($key, $stored["token"], $stored["expires"]);

                return $stored;
            });
        }

        $this->memory[$key] = $stored;

        return $stored["token"];
    }

    /**
     * forget
     *  Drops the token, e.g. when the service rejected it (401)
     */
    public function forget(string $scope, #[\SensitiveParameter] null|string $subjectToken = null, null|string $tenant = null) : void
    {
        $key = $this->key($scope, $subjectToken, $tenant);

        unset($this->memory[$key]);
        $this->store->forget($key);
    }

    /**
     * forgetAll
     *  Drops every token of the store, e.g. after the OAuth2 service was installed again (new keys, clients and scopes)
     */
    public function forgetAll() : void
    {
        $this->memory = [];
        $this->store->forget();
    }

    /**
     * key
     *  Never the token of the user itself: a hash of it
     */
    private function key(string $scope, #[\SensitiveParameter] null|string $subjectToken, null|string $tenant) : string
    {
        return "token_" . substr(hash("sha256", implode("|", [$this->clientId, $scope, $tenant ?? "", $subjectToken !== null ? hash("sha256", $subjectToken) : ""])), 0, 40);
    }

    /**
     * isFresh
     */
    private static function isFresh(null|array $stored) : bool
    {
        return $stored !== null && is_string($stored["token"] ?? null) && ($stored["expires"] ?? 0) - self::REFRESH_BUFFER > time();
    }
}
