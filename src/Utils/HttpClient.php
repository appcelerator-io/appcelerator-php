<?php
declare(strict_types=1);

namespace AppCelerator\Utils;

use AppCelerator\Exceptions\AppCeleratorException;

/**
 * HttpClient
 *  Sends one HTTP request with curl. The body is sent as given: a string (raw, e.g. JSON or a proxied body) or an array of
 *  multipart fields (values may be CURLFile). Network failures throw, every HTTP status is a Response.
 */
final class HttpClient
{
    /**
     * send
     *  $headers: name => value
     */
    public static function send(string $method, string $url, null|string|array $body = null, array $headers = [], int $timeout = 30) : Response
    {
        $method = strtoupper($method);

        $curl = curl_init();

        if($curl === false)
            throw new AppCeleratorException("Curl init failed");

        $responseHeaders = [];

        $curlHeaders = [];
        foreach($headers as $name => $value)
            if($value !== null)
                $curlHeaders[] = "$name: $value";

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_FOLLOWLOCATION => false, // a redirect must not take the token to another host
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_HEADERFUNCTION => function($curl, string $header) use (&$responseHeaders)
            {
                $parts = explode(":", $header, 2);

                if(count($parts) === 2)
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);

                return strlen($header);
            },
        ]);

        if($body !== null && $method !== "GET" && $method !== "HEAD")
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

        if($method === "HEAD")
            curl_setopt($curl, CURLOPT_NOBODY, true);

        $result = curl_exec($curl);

        if($result === false)
        {
            $error = curl_error($curl);

            throw new AppCeleratorException("Request failed: $error ($method $url)");
        }

        $response = new Response(is_string($result) ? $result : "", (int) curl_getinfo($curl, CURLINFO_HTTP_CODE), $responseHeaders);
        $response->setMethod($method);
        $response->setUrl($url);

        return $response;
    }
}
