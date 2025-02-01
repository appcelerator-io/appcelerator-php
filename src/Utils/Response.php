<?php
declare(strict_types=1);

namespace AppCelerator\Utils;

use function AppCelerator\is_json;

/**
 * Response
 */
class Response extends \Symfony\Component\HttpFoundation\Response
{
    private string $method;
    private string $url;

    public function __construct(?string $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);
        $this->method = '';
        $this->url = '';
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
    
    public function getParameters()
    {
        $data = [];

        if(is_json($this->getContent()))
            $data = json_decode($this->getContent(), true);

        if($this->hasUrl())
            $data["url"] = $this->getUrl();

        return $data;
    }
}