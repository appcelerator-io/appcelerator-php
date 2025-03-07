<?php
declare(strict_types=1);

namespace AppCelerator\Exceptions;

use AppCelerator\Utils\Response;
use Exception;

/**
 * AppCeleratorHttpException
 */
class AppCeleratorHttpException extends Exception
{
    public static $showUrl = false;

    public function __construct(private string|Response $response)
    {
        $this->response = $response;

        $message = $response;
        $status = 500;

        if($response instanceof Response)
        {
            $content = $response->getParameters();
            $status = $response->getStatusCode();
    
            $message = "$status";
            
            if(array_key_exists("error", $content))
                $message .= " - " . $content["error"];
    
            if(array_key_exists("errorDescription", $content))
                $message .= " - " . $content["errorDescription"];
    
            if(self::$showUrl && $response->hasUrl())
                $message .= " - (url: " . $response->getUrl() . ")";
        }

        parent::__construct($message, $status);
    }

    public function getResponse()
    {
        return $this->response;
    }
}