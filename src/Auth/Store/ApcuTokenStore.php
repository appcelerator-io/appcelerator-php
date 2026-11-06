<?php
declare(strict_types=1);

namespace AppCelerator\Auth\Store;

/**
 * ApcuTokenStore
 *  The tokens in APCu, shared by the workers of a php-fpm pool. Only usable when APCu is enabled (see isAvailable): with
 *  apc.enable_cli=0 the functions exist in the CLI but store nothing.
 */
class ApcuTokenStore implements TokenStore
{
    private const PREFIX = "APPCELERATOR_TOKEN:";
    private const LOCK_PREFIX = "APPCELERATOR_TOKEN_LOCK:";
    private const LOCK_TTL = 30; // seconds, a crashed renewal releases the lock
    private const LOCK_WAIT = 10; // seconds waiting for another process to renew

    /**
     * isAvailable
     */
    public static function isAvailable() : bool
    {
        return function_exists("apcu_enabled") && apcu_enabled();
    }

    public function get(string $key) : null|array
    {
        $value = apcu_fetch(self::PREFIX . $key, $success);

        return $success && is_array($value) && is_string($value["token"] ?? null) && is_int($value["expires"] ?? null) ? $value : null;
    }

    public function put(string $key, #[\SensitiveParameter] string $token, int $expires) : void
    {
        apcu_store(self::PREFIX . $key, ["token" => $token, "expires" => $expires], max(1, $expires - time()));
    }

    public function forget(null|string $key = null) : void
    {
        if($key !== null)
        {
            apcu_delete(self::PREFIX . $key);
            return;
        }

        if(class_exists(\APCUIterator::class))
            apcu_delete(new \APCUIterator("/^" . preg_quote(self::PREFIX, "/") . "/"));
    }

    public function synchronized(string $key, callable $renew) : mixed
    {
        $lock = self::LOCK_PREFIX . $key;
        $deadline = microtime(true) + self::LOCK_WAIT;

        while(!($locked = apcu_add($lock, 1, self::LOCK_TTL)) && microtime(true) < $deadline)
            usleep(100_000);

        try
        {
            return $renew();
        }
        finally
        {
            if($locked)
                apcu_delete($lock);
        }
    }
}
