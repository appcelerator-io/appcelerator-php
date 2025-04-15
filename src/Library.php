<?php
declare(strict_types=1);

namespace AppCelerator;

function is_json($input) : bool 
{
    if(!is_string($input)) return false;
    if(strcmp($input, "null") == 0) return false;
    json_decode($input);
    return (json_last_error() == JSON_ERROR_NONE);
}

function str_start_with(string $haystack, string $needle)
{
    if(strlen($haystack) == 0)
        return $haystack;

    if($haystack[0] == $needle)
        return $haystack;

    return "$needle$haystack";
}

function str_not_start_with(string $haystack, string $needle)
{
    if(strlen($haystack) > 0)
        if($haystack[0] == $needle)
            return substr($haystack, strlen($needle));

    return "$haystack";
}

function str_end_with(string $haystack, string $needle)
{
    if(strlen($haystack) == 0)
        return $haystack;

    if($haystack[strlen($haystack) - 1] == $needle)
        return $haystack;

    return "$haystack$needle";
}

function str_not_end_with(string $haystack, string $needle)
{
    if(strlen($haystack) > 0)
        if($haystack[($len = strlen($haystack)) - 1] == $needle)
            return substr($haystack, 0, $len - 1);

    return "$haystack";
}