<?php
declare(strict_types=1);

namespace AppCelerator\Utils;

/**
 * Headers
 *  Header names are case insensitive: a later set replaces a header of an earlier one, whatever its case
 */
final class Headers
{
    /**
     * merge
     */
    public static function merge(array ...$sets) : array
    {
        $headers = [];
        $names = []; // lowercase => name as given

        foreach($sets as $set)
        {
            foreach($set as $name => $value)
            {
                $lower = strtolower((string) $name);

                if(isset($names[$lower]))
                    unset($headers[$names[$lower]]);

                $names[$lower] = (string) $name;
                $headers[(string) $name] = $value;
            }
        }

        return $headers;
    }

    /**
     * has
     */
    public static function has(array $headers, string $name) : bool
    {
        foreach(array_keys($headers) as $key)
            if(strtolower((string) $key) === strtolower($name))
                return true;

        return false;
    }
}
