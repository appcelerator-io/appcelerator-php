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

function format_uri(...$args) : string
{
    foreach($args as $i => &$v)
    {
        $v = str_starts_with($v, "/") ? substr($v, 1) : $v;
        $v = str_ends_with($v, "/") ? substr($v, 0, strlen($v) - 1) : $v;
    }
    return implode("/", $args);
}