<?php
declare(strict_types=1);

namespace AppCelerator\Utils;

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

        $_headers = [];
        foreach($headers as $key => $value)
            $_headers[] = "$key: $value";

        // Response headers, lowercase name => value
        $responseHeaders = [];

        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $_headers,
            CURLOPT_HEADERFUNCTION => function($curl, string $header) use (&$responseHeaders) {
                $parts = explode(":", $header, 2);

                if(count($parts) === 2)
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);

                return strlen($header);
            },
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
                throw new Exception("Curl failed: " . curl_error($curl) . " ($method $url)");

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

            // Empty and non JSON bodies are a Response too, Controller::handleResponseErrors decides if it is an error
            $response = new Response(is_string($result) ? $result : "", $statusCode, $responseHeaders);

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