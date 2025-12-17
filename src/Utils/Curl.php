<?php
declare(strict_types=1);

namespace AppCelerator\Utils;

use AppCelerator\Exceptions\AppCeleratorHttpException;
use Exception;

/**
 * Curl
 */
abstract class Curl
{
    public function __construct(private null|string $token = null)
    { }

    /**
     * call
     */
    function call(string $method, string $url, array $data = [], array $headers = [], bool $verbose = false, bool $debug = false)
    {
        $method = strtoupper($method);

        $curl = curl_init();

        if($curl === false)
            throw new Exception("Curl init failed");

        $hasHeader = function($key, $value, $headers) {
            return @$headers[$key] == strtolower($value);
        };

        if(count($data))
        {
            if($method === "POST" || $method === "PUT")
            {
                if($hasHeader("Content-Type", "multipart\/form-data", $headers))
                    $data = $data;
                else if($hasHeader("Content-Type", "application/json", $headers))
                    $data = json_encode($data);
                else
                    $data = http_build_query($data, "", null, PHP_QUERY_RFC3986);
            }
            else if($method === "GET" || "DELETE")
            {
                $url .= "?" . http_build_query($data, "", null, PHP_QUERY_RFC3986);
            }
        }

        if(is_string($this->token) && !is_string(@$headers["Authorization"]))
            $headers["Authorization"] = "Bearer " . $this->token;

        if(count($headers))
        {
            $_headers = [];
            foreach($headers as $key => $value)
                $_headers[] = "$key: $value";
        }

        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $_headers,
        );

        // Add params
        if($method === "POST" || $method === "PUT" || $method === "DELETE")
        {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        if($verbose)
        {
            curl_setopt($curl, CURLOPT_VERBOSE, 1);
        }

        // Set options
        curl_setopt_array($curl, $curlOptions);

        // Get result
        $result = curl_exec($curl);

        // Handle result
        if($result === false)
        {
            if(!$debug)
                throw new Exception("Curl failed");

            // Debug
            $debugData = [
                "error" => curl_error($curl),
                "errno" => curl_errno($curl),
                "curlinfo" => curl_getinfo($curl),
                "private" => curl_getinfo($curl, CURLINFO_PRIVATE),
                "request" => [
                    "method" => $method,
                    "url" => $url,
                    "data" => $data,
                    "headers" => $headers,
                ]
            ];

            // Error
            echo(json_encode($debugData, JSON_PRETTY_PRINT));
            exit();
        }
        else
        {
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Result empty, set to empty array instead of leaving string empty
            $result = is_string($result) && strlen($result) == 0 ? [] : $result;

            // Get result
            $response = is_string($result) && json_validate($result) ? new Response($result, $statusCode) : $result;

            // Not a response
            if($response instanceof Response === false)
                throw new AppCeleratorHttpException($response);

            // Add information
            if(defined('CURLINFO_EFFECTIVE_METHOD'))
                $response->setMethod(curl_getinfo($curl, CURLINFO_EFFECTIVE_METHOD));

            if(defined('CURLINFO_EFFECTIVE_URL'))
                $response->setUrl($url);

            // Return response
            return $response;
        }
    }
}