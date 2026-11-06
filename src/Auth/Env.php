<?php
declare(strict_types=1);

namespace AppCelerator\Auth;

/**
 * Env
 *  Environment variables, from getenv(), $_ENV or $_SERVER (whichever the application's .env loader fills)
 */
final class Env
{
    /**
     * get
     *  The value of $name, null when it is not set or empty
     */
    public static function get(string $name) : null|string
    {
        $value = getenv($name);

        if(!is_string($value) || $value === "")
            $value = $_ENV[$name] ?? $_SERVER[$name] ?? null;

        return is_string($value) && $value !== "" ? $value : null;
    }

    /**
     * all
     *  Every variable, name => value
     */
    public static function all() : array
    {
        $variables = array_merge(
            array_filter($_SERVER, fn($value) => is_string($value)),
            array_filter($_ENV, fn($value) => is_string($value)),
            getenv(),
        );

        return array_filter($variables, fn($value) => is_string($value) && $value !== "");
    }
}
