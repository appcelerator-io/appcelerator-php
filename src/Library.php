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

function str_must_start_with(string $haystack, string $needle)
{
    if(strlen($haystack) == 0)
        return $haystack;

    if(substr($haystack, 0, strlen($needle)) == $needle)
        return $haystack;

    return "$needle$haystack";
}

function str_must_not_start_with(string $haystack, string $needle)
{
    if(strlen($haystack) > 0)
        if(substr($haystack, 0, strlen($needle)) == $needle)
            return substr($haystack, strlen($needle));

    return "$haystack";
}

function str_must_end_with(string $haystack, string $needle)
{
    if(strlen($haystack) == 0)
        return $haystack;

    if(substr($haystack, strlen($haystack) - strlen($needle)) == $needle)
        return $haystack;

    return "$haystack$needle";
}

function str_must_not_end_with(string $haystack, string $needle)
{
    if(strlen($haystack) > 0)
        if(substr($haystack, strlen($haystack) - strlen($needle)) == $needle)
            return substr($haystack, 0, strlen($haystack) - strlen($needle));

    return "$haystack";
}