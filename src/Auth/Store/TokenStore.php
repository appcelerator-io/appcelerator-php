<?php
declare(strict_types=1);

namespace AppCelerator\Auth\Store;

/**
 * TokenStore
 *  Where TokenProvider keeps tokens, one per key (a token per service, scope, tenant and user). A stored token is
 *  ["token" => JWT, "expires" => unix time]. Every method fails soft: a store that cannot be read or written behaves as
 *  empty, the provider then requests a new token.
 */
interface TokenStore
{
    /**
     * get
     *  The stored token of $key, null when there is none or it cannot be read
     *
     * @return null|array{token: string, expires: int}
     */
    public function get(string $key) : null|array;

    /**
     * put
     */
    public function put(string $key, #[\SensitiveParameter] string $token, int $expires) : void;

    /**
     * forget
     *  Removes the token of $key, every token without $key
     */
    public function forget(null|string $key = null) : void;

    /**
     * synchronized
     *  Runs $renew while holding the lock of $key, so concurrent processes do not all request a token at the same time. When
     *  the lock cannot be taken in time, $renew runs anyway: a token is always returned.
     */
    public function synchronized(string $key, callable $renew) : mixed;
}
