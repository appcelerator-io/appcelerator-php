<?php
declare(strict_types=1);

namespace AppCelerator\Auth\Store;

/**
 * MemoryTokenStore
 *  The tokens of this process only (e.g. tests), nothing is shared with other processes
 */
class MemoryTokenStore implements TokenStore
{
    private array $tokens = [];

    public function get(string $key) : null|array
    {
        return $this->tokens[$key] ?? null;
    }

    public function put(string $key, #[\SensitiveParameter] string $token, int $expires) : void
    {
        $this->tokens[$key] = ["token" => $token, "expires" => $expires];
    }

    public function forget(null|string $key = null) : void
    {
        if($key === null)
            $this->tokens = [];
        else
            unset($this->tokens[$key]);
    }

    public function synchronized(string $key, callable $renew) : mixed
    {
        return $renew();
    }
}
