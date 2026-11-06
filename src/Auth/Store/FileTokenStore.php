<?php
declare(strict_types=1);

namespace AppCelerator\Auth\Store;

/**
 * FileTokenStore
 *  The tokens in a JSON file, for the CLI (cronjobs, app exec), development and php without APCu. Shared by every process of
 *  the same user on this machine, locked with flock. The file holds tokens: it is created readable by its owner only and
 *  lives in the temp directory by default, out of the web root (Config tokenCacheFile sets another path).
 */
class FileTokenStore implements TokenStore
{
    public function __construct(private string $path)
    {}

    /**
     * defaultPath
     *  One file per application (working directory), client and user: php-fpm and the CLI may run as different users
     */
    public static function defaultPath(string $clientId) : string
    {
        $user = function_exists("posix_geteuid") ? (string) posix_geteuid() : get_current_user();

        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "appcelerator-tokens-" . substr(hash("sha256", getcwd() . "|$clientId|$user"), 0, 16) . ".json";
    }

    public function get(string $key) : null|array
    {
        $value = $this->read()[$key] ?? null;

        return is_array($value) && is_string($value["token"] ?? null) && is_int($value["expires"] ?? null) ? $value : null;
    }

    public function put(string $key, #[\SensitiveParameter] string $token, int $expires) : void
    {
        $this->update(function(array $tokens) use ($key, $token, $expires)
        {
            $tokens[$key] = ["token" => $token, "expires" => $expires];

            // Expired tokens are of no use
            return array_filter($tokens, fn($value) => is_array($value) && ($value["expires"] ?? 0) > time());
        });
    }

    public function forget(null|string $key = null) : void
    {
        if($key === null)
        {
            if(is_file($this->path))
                @unlink($this->path);

            return;
        }

        $this->update(function(array $tokens) use ($key)
        {
            unset($tokens[$key]);
            return $tokens;
        });
    }

    public function synchronized(string $key, callable $renew) : mixed
    {
        $handle = @fopen("$this->path.lock", "c");

        if($handle === false)
            return $renew();

        try
        {
            flock($handle, LOCK_EX);
            return $renew();
        }
        finally
        {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * read
     */
    private function read() : array
    {
        $handle = @fopen($this->path, "r");

        if($handle === false)
            return [];

        try
        {
            flock($handle, LOCK_SH);
            $tokens = json_decode((string) stream_get_contents($handle), true);
        }
        finally
        {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return is_array($tokens) ? $tokens : [];
    }

    /**
     * update
     *  Reads, changes and writes the file under an exclusive lock
     */
    private function update(callable $change) : void
    {
        $umask = umask(0077); // a new file is readable by its owner only
        $handle = @fopen($this->path, "c+");
        umask($umask);

        if($handle === false)
            return;

        try
        {
            flock($handle, LOCK_EX);

            $tokens = json_decode((string) stream_get_contents($handle), true);
            $tokens = $change(is_array($tokens) ? $tokens : []);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($tokens));
            fflush($handle);
        }
        finally
        {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
