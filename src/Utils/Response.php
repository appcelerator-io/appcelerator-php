<?php
declare(strict_types=1);

namespace AppCelerator\Utils;

/**
 * Response
 *  The HTTP response of an API call: status code, headers and body
 */
class Response
{
    private string $method;
    private string $url;

    /**
     * @param array $headers Lowercase header name => value
     */
    public function __construct(private string $content = '', private int $statusCode = 200, private array $headers = [])
    {
        $this->method = '';
        $this->url = '';
    }

    public function getContent() : string
    {
        return $this->content;
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function isSuccessful() : bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isJson() : bool
    {
        return json_validate($this->content);
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getHeader(string $name) : null|string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function hasMethod()
    {
        return strlen($this->method);
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    public function hasUrl()
    {
        return strlen($this->url);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    /**
     * Returns the decoded JSON body, an empty array when the body is empty or not JSON
     */
    public function getParameters() : array
    {
        if(!$this->isJson())
            return [];

        $data = json_decode($this->content, true);

        return is_array($data) ? $data : [];
    }
}
